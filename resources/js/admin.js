/*
 * Trang quản trị: trình soạn thảo nội dung + bảng chấm điểm SEO trực tiếp.
 * Đăng ký trước khi Alpine khởi động (file này được nạp trước app.js trong layout quản trị).
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export function slugify(str) {
    return String(str ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

const lower = (s) => (s || '').toLocaleLowerCase('vi');
const countOccurrences = (text, needle) => {
    if (!needle) return 0;
    let n = 0;
    let i = text.indexOf(needle);
    while (i !== -1) {
        n++;
        i = text.indexOf(needle, i + needle.length);
    }
    return n;
};

/* ---------- Làm sạch HTML khi dán từ Word / Google Docs / web khác ---------- */
const ALLOWED = new Set(['P', 'BR', 'H2', 'H3', 'H4', 'STRONG', 'EM', 'U', 'UL', 'OL', 'LI', 'A', 'IMG', 'BLOCKQUOTE', 'TABLE', 'THEAD', 'TBODY', 'TR', 'TH', 'TD', 'HR']);
const RENAME = { B: 'STRONG', I: 'EM', H1: 'H2', H5: 'H4', H6: 'H4', DIV: 'P', SECTION: 'P', ARTICLE: 'P' };

export function cleanHtml(html) {
    const doc = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html');
    const walk = (node) => {
        [...node.childNodes].forEach((child) => {
            if (child.nodeType === Node.COMMENT_NODE) return child.remove();
            if (child.nodeType !== Node.ELEMENT_NODE) return;
            if (['SCRIPT', 'STYLE', 'META', 'LINK', 'IFRAME', 'OBJECT', 'TITLE'].includes(child.tagName)) return child.remove();

            walk(child);
            let tag = RENAME[child.tagName] || child.tagName;
            if (!ALLOWED.has(tag)) {
                child.replaceWith(...child.childNodes); // bỏ thẻ, giữ chữ (span, font...)
                return;
            }
            let el = child;
            if (tag !== child.tagName) {
                el = doc.createElement(tag);
                el.append(...child.childNodes);
                child.replaceWith(el);
            }
            const keep = { A: ['href'], IMG: ['src', 'alt'], TH: ['colspan', 'rowspan'], TD: ['colspan', 'rowspan'] }[tag] || [];
            [...el.attributes].forEach((a) => {
                if (!keep.includes(a.name) || /^\s*(javascript|data|vbscript):/i.test(a.value)) el.removeAttribute(a.name);
            });
        });
    };
    walk(doc.body);
    return doc.body.innerHTML.replace(/<p>(\s|&nbsp;|<br>)*<\/p>/g, '').trim();
}

