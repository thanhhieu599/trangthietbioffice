import Alpine from 'alpinejs';

/*
 * Danh sách báo giá lưu ở trình duyệt (localStorage) dạng [{sku, qty}].
 * Khách thêm nhiều mã rồi gửi một yêu cầu báo giá duy nhất.
 */
const STORAGE_KEY = 'quote_list_v1';

function readList() {
    try {
        const parsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

Alpine.store('quote', {
    items: readList(),

    get count() {
        return this.items.length;
    },
    /** Khóa 1 dòng giỏ: SKU + lựa chọn của khách (VD "Màu sắc: Hồng") khi sản phẩm không có SKU riêng cho từng lựa chọn. */
    key(item) {
        return item.sku + '|' + (item.opts || '');
    },
    has(sku) {
        return this.items.some((i) => i.sku === sku);
    },
    add(sku, qty = 1, opts = '') {
        const found = this.items.find((i) => i.sku === sku && (i.opts || '') === (opts || ''));
        if (found) found.qty += Number(qty) || 1;
        else this.items.push({ sku, qty: Number(qty) || 1, opts: opts || '' });
        this.save();
    },
    setQty(key, qty) {
        const found = this.items.find((i) => this.key(i) === key || i.sku === key);
        if (found) found.qty = Math.max(1, Number(qty) || 1);
        this.save();
    },
    remove(key) {
        this.items = this.items.filter((i) => this.key(i) !== key && i.sku !== key);
        this.save();
    },
    clear() {
        this.items = [];
        this.save();
    },
    save() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
        } catch {
            /* trình duyệt chặn lưu trữ: danh sách chỉ tồn tại trong phiên */
        }
    },
});

/* Trang /bao-gia: lấy tên, ảnh sản phẩm theo SKU để hiển thị danh sách. */
Alpine.data('quotePage', (lookupUrl) => ({
    details: {},
    loading: false,
    async init() {
        const skus = Alpine.store('quote').items.map((i) => i.sku);
        if (!skus.length) return;
        this.loading = true;
        const params = new URLSearchParams();
        skus.forEach((s) => params.append('skus[]', s));
        try {
            const res = await fetch(`${lookupUrl}?${params}`, { headers: { Accept: 'application/json' } });
            (await res.json()).forEach((p) => (this.details[p.sku] = p));
        } catch {
            /* vẫn hiển thị theo SKU nếu không lấy được chi tiết */
        } finally {
            this.loading = false;
        }
    },
    source() {
        const p = new URLSearchParams(location.search);
        return p.get('utm_source') || document.referrer || '';
    },
}));

/*
 * Bộ chọn thuộc tính ở trang sản phẩm: "Màu sắc" = chấm màu, thuộc tính khác = nút.
 * Sản phẩm có phiên bản (SKU riêng từ Sapo): chọn xong thì mã sản phẩm / tình trạng đổi theo phiên bản, giỏ hàng ghi đúng SKU đó.
 * Không có phiên bản: giỏ ghi SKU chính + lựa chọn của khách (VD "Màu sắc: Hồng") để bộ phận bán hàng biết.
 */
Alpine.data('productOptions', (cfg) => ({
    sel: {},
    init() {
        const start = cfg.variants.find((v) => v.stock !== 'out_of_stock') || cfg.variants[0];
        cfg.attrs.forEach((a) => {
            this.sel[a.name] = start && start.options[a.name] && a.values.includes(start.options[a.name]) ? start.options[a.name] : a.values[0];
        });
    },
    pick(name, value) {
        this.sel[name] = value;
    },
    matches(v, sel) {
        return Object.entries(v.options || {}).every(([k, val]) => !(k in sel) || sel[k] === val);
    },
    get variant() {
        return cfg.variants.length ? cfg.variants.find((v) => this.matches(v, this.sel)) || null : null;
    },
    /** Lựa chọn này có phiên bản còn hàng không (không có phiên bản -> luôn chọn được). */
    available(name, value) {
        if (!cfg.variants.length || !cfg.variants.some((v) => name in (v.options || {}))) return true;
        const v = cfg.variants.find((x) => this.matches(x, { ...this.sel, [name]: value }));
        return !!v && v.stock !== 'out_of_stock';
    },
    get currentSku() {
        return this.variant ? this.variant.sku : cfg.sku;
    },
    /** Ghi kèm vào giỏ khi không có SKU riêng cho lựa chọn (hoặc thuộc tính không thuộc phiên bản). */
    get optsLabel() {
        const covered = this.variant ? Object.keys(this.variant.options || {}) : [];
        return Object.entries(this.sel).filter(([k]) => !covered.includes(k)).map(([k, v]) => `${k}: ${v}`).join(' · ');
    },
    get stockKey() {
        return this.variant ? this.variant.stock || cfg.stock : cfg.stock;
    },
    get stockText() {
        return cfg.stocks[this.stockKey] || cfg.stocks.in_stock;
    },
    get stockClass() {
        return { in_stock: 'text-green-600', out_of_stock: 'text-red-600', preorder: 'text-amber-600' }[this.stockKey] || 'text-neutral-700';
    },
    addToCart(qty) {
        Alpine.store('quote').add(this.currentSku, qty, this.optsLabel);
    },
}));

