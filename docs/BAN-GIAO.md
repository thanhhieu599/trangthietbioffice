# Bàn giao dự án D&T Office (website văn phòng phẩm B2B)

Nhật ký chi tiết từng phần việc: [`nhat-ky-du-an.csv`](nhat-ky-du-an.csv) (mở bằng Excel / Google Sheets).

## Tóm tắt

- **Mục tiêu:** website chuyên văn phòng phẩm & vật tư tiêu hao cho doanh nghiệp, nhà máy (FDI), thuộc hệ sinh thái trangthietbi.vn. Kênh chính: SEO + được Google/AI trích dẫn + tạo yêu cầu báo giá (RFQ).
- **Định vị:** cung cấp VPP theo hợp đồng – báo giá theo danh mục, giao định kỳ, công nợ, hóa đơn VAT. Không cạnh tranh giá lẻ.
- **Công nghệ:** Laravel 13 · Blade · Tailwind CSS 4 · Alpine.js · Vite. Chạy trên hosting thường (PHP 8.3+, MySQL).
- **Repo:** https://github.com/thanhhieu599/trangthietbioffice (nhánh `main`).
- **Giao diện:** đỏ – đen – trắng như trangthietbi.vn (màu đỏ `#d71920`, sửa trong `resources/css/app.css`).

## Đã có

Trang chủ (theo thứ tự từ trên xuống): header (logo, tìm kiếm, hotline, giỏ báo giá) → thanh menu đỏ (Trang chủ, Tin tức, Thương hiệu, Liên hệ) + menu Danh mục sổ xuống → banner trình chiếu → lưới danh mục 2 hàng (tự trượt khi nhiều) → khối sản phẩm theo từng danh mục (1 hàng, trượt ngang) → cam kết dịch vụ → thương hiệu (2 hàng thẻ logo, tự trượt) → tin tức mới (băng chuyền) → footer đỏ.

Các trang khác: danh mục, sản phẩm, tìm kiếm, thương hiệu, tin tức, liên hệ, yêu cầu báo giá, trang chính sách, sitemap.xml, robots.txt, khu quản trị banner `/quan-tri`.

## Quy ước quan trọng khi làm tiếp

- Ảnh "chép vào là hiện": `public/images/logo.*`, `logo-footer.*`, `banners/`, `categories/{slug}.*`, `brands/{slug}.*`.
- Thông tin doanh nghiệp: `config/site.php` + `.env`. Danh mục mặc định: `database/seeders/CategorySeeder.php`.
- Băng chuyền dùng chung: `Alpine.data('carousel')` trong `resources/js/app.js` (tùy chọn `autoplay`).
- Cache menu lưu dạng mảng (Laravel 13 chặn unserialize model Eloquent từ cache).
- Không tự soạn văn bản pháp lý (trang chính sách) và không tự vẽ logo các hãng – chờ D&T cung cấp.
- Không gắn logo "Đã thông báo Bộ Công Thương" khi tên miền chưa đăng ký.

## Việc còn lại

Xem các dòng "Việc còn lại" trong file CSV. Ưu tiên: logo + ảnh banner thật → file sản phẩm từ Sapo → trang quản trị sản phẩm/danh mục/tin tức/RFQ → nội dung bài viết SEO → đưa lên hosting.

## Câu mở đầu để dán vào tài khoản / cuộc trò chuyện mới

```
Tôi đang làm website văn phòng phẩm B2B "D&T Office" (Laravel 13 + Blade + Tailwind 4 + Alpine.js),
repo GitHub: https://github.com/thanhhieu599/trangthietbioffice (nhánh main).
Hãy đọc file docs/BAN-GIAO.md, docs/nhat-ky-du-an.csv và README.md trong repo để nắm toàn bộ
những gì đã làm, các quy ước và việc còn lại, rồi làm tiếp với tôi.
Việc tiếp theo tôi muốn làm: <ghi việc cần làm>.
```
