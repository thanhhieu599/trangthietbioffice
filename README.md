# D&T Office – Website văn phòng phẩm B2B

Website chuyên ngành văn phòng phẩm & vật tư tiêu hao cho doanh nghiệp, nhà máy, thuộc hệ sinh thái
[trangthietbi.vn](https://trangthietbi.vn). Mục tiêu: lên top Google / được AI trích dẫn và tạo yêu cầu báo giá (RFQ).

**Công nghệ:** Laravel 13 · Blade (dựng HTML phía máy chủ, tốt cho SEO) · Tailwind CSS 4 · Alpine.js · Vite.

## Giao diện

- **Màu thương hiệu:** đỏ – đen – trắng, khai báo một chỗ trong `resources/css/app.css` (biến `--color-primary-*`, `--color-ink-*`).
- **Header:** logo · ô tìm kiếm · hotline · giỏ báo giá. Thanh menu chính màu đỏ: Trang chủ, Tin tức, Thương hiệu, Liên hệ.
- **Danh mục sản phẩm:** nút đen bên trái thanh menu đỏ, sổ xuống danh mục cấp 1, rê chuột hiện danh mục con. Ở trang chủ menu luôn mở bên trái banner; trên điện thoại là ngăn kéo trượt.
- **Banner trang chủ:** trình chiếu ảnh tự chuyển 5 giây, có nút trái/phải và chấm chuyển ảnh.
- **Lưới danh mục dạng biểu tượng** giữa banner và các khối sản phẩm: luôn **2 hàng**, mỗi ô gồm ảnh và tên danh mục cấp 1. Vừa 2 hàng (máy tính tối đa 16 ô, điện thoại tối đa 10 ô) thì đứng yên; nhiều hơn thì tự trượt mỗi 4 giây (dừng khi rê chuột / chạm) và có nút trái / phải. Hàng trên là nửa đầu danh sách, hàng dưới là nửa sau. Đã có sẵn biểu tượng mặc định (`.svg`); chép ảnh `.png` cùng tên vào là ảnh mới được ưu tiên.
- **Thương hiệu** (cuối trang chủ): thẻ logo lớn + tên, luôn **2 hàng**. Vừa 2 hàng (máy tính tối đa 12, điện thoại tối đa 6) thì đứng yên; nhiều hơn thì tự trượt mỗi 4 giây và có nút trái / phải. Thương hiệu tự lấy từ cột `brand` của sản phẩm; chưa có logo thì hiện tên dạng chữ lớn.
- **Trang chủ chia khối theo danh mục:** mỗi danh mục bật `show_on_home` là một khối chỉ **1 hàng** (5 sản phẩm trên máy tính, 2 trên điện thoại). Có tối đa 15 sản phẩm, trượt qua lại bằng nút hai bên hoặc vuốt; cuối hàng có ô "Xem thêm N sản phẩm".
- **Thẻ sản phẩm:** ảnh · tên (2 dòng) · giá gốc gạch ngang + % giảm · giá đỏ · nút "Báo giá". Một lớp viền, không lồng khung.

### Quản trị banner trên trình duyệt

1. Đặt mật khẩu trong `.env`: `ADMIN_PASSWORD="mat-khau-manh"` (để trống = tắt khu quản trị), rồi chạy `php artisan config:clear`.
2. Vào **/quan-tri** → đăng nhập → **Banner trang chủ**.
3. Chọn ảnh (1170 × 500 px, JPG/PNG/WebP, tối đa 2 MB), nhập tiêu đề, link khi bấm, thứ tự → **Tải lên banner**. Có xem trước ảnh theo đúng khung trang chủ.
4. Từng banner: sửa tiêu đề/link/thứ tự, thêm ảnh riêng cho điện thoại, bỏ chọn "Hiển thị" để ẩn, hoặc xóa (xóa luôn file ảnh).

Ảnh tải lên lưu ở `public/images/banners/uploads/` (không đưa vào git). Chưa có banner nào tải lên (hoặc ẩn hết) thì trang chủ dùng ảnh trong thư mục `public/images/banners/`. Trang quản trị được chặn Google (`robots.txt` + `noindex`) và giới hạn 5 lần đăng nhập/phút.

### Chèn logo và banner (chép file vào là hiện, không cần sửa code)

| Vị trí | Đặt file vào | Kích thước khuyến nghị |
|---|---|---|
| Logo header (D&T Office) | `public/images/logo.png` | Cao 120 px, nền trong suốt (PNG/SVG) |
| Logo khối giới thiệu ở footer (trangthietbi.vn) | `public/images/logo-footer.png` | Rộng 500 px, nền trong suốt |
| Banner trang chủ | `public/images/banners/` (mỗi ảnh 1 file) | **1170 × 500 px** |
| Logo thương hiệu (khối Thương hiệu, trang /thuong-hieu) | `public/images/brands/{đường-dẫn-thương-hiệu}.png`, ví dụ `thien-long.png`, `double-a.png` | Ngang khoảng 300 × 120 px, nền trong suốt |
| Ảnh từng danh mục ở lưới danh mục trang chủ | `public/images/categories/{đường-dẫn-danh-mục}.png`, ví dụ `bia-ho-so.png` | Vuông 256 × 256 px, nền trong suốt |

- Đuôi file chấp nhận: `.png`, `.svg`, `.webp`, `.jpg`. Chưa có file thì hiện logo dạng chữ như hiện tại.
- Banner xếp theo tên file: đặt tên `01-khuyen-mai.jpg`, `02-giao-dinh-ky.jpg`… Muốn ảnh riêng cho điện thoại (800 × 450 px) thì thêm file cùng tên có đuôi `-mobile`, ví dụ `01-khuyen-mai-mobile.jpg`.
- Xóa 2 file `demo-1.svg`, `demo-2.svg` trong thư mục banners khi đã có ảnh thật.
- Đặt chữ và sản phẩm chính ở giữa ảnh banner vì màn hình hẹp sẽ cắt bớt hai bên.
- Banner nhập trong bảng `banners` (có link riêng cho từng ảnh) được ưu tiên hơn ảnh trong thư mục; bảng trống thì dùng ảnh trong thư mục, bấm vào dẫn tới trang Yêu cầu báo giá.

### Footer & thông tin doanh nghiệp

Footer nền đỏ chữ trắng gồm khối giới thiệu D&T và 4 cột: Liên hệ · Danh mục sản phẩm (tự lấy theo danh mục của web) · Chính sách · Vận chuyển & thanh toán.
Địa chỉ, người liên hệ, nội dung giới thiệu, hình thức thanh toán sửa trong `config/site.php`; hotline, email, mạng xã hội sửa trong `.env`.

- **Trang chính sách** (bảng `pages`, đường dẫn `/trang/{slug}`): nội dung để trống thì trang hiện "đang cập nhật" và gắn `noindex`. Có nội dung mới được đưa vào sitemap.
- **Logo "Đã thông báo Bộ Công Thương"** chỉ hiện khi điền `SITE_BCT_URL` (link hồ sơ trên online.gov.vn của chính tên miền này).

### Tin tức mới

Cuối trang chủ là băng chuyền 12 bài mới nhất (4 bài/màn hình máy tính) với ảnh, tiêu đề, "Đăng bởi … – ngày", đoạn tóm tắt. Tên người đăng lấy từ cột `author` của bài viết (trống = Admin). Ảnh đại diện nên dùng tỷ lệ 16:9 (ví dụ 800 × 450 px).

### Chọn danh mục hiện trên trang chủ

Sửa cột `show_on_home` (1 = hiện) và `sort_order` (thứ tự khối) trong bảng `categories`, hoặc sửa mảng trong `database/seeders/CategorySeeder.php`.

## Đã có trong giai đoạn 1

| Phần | Mô tả |
|---|---|
| Danh mục nhiều cấp | `/danh-muc/{slug}` – lọc theo thương hiệu, phân trang, đoạn nội dung SEO cuối trang |
| Trang sản phẩm | `/san-pham/{slug}` – ảnh, thông số, quy cách, giá hoặc "Liên hệ báo giá" |
| Danh sách báo giá | Khách thêm nhiều mã (lưu ở trình duyệt), gửi 1 yêu cầu tại `/bao-gia` |
| Lưu RFQ | Bảng `quote_requests` + `quote_request_items`, email thông báo cho sales, chống spam (ô bẫy + giới hạn 5 lần/phút) |
| Tin tức | `/tin-tuc/{slug}` – kèm mục Hỏi đáp (FAQ). Link cũ `/kien-thuc` tự chuyển 301 |
| Thương hiệu | `/thuong-hieu` và `/thuong-hieu/{slug}` – tự lấy từ cột `brand` của sản phẩm |
| Liên hệ | `/lien-he` – thông tin công ty, bản đồ (biến `SITE_MAP_EMBED`), form gửi yêu cầu |
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
| `price` | | Giá bán. Để trống = "Liên hệ" |
| `compare_price` | | Giá gốc. Lớn hơn `price` thì hiện giá gạch ngang và % giảm |
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
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force   # chỉ tạo cây danh mục, không có dữ liệu demo
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