/* ---------- Flash Sale: chọn sản phẩm + giá flash ---------- */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('flashItems', (rows, searchUrl) => ({
        rows: rows || [],
        q: '',
        results: [],
        open: false,
        loading: false,
        money(v) {
            return v ? Number(v).toLocaleString('vi-VN') + 'đ' : '';
        },
        has(id) {
            return this.rows.some((r) => r.product_id === id);
        },
        pct(r) {
            return r.price && r.sale_price ? Math.round(100 - (r.sale_price * 100) / r.price) : 0;
        },
        async search() {
            this.loading = true;
            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(this.q)}`, { headers: { Accept: 'application/json' } });
                this.results = res.ok ? await res.json() : [];
            } finally {
                this.loading = false;
            }
        },
        add(r) {
            if (this.has(r.product_id)) return;
            // Gợi ý mặc định giảm 10%, làm tròn xuống bội số 100đ.
            this.rows.push({ ...r, sale_price: Math.floor((r.price * 0.9) / 100) * 100, quantity_limit: null });
        },
        applyAll(p) {
            this.rows.forEach((r) => (r.sale_price = Math.floor((r.price * (100 - p)) / 100 / 100) * 100));
        },
        move(i, d) {
            const j = i + d;
            if (j < 0 || j >= this.rows.length) return;
            [this.rows[i], this.rows[j]] = [this.rows[j], this.rows[i]];
        },
    }));
});

/* ---------- Thành phần chính của form quản trị ---------- */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('adminForm', (cfg) => ({
        type: cfg.type, // post | product | category
        brand: cfg.brand,
        baseUrl: cfg.baseUrl.replace(/\/$/, ''),
        prefix: cfg.prefix, // /tin-tuc/
        uploadUrl: cfg.uploadUrl,
        exists: cfg.exists,
        originalSlug: cfg.f.slug || '',
        slugTouched: !!cfg.f.slug,
        f: cfg.f, // các trường: title, slug, meta_title, meta_description, focus_keyword, summary, content, brand...
        hasImage: !!cfg.hasImage,
        images: cfg.images || [], // sản phẩm: ảnh đang có
        newImages: [], // sản phẩm: ảnh mới chọn (xem trước)
        rows: cfg.rows || [], // FAQ hoặc thông số kỹ thuật
        htmlMode: false,
        uploading: false,
        savedRange: null,
        dirty: false,
        slugify,
        fullscreen: false,
        painter: null, // định dạng đã sao chép bằng nút chổi
        fmt: {}, // trạng thái nút (đậm, nghiêng, căn lề...) tại vị trí con trỏ
        maxHtml: 100000,
        fonts: [
            ['Mặc định', ''],
            ['Roboto', "'Roboto Variable', Roboto, Arial, sans-serif"],
            ['Arial', 'Arial, Helvetica, sans-serif'],
            ['Tahoma', 'Tahoma, Geneva, sans-serif'],
            ['Verdana', 'Verdana, Geneva, sans-serif'],
            ['Times New Roman', "'Times New Roman', Times, serif"],
            ['Georgia', 'Georgia, serif'],
            ['Courier New', "'Courier New', monospace"],
        ],
        sizes: [12, 13, 14, 15, 16, 18, 20, 24, 28, 32, 36],
        lineHeights: ['1', '1.15', '1.5', '1.8', '2', '2.5'],
        palette: [
            '#000000', '#333333', '#555555', '#777777', '#999999', '#cccccc', '#eeeeee', '#ffffff',
            '#b3262d', '#e53935', '#f4511e', '#fb8c00', '#fdd835', '#43a047', '#00897b', '#039be5',
            '#1e88e5', '#3949ab', '#5e35b1', '#8e24aa', '#d81b60', '#6d4c41', '#fce4ec', '#fff8e1',
            '#e8f5e9', '#e3f2fd', '#ede7f6', '#fff3e0', '#e0f7fa', '#f3e5f5', '#ffebee', '#f1f8e9',
        ],
        foreColor: '#b3262d',
        backColor: '#fdd835',

        init() {
            this.$refs.editor && (this.$refs.editor.innerHTML = this.f.content || '');
            document.addEventListener('selectionchange', () => {
                const sel = window.getSelection();
                if (this.$refs.editor && sel.rangeCount && this.$refs.editor.contains(sel.anchorNode)) this.updateFmt();
            });
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.fullscreen) this.fullscreen = false;
            });
            this.$watch('f.title', (v) => {
                if (!this.slugTouched) this.f.slug = slugify(v);
            });
            this.$el.addEventListener('input', () => (this.dirty = true));
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) e.preventDefault();
            });
            this.$el.addEventListener('submit', () => {
                this.syncFromEditor();
                this.dirty = false;
            });
        },

        /* ----- Trình soạn thảo ----- */
        syncFromEditor() {
            if (!this.htmlMode && this.$refs.editor) this.f.content = this.$refs.editor.innerHTML;
        },
        toggleHtml() {
            if (this.htmlMode) {
                this.$refs.editor.innerHTML = this.f.content;
            } else {
                this.syncFromEditor();
            }
            this.htmlMode = !this.htmlMode;
        },
        keepSelection() {
            const sel = window.getSelection();
            if (sel.rangeCount && this.$refs.editor.contains(sel.anchorNode)) this.savedRange = sel.getRangeAt(0).cloneRange();
        },
        restoreSelection() {
            this.$refs.editor.focus();
            if (this.savedRange) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(this.savedRange);
            }
        },
        cmd(command, value = null) {
            this.restoreSelection();
            document.execCommand(command, false, value);
            this.syncFromEditor();
            this.keepSelection();
            this.updateFmt();
        },
        block(tag) {
            this.cmd('formatBlock', `<${tag}>`);
        },
        updateFmt() {
            const q = (c) => {
                try {
                    return document.queryCommandState(c);
                } catch {
                    return false;
                }
            };
            this.fmt = {
                bold: q('bold'), italic: q('italic'), underline: q('underline'),
                ol: q('insertOrderedList'), ul: q('insertUnorderedList'),
                left: q('justifyLeft'), center: q('justifyCenter'), right: q('justifyRight'), full: q('justifyFull'),
                block: (document.queryCommandValue('formatBlock') || '').toLowerCase(),
            };
        },
        get blockLabel() {
            return { h2: 'Tiêu đề 2', h3: 'Tiêu đề 3', h4: 'Tiêu đề 4', blockquote: 'Trích dẫn', pre: 'Mã' }[this.fmt.block] || 'Định dạng';
        },
        get htmlLength() {
            return (this.f.content || '').length;
        },

        /* Bọc phần chữ đang chọn bằng <span style="..."> (phông, cỡ chữ, màu chữ, màu nền). */
        applyStyles(styles) {
            this.restoreSelection();
            const sel = window.getSelection();
            if (!sel.rangeCount || sel.isCollapsed) {
                window.alert('Hãy bôi đen đoạn chữ cần định dạng trước.');
                return;
            }
            const ed = this.$refs.editor;
            document.execCommand('styleWithCSS', false, false);
            document.execCommand('fontName', false, 'adm-tmp');
            let last = null;
            ed.querySelectorAll('font[face="adm-tmp"], span[style*="adm-tmp"]').forEach((el) => {
                const span = document.createElement('span');
                if (el.tagName === 'SPAN') {
                    span.style.cssText = el.style.cssText;
                    span.style.removeProperty('font-family');
                }
                Object.entries(styles).forEach(([prop, value]) => {
                    el.querySelectorAll('[style]').forEach((d) => d.style.removeProperty(prop)); // giá trị mới thắng
                    if (value) span.style.setProperty(prop, value);
                    else span.style.removeProperty(prop);
                });
                span.append(...el.childNodes);
                el.replaceWith(span);
                last = span;
            });
            // span rỗng style -> bỏ thẻ
            ed.querySelectorAll('span:not([style]), span[style=""]').forEach((s) => s.replaceWith(...s.childNodes));
            if (last && last.isConnected) {
                const r = document.createRange();
                r.selectNodeContents(last);
                sel.removeAllRanges();
                sel.addRange(r);
            }
            this.keepSelection();
            this.syncFromEditor();
        },
        setFont(family) {
            this.applyStyles({ 'font-family': family });
        },
        setSize(px) {
            this.applyStyles({ 'font-size': px ? `${px}px` : '' });
        },
        setColor(kind, value) {
            if (kind === 'fore') this.foreColor = value || this.foreColor;
            else this.backColor = value || this.backColor;
            this.applyStyles({ [kind === 'fore' ? 'color' : 'background-color']: value });
        },
        align(dir) {
            this.restoreSelection();
            document.execCommand('styleWithCSS', false, true);
            document.execCommand({ left: 'justifyLeft', center: 'justifyCenter', right: 'justifyRight', full: 'justifyFull' }[dir]);
            document.execCommand('styleWithCSS', false, false);
            this.syncFromEditor();
            this.keepSelection();
            this.updateFmt();
        },
        selectedBlocks() {
            const ed = this.$refs.editor;
            const sel = window.getSelection();
            if (!sel.rangeCount) return [];
            const range = sel.getRangeAt(0);
            const BLOCK = /^(P|H2|H3|H4|LI|BLOCKQUOTE|DIV|TD|TH|PRE)$/;
            const leaves = [...ed.querySelectorAll('p,h2,h3,h4,li,blockquote,div,td,th,pre')]
                .filter((b) => range.intersectsNode(b) && ![...b.children].some((c) => BLOCK.test(c.tagName)));
            if (leaves.length) return leaves;
            let n = sel.anchorNode;
            while (n && n !== ed && !(n.nodeType === 1 && BLOCK.test(n.tagName))) n = n.parentNode;
            return n && n !== ed ? [n] : [];
        },
        setLineHeight(v) {
            this.restoreSelection();
            let blocks = this.selectedBlocks();
            if (!blocks.length) {
                document.execCommand('formatBlock', false, '<p>');
                blocks = this.selectedBlocks();
            }
            blocks.forEach((b) => (b.style.lineHeight = v || ''));
            this.syncFromEditor();
        },
        insertTable(rows, cols) {
            const th = Array.from({ length: cols }, (_, i) => `<th>Cột ${i + 1}</th>`).join('');
            const td = Array.from({ length: cols }, () => '<td><br></td>').join('');
            const body = Array.from({ length: Math.max(rows - 1, 1) }, () => `<tr>${td}</tr>`).join('');
            this.cmd('insertHTML', `<table><thead><tr>${th}</tr></thead><tbody>${body}</tbody></table><p><br></p>`);
        },
        anchor() {
            this.restoreSelection();
            const [blockEl] = this.selectedBlocks();
            if (!blockEl) return window.alert('Đặt con trỏ vào đoạn / tiêu đề cần gắn neo.');
            const name = window.prompt('Tên neo (dùng để làm link nhảy tới mục này, VD: #cach-chon-giay):', blockEl.id || slugify(blockEl.textContent).slice(0, 40));
            if (name === null) return;
            const id = slugify(name);
            if (id) blockEl.id = /^[a-z]/.test(id) ? id : `muc-${id}`;
            else blockEl.removeAttribute('id');
            this.syncFromEditor();
            if (blockEl.id) window.alert(`Đã gắn neo. Link tới mục này: #${blockEl.id}`);
        },
        embed() {
            this.keepSelection();
            const code = window.prompt('Dán link YouTube, hoặc mã nhúng <iframe> của YouTube / Google Maps:');
            if (!code) return;
            const yt = code.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{6,})/);
            const html = yt
                ? `<iframe src="https://www.youtube.com/embed/${yt[1]}" width="560" height="315" title="Video YouTube" loading="lazy" allowfullscreen></iframe>`
                : code.trim();
            this.cmd('insertHTML', `${html}<p><br></p>`);
        },
        /* Chổi định dạng: lần bấm 1 sao chép định dạng tại con trỏ, bôi đen chữ khác để dán. */
        togglePainter() {
            if (this.painter) return (this.painter = null);
            this.restoreSelection();
            const ed = this.$refs.editor;
            let el = window.getSelection().anchorNode;
            if (el && el.nodeType === 3) el = el.parentNode;
            if (!el || !ed.contains(el)) return;
            const styles = {};
            for (let n = el; n && n !== ed; n = n.parentNode) {
                if (n.tagName === 'SPAN') {
                    ['color', 'background-color', 'font-size', 'font-family'].forEach((p) => {
                        if (!(p in styles) && n.style.getPropertyValue(p)) styles[p] = n.style.getPropertyValue(p);
                    });
                }
            }
            this.updateFmt();
            this.painter = { bold: this.fmt.bold, italic: this.fmt.italic, underline: this.fmt.underline, styles };
        },
        applyPainter() {
            const sel = window.getSelection();
            if (!this.painter || sel.isCollapsed || !this.$refs.editor.contains(sel.anchorNode)) return;
            const p = this.painter;
            this.painter = null;
            this.keepSelection();
            ['bold', 'italic', 'underline'].forEach((c) => {
                if (document.queryCommandState(c) !== p[c]) document.execCommand(c);
            });
            this.keepSelection();
            const all = { color: '', 'background-color': '', 'font-size': '', 'font-family': '', ...p.styles };
            this.applyStyles(all);
        },
        clearFormat() {
            this.cmd('removeFormat');
            // removeFormat không bỏ span style -> bỏ thủ công trong vùng chọn
            const sel = window.getSelection();
            if (!sel.rangeCount) return;
            const range = sel.getRangeAt(0);
            this.$refs.editor.querySelectorAll('span[style]').forEach((s) => {
                if (range.intersectsNode(s)) s.replaceWith(...s.childNodes);
            });
            this.syncFromEditor();
        },
        link() {
            this.keepSelection();
            const url = window.prompt('Nhập link (VD: /danh-muc/giay-in hoặc https://...):', '/');
            if (!url) return;
            if (/^\s*(javascript|data|vbscript):/i.test(url)) return;
            this.cmd('createLink', url.trim());
        },
        pickImage() {
            this.keepSelection();
            this.$refs.imageInput.click();
        },
        async uploadImage(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file) return;
            const alt = window.prompt('Mô tả ảnh (alt) – nói rõ ảnh có gì, nên chứa từ khóa nếu phù hợp:', this.f.focus_keyword || '');
            if (alt === null) return;

            const body = new FormData();
            body.append('image', file);
            body.append('name', alt);
            this.uploading = true;
            try {
                const res = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    body,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Tải ảnh thất bại');
                const safeAlt = alt.replace(/"/g, '&quot;');
                this.cmd('insertHTML', `<p><img src="${data.url}" alt="${safeAlt}"></p>`);
            } catch (e) {
                window.alert(e.message);
            } finally {
                this.uploading = false;
            }
        },
        editAlt(event) {
            if (event.target.tagName !== 'IMG') return;
            const alt = window.prompt('Mô tả ảnh (alt):', event.target.getAttribute('alt') || '');
            if (alt !== null) {
                event.target.setAttribute('alt', alt);
                this.syncFromEditor();
            }
        },
        onPaste(event) {
            const html = event.clipboardData.getData('text/html');
            const text = event.clipboardData.getData('text/plain');
            event.preventDefault();
            const clean = html
                ? cleanHtml(html)
                : text.split(/\n{2,}/).map((p) => `<p>${p.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>')}</p>`).join('');
            document.execCommand('insertHTML', false, clean);
            this.syncFromEditor();
        },

        /* ----- Ảnh sản phẩm ----- */
        moveImage(i, d) {
            const j = i + d;
            if (j < 0 || j >= this.images.length) return;
            [this.images[i], this.images[j]] = [this.images[j], this.images[i]];
        },
        pickNewImages(event) {
            this.newImages.forEach((u) => URL.revokeObjectURL(u));
            this.newImages = [...event.target.files].map((f) => URL.createObjectURL(f));
        },
        get imageCount() {
            return this.type === 'product' ? this.images.length + this.newImages.length : this.hasImage ? 1 : 0;
        },

        /* ----- Xem trước Google ----- */
        get slugChanged() {
            return this.exists && this.originalSlug && slugify(this.f.slug) !== this.originalSlug;
        },
        get defaultTitle() {
            if (this.type === 'product') return (this.f.title || '') + (this.f.brand ? ` – ${this.f.brand}` : '');
            if (this.type === 'category') return `${this.f.title || ''} cho doanh nghiệp – báo giá sỉ`;
            return this.f.title || '';
        },
        get seoTitle() {
            return `${this.f.meta_title || this.defaultTitle} | ${this.brand}`;
        },
        get seoDesc() {
            const d = this.f.meta_description || this.f.summary || '';
            return d.length > 160 ? d.slice(0, 157) + '…' : d;
        },
        get displayUrl() {
            const host = this.baseUrl.replace(/^https?:\/\//, '');
            return `${host} › ${this.prefix.replace(/^\/|\/$/g, '')} › ${slugify(this.f.slug) || '…'}`;
        },
        get fullUrl() {
            return `${this.baseUrl}${this.prefix}${slugify(this.f.slug)}`;
        },

        /* ----- Chấm điểm SEO ----- */
        get plain() {
            const div = document.createElement('div');
            div.innerHTML = this.f.content || '';
            return (div.textContent || '').replace(/\s+/g, ' ').trim();
        },
        get wordCount() {
            return this.plain ? this.plain.split(' ').length : 0;
        },
        get minWords() {
            return { post: 600, product: 150, category: 300 }[this.type];
        },
        get checks() {
            const kw = lower(this.f.focus_keyword).trim();
            const html = this.f.content || '';
            const text = lower(this.plain);
            const titleLen = this.seoTitle.length;
            const descLen = (this.f.meta_description || '').length;
            const c = [];
            const add = (ok, label, tip, warn = false) => c.push({ ok, warn: !ok && warn, label, tip });

            add(!!kw, 'Đã đặt từ khóa chính', 'Nhập cụm từ khách hay tìm trên Google, VD: "giấy in A4 giá sỉ".');
            if (kw) {
                add(lower(this.seoTitle).includes(kw), 'Từ khóa có trong tiêu đề SEO', 'Đưa từ khóa vào tiêu đề, càng gần đầu càng tốt.');
                add(lower(this.f.meta_description).includes(kw), 'Từ khóa có trong mô tả meta', 'Viết mô tả meta có chứa từ khóa.');
                add(slugify(this.f.slug).includes(slugify(kw)), 'Từ khóa có trong đường dẫn (slug)', `Đường dẫn nên chứa "${slugify(kw)}".`, this.exists);
                if (this.type !== 'product') {
                    const first = text.split(' ').slice(0, 120).join(' ');
                    add(first.includes(kw), 'Từ khóa xuất hiện ở đoạn mở đầu', 'Nhắc từ khóa trong khoảng 100 chữ đầu tiên.');
                }
                const kwWords = kw.split(/\s+/).length;
                const density = this.wordCount ? (countOccurrences(text, kw) * kwWords * 100) / this.wordCount : 0;
                add(density >= 0.5 && density <= 2.5, `Mật độ từ khóa ${density.toFixed(1)}% (nên 0,5–2,5%)`,
                    density > 2.5 ? 'Đang lặp từ khóa quá nhiều – Google có thể coi là nhồi từ khóa.' : 'Nhắc từ khóa (hoặc biến thể) thêm vài lần một cách tự nhiên.', true);
            }
            add(titleLen >= 30 && titleLen <= 65, `Độ dài tiêu đề SEO: ${titleLen} ký tự (nên 30–65)`, 'Google cắt tiêu đề dài hơn khoảng 60 ký tự.', true);
            add(descLen >= 120 && descLen <= 160, `Độ dài mô tả meta: ${descLen} ký tự (nên 120–160)`,
                descLen === 0 ? 'Chưa có mô tả meta – Google sẽ tự lấy đoạn bất kỳ.' : 'Điều chỉnh để mô tả hiển thị trọn vẹn.', descLen > 0);
            add(this.wordCount >= this.minWords, `Độ dài nội dung: ${this.wordCount} từ (nên ≥ ${this.minWords})`, 'Nội dung chi tiết, hữu ích giúp lên top tốt hơn.', this.wordCount > this.minWords / 2);

            if (this.type !== 'product') {
                add(/<h2\b/i.test(html), 'Có tiêu đề phụ H2', 'Chia bài thành các mục bằng nút "H2" trên thanh công cụ.');
            }
            add(!/<h1\b/i.test(html), 'Không dùng H1 trong nội dung', 'Trang đã có H1 là tiêu đề; trong nội dung hãy dùng H2/H3.');
            const imgs = html.match(/<img\b[^>]*>/gi) || [];
            add(imgs.every((t) => /\balt\s*=\s*"[^"]+"/i.test(t)), 'Mọi ảnh trong nội dung đều có mô tả (alt)', 'Nhấp đúp vào ảnh trong khung soạn thảo để thêm mô tả.');
            if (this.type !== 'product') {
                const internal = (html.match(/<a\b[^>]*href="[^"]+"/gi) || []).filter((a) => /href="\/(?!\/)/i.test(a) || a.includes(`href="${this.baseUrl}`));
                add(internal.length > 0, 'Có liên kết nội bộ', 'Chèn link tới danh mục / sản phẩm / bài viết khác trên website.', true);
            }
            if (this.type === 'post') add(this.imageCount > 0, 'Có ảnh đại diện', 'Ảnh đại diện hiện khi chia sẻ Facebook/Zalo và trong Google Discover.');
            if (this.type === 'product') {
                add(this.imageCount > 0, 'Có ảnh sản phẩm', 'Sản phẩm có ảnh mới hiện được trên Google Hình ảnh và Google Shopping.');
                add(!!(this.f.summary || '').trim(), 'Có mô tả ngắn', 'Mô tả ngắn hiện ở đầu trang và dùng làm mô tả mặc định.');
            }
            if (this.type === 'category') add(!!(this.f.summary || '').trim(), 'Có đoạn giới thiệu đầu trang', 'Viết 2–3 câu giới thiệu danh mục.', true);
            return c;
        },
        get score() {
            const list = this.checks;
            const pts = list.reduce((s, x) => s + (x.ok ? 1 : x.warn ? 0.5 : 0), 0);
            return Math.round((pts * 100) / list.length);
        },
        get scoreClass() {
            return this.score >= 80 ? 'bg-emerald-600' : this.score >= 50 ? 'bg-amber-500' : 'bg-primary-600';
        },
    }));
});
