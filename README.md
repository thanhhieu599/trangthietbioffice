# D&T Office – Website văn phòng phẩm B2B

Website chuyên ngành văn phòng phẩm & vật tư tiêu hao cho doanh nghiệp, nhà máy, thuộc hệ sinh thái
[trangthietbi.vn](https://trangthietbi.vn). Mục tiêu: lên top Google / được AI trích dẫn và tạo yêu cầu báo giá (RFQ).

**Công nghệ:** Laravel 13 · Blade (dựng HTML phía máy chủ, tốt cho SEO) · Tailwind CSS 4 · Alpine.js · Vite.

## Đã có trong giai đoạn 1

| Phần | Mô tả |
|---|---|
| Danh mục nhiều cấp | `/danh-muc/{slug}` – lọc theo thương hiệu, phân trang, đoạn nội dung SEO cuối trang |
| Trang sản phẩm | `/san-pham/{slug}` – ảnh, thông số, quy cách, giá hoặc "Liên hệ báo giá" |
| Danh sách báo giá | Khách thêm nhiều mã (lưu ở trình duyệt), gửi 1 yêu cầu tại `/bao-gia` |
| Lưu RFQ | Bảng `quote_requests` + `quote_request_items`, email thông báo cho sales, chống spam (ô bẫy + giới hạn 5 lần/phút) |
| Bài viết kiến thức | `/kien-thuc/{slug}` – kèm mục Hỏi đáp (FAQ) |
| SEO kỹ thuật | Title/description riêng từng trang, canonical, Open Graph, `sitemap.xml` tự động, `robots.txt` (chặn index khi chưa ở production) |
| Dữ liệu có cấu trúc | JSON-LD: Organization, Product, BreadcrumbList, Article, FAQPage |
| Hiệu năng | Font tự lưu (không gọi Google Fonts), ảnh lazy-load, CSS ~6,5 KB gzip |
| Nhập sản phẩm | `php artisan products:import file.csv` – thêm mới / cập nhật theo SKU |

## Chạy trên máy lập trình

Yêu cầu: PHP ≥ 8.3, Composer, Node.js ≥ 20.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run dev        # cửa sổ 1
php artisan serve  # cửa sổ 2 -> http://localhost:8000
```

## Nhập sản phẩm từ Excel

1. Mở file mẫu `docs/mau-nhap-san-pham.csv` bằng Excel, điền dữ liệu (giữ nguyên dòng tiêu đề).
2. **File → Save As → CSV UTF-8 (Comma delimited)**.
3. Chạy thử để kiểm tra, sau đó nhập thật:

```bash
php artisan products:import duong-dan/san-pham.csv --dry-run
php artisan products:import duong-dan/san-pham.csv
```

| Cột | Bắt buộc | Ghi chú |
|---|---|---|
| `sku` | ✔ | Khóa đối chiếu. Giữ nguyên mã như trên Sapo/MISA |
| `name` | ✔ | Loại sp + thương hiệu + quy cách |
| `category` | | Dạng `Giấy in > Giấy A4`, tự tạo danh mục nếu chưa có |
| `brand`, `unit`, `pack_size` | | Thương hiệu, đơn vị tính, quy cách đóng gói |
| `price` | | Để trống = "Liên hệ báo giá" |
| `min_order_qty` | | Số lượng đặt tối thiểu |
| `short_description`, `description` | | Mô tả ngắn (chữ) / mô tả chi tiết (cho phép HTML) |
| `specs` | | `Khổ: A4 \| Định lượng: 80gsm` |
| `images` | | Đường dẫn ảnh, ngăn cách bằng dấu phẩy |
| `is_featured`, `is_active` | | 1/0 |
| `meta_title`, `meta_description` | | Để trống thì hệ thống tự sinh |

Cột nào không có trong file thì giữ nguyên dữ liệu cũ, nên có thể dùng file chỉ gồm `sku,name,price` để cập nhật giá hàng loạt.

## Đưa lên hosting

Hosting cần: PHP 8.3+, MySQL/MariaDB, Composer, nên có SSH. Hỏi nhà cung cấp trước khi mua.

1. Trên máy tính: `npm run build` (tạo thư mục `public/build`, hosting không cần Node.js).
2. Tải mã nguồn (kèm `public/build`) lên hosting, **trỏ document root của tên miền vào thư mục `public/`**.
3. Tạo database MySQL, sửa `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tenmien.vn`, `DB_CONNECTION=mysql` và thông tin DB, `MAIL_*` để gửi email, các biến `SITE_*`.
4. Qua SSH:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

5. Bật SSL, khai báo `https://tenmien.vn/sitemap.xml` trong Google Search Console.

## Giai đoạn tiếp theo

- Trang quản trị (Filament): sản phẩm, danh mục, bài viết, theo dõi RFQ.
- Trang theo khu vực (VPP tại từng KCN) và trang "Danh mục VPP mẫu cho văn phòng 50/100/200 người".
- Ảnh WebP tự động, cache trang toàn phần.
- Đồng bộ RFQ sang Google Sheet / CRM.