/*
 * Băng chuyền trượt ngang (tin tức...): dùng cuộn gốc của trình duyệt + scroll-snap,
 * nút trái/phải cuộn đúng 1 "trang", chấm tròn theo số trang.
 * Tùy chọn: x-data="carousel({ autoplay: 4000 })" -> tự chuyển trang mỗi 4 giây khi có
 * nhiều hơn 1 trang; tạm dừng khi rê chuột, chạm hoặc dùng bàn phím trong khối.
 */
Alpine.data('carousel', (opts = {}) => ({
    page: 0,
    pages: 1,
    arrowTop: 0, // nút trái/phải đặt giữa chiều cao ảnh
    paused: false,
    timer: null,
    init() {
        this.measure();
        window.addEventListener('resize', () => this.measure());

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (opts.autoplay && !reduceMotion) {
            const pause = () => (this.paused = true);
            const resume = () => (this.paused = false);
            this.$el.addEventListener('mouseenter', pause);
            this.$el.addEventListener('mouseleave', resume);
            this.$el.addEventListener('focusin', pause);
            this.$el.addEventListener('focusout', resume);
            // Trên điện thoại: chạm vào thì dừng, 6 giây sau tự chạy tiếp.
            this.$el.addEventListener('touchstart', () => {
                pause();
                clearTimeout(this._resume);
                this._resume = setTimeout(resume, 6000);
            }, { passive: true });
            this.timer = setInterval(() => {
                if (!this.paused && this.pages > 1 && !document.hidden) this.go(this.page + 1);
            }, opts.autoplay);
        }
    },
    destroy() {
        clearInterval(this.timer);
        clearTimeout(this._resume);
    },
    gap() {
        return parseFloat(getComputedStyle(this.$refs.track).columnGap) || 0;
    },
    measure() {
        const t = this.$refs.track;
        // Làm tròn lên: trang cuối chưa đầy vẫn tính là 1 trang (trừ sai số vài px).
        this.pages = Math.max(1, Math.ceil((t.scrollWidth + this.gap()) / (t.clientWidth + this.gap()) - 0.02));
        this.arrowTop = (t.querySelector('a')?.offsetHeight || 0) / 2;
        this.onScroll();
    },
    onScroll() {
        const t = this.$refs.track;
        const max = t.scrollWidth - t.clientWidth;
        // Đã chạm cuối hàng -> trang cuối; còn lại tính theo bề rộng 1 trang.
        this.page = t.scrollLeft >= max - 2
            ? this.pages - 1
            : Math.min(this.pages - 1, Math.round(t.scrollLeft / (t.clientWidth + this.gap())));
    },
    go(p) {
        const t = this.$refs.track;
        const target = (p + this.pages) % this.pages;
        t.scrollTo({ left: target * (t.clientWidth + this.gap()), behavior: 'smooth' });
    },
}));

/*
 * Đếm ngược Flash Sale. `end` = thời điểm kết thúc (ISO 8601 từ máy chủ).
 * Có ngày thì hiện "N ngày"; hết giờ thì done = true (khối Flash Sale tự ẩn).
 */
