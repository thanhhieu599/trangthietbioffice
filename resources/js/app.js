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
    has(sku) {
        return this.items.some((i) => i.sku === sku);
    },
    add(sku, qty = 1) {
        const found = this.items.find((i) => i.sku === sku);
        if (found) found.qty += Number(qty) || 1;
        else this.items.push({ sku, qty: Number(qty) || 1 });
        this.save();
    },
    setQty(sku, qty) {
        const found = this.items.find((i) => i.sku === sku);
        if (found) found.qty = Math.max(1, Number(qty) || 1);
        this.save();
    },
    remove(sku) {
        this.items = this.items.filter((i) => i.sku !== sku);
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

window.Alpine = Alpine;
Alpine.start();
