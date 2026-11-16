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

window.Alpine = Alpine;
Alpine.start();