Alpine.data('countdown', (end, cycle = 0) => ({
    end: new Date(end).getTime(),
    cycle: Number(cycle) || 0, // > 0: chương trình tự lặp – hết giờ thì đếm lại chu kỳ mới (giây)
    d: 0,
    h: '00',
    m: '00',
    s: '00',
    done: false,
    timer: null,
    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
    },
    destroy() {
        clearInterval(this.timer);
    },
    tick() {
        let ms = this.end - Date.now();
        // Tự lặp: đã qua mốc kết thúc thì dời sang chu kỳ kế tiếp và đếm tiếp (không ẩn khối Flash Sale).
        if (this.cycle > 0) {
            while (ms <= 0) {
                this.end += this.cycle * 1000;
                ms = this.end - Date.now();
            }
        }
        const left = Math.max(0, Math.floor(ms / 1000));
        const pad = (n) => String(n).padStart(2, '0');
        this.d = Math.floor(left / 86400);
        this.h = pad(Math.floor((left % 86400) / 3600));
        this.m = pad(Math.floor((left % 3600) / 60));
        this.s = pad(left % 60);
        // Chỉ coi là hết giờ khi đã THẬT SỰ qua mốc (không dựa vào số giây đã làm tròn) và không tự lặp.
        if (ms <= 0 && !this.cycle) {
            this.done = true;
            clearInterval(this.timer);
        }
    },
}));

/*
 * Gợi ý tức thì ở ô tìm kiếm header: gõ từ 2 ký tự -> hiện sản phẩm (ảnh, giá) + danh mục khớp.
 * Chờ 250ms sau lần gõ cuối mới gọi máy chủ; yêu cầu cũ bị hủy khi gõ tiếp; có cache theo từ khóa.
 * Bàn phím: ↑/↓ chọn, Enter mở mục đang chọn (không chọn thì tìm như bình thường), Esc đóng.
 */
const foldChar = (c) => c.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[đĐ]/g, 'd').toLowerCase().charAt(0) || c;
const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

Alpine.data('searchSuggest', (url, initial = '') => ({
    q: initial,
    open: false,
    loading: false,
    active: -1,
    products: [],
    categories: [],
    total: 0,
    cache: {},
    timer: null,
    ctrl: null,
    get items() {
        return [...this.products, ...this.categories];
    },
    get ready() {
        return this.q.trim().length >= 2;
    },
    onInput() {
        clearTimeout(this.timer);
        this.active = -1;
        if (!this.ready) {
            this.open = false;
            return;
        }
        this.timer = setTimeout(() => this.fetch(), 250);
    },
    async fetch() {
        const term = this.q.trim();
        if (this.cache[term]) {
            return this.show(this.cache[term]);
        }
        this.ctrl?.abort();
        this.ctrl = new AbortController();
        this.loading = true;
        this.open = true;
        try {
            const res = await fetch(`${url}?q=${encodeURIComponent(term)}`, { signal: this.ctrl.signal, headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();
            this.cache[term] = data;
            if (term === this.q.trim()) this.show(data);
        } catch (e) {
            if (e.name !== 'AbortError') this.loading = false;
        }
    },
    show(data) {
        this.products = data.products;
        this.categories = data.categories;
        this.total = data.total;
        this.loading = false;
        this.open = this.ready;
    },
    move(step) {
        if (!this.open || !this.items.length) return;
        this.active = (this.active + step + this.items.length) % this.items.length;
    },
    submit(e) {
        if (this.open && this.active >= 0 && this.items[this.active]) {
            e.preventDefault();
            window.location = this.items[this.active].url;
        }
    },
    close() {
        this.open = false;
        this.active = -1;
    },
    /** Tô đậm phần khớp từ khóa (không phân biệt dấu: gõ "but bi" vẫn tô "Bút bi"). */
    highlight(text) {
        const folded = Array.from(text).map(foldChar).join('');
        const chars = Array.from(text);
        const mark = new Array(chars.length).fill(false);
        this.q.trim().split(/\s+/).map((w) => Array.from(w).map(foldChar).join('')).filter(Boolean).forEach((w) => {
            // Chỉ tô ở đầu một từ ("bi" tô trong "Bút bi", không tô giữa "WBI-01").
            let i = folded.indexOf(w);
            while (i !== -1) {
                if (i === 0 || !/[\p{L}\p{N}]/u.test(folded[i - 1])) {
                    for (let k = i; k < i + w.length; k++) mark[k] = true;
                }
                i = folded.indexOf(w, i + 1);
            }
        });
        let html = '';
        chars.forEach((c, i) => {
            if (mark[i] && !mark[i - 1]) html += '<mark class="bg-transparent font-bold text-primary-600">';
            html += escapeHtml(c);
            if (mark[i] && !mark[i + 1]) html += '</mark>';
        });
        return html;
    },
}));

window.Alpine = Alpine;
Alpine.start();
