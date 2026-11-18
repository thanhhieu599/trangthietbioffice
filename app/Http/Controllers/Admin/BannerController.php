<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\SiteImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Quản lý banner trang chủ: tải ảnh lên, đặt link, thứ tự, ẩn/hiện, xóa.
 * Ảnh lưu trong public/images/banners/uploads (chạy được trên hosting thường, không cần storage:link).
 */
class BannerController extends Controller
{
    private const DIR = 'images/banners/uploads';

    public function index(): View
    {
        return view('admin.banners', [
            'banners' => Banner::orderBy('sort_order')->orderBy('id')->get()->groupBy('position'),
            'folderBanners' => SiteImage::banners(),
        ]);
    }

    /** Ảnh: kiểm tra cả nội dung (mimes) lẫn đuôi tên file (extensions) – không lưu được file .php giả ảnh. */
    private const IMAGE = ['file', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'];

    /** Link chỉ nhận đường dẫn trong website (/...) hoặc http(s):// – chặn javascript:, data:... */
    private const LINK = ['nullable', 'string', 'max:500', 'regex:#^(/(?!/)|https?://)#i'];

    public function store(Request $request): RedirectResponse
    {
        $position = in_array($request->input('position'), array_keys(Banner::allPositions()), true) ? $request->input('position') : 'slider';
        $minWidth = Banner::allPositions()[$position]['minWidth'];

        $data = $request->validate([
            'position' => ['required', Rule::in(array_keys(Banner::allPositions()))],
            'image' => ['required', ...self::IMAGE, 'dimensions:min_width='.$minWidth],
            'image_mobile' => ['nullable', ...self::IMAGE],
            'title' => ['nullable', 'string', 'max:255'],
            'link' => self::LINK,
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], $this->messages() + ['image.dimensions' => "Ảnh quá nhỏ: chiều ngang tối thiểu {$minWidth} px (khuyến nghị ".Banner::allPositions()[$position]['size'].' px).']);

        Banner::create([
            'position' => $data['position'],
            'image' => $this->save($request->file('image')),
            'image_mobile' => $request->hasFile('image_mobile') ? $this->save($request->file('image_mobile')) : null,
            'title' => $data['title'] ?? null,
            'link' => $data['link'] ?? null,
            'sort_order' => $data['sort_order'] ?? ((int) Banner::where('position', $data['position'])->max('sort_order') + 1),
            'is_active' => true,
        ]);

        return back()->with('ok', 'Đã thêm '.mb_strtolower(Banner::allPositions()[$data['position']]['label']).'.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'position' => ['required', Rule::in(array_keys(Banner::allPositions()))],
            'title' => ['nullable', 'string', 'max:255'],
            'link' => self::LINK,
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', ...self::IMAGE],
            'image_mobile' => ['nullable', ...self::IMAGE],
        ], $this->messages());

        foreach (['image', 'image_mobile'] as $field) {
            if ($request->hasFile($field)) {
                $this->delete($banner->{$field});
                $data[$field] = $this->save($request->file($field));
            }
        }
        $data['is_active'] = $request->boolean('is_active');

        $banner->update($data);

        return back()->with('ok', 'Đã lưu banner.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->delete($banner->image);
        $this->delete($banner->image_mobile);
        $banner->delete();

        return back()->with('ok', 'Đã xóa banner.');
    }

    private function save(UploadedFile $file): string
    {
        // Đuôi file theo NỘI DUNG ảnh (không theo tên file trình duyệt gửi) -> không thể lưu file .php vào public/.
        $ext = match ($file->extension()) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            default => throw new \InvalidArgumentException('Định dạng ảnh không hợp lệ.'),
        };
        $name = now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.'.$ext;
        $file->move(public_path(self::DIR), $name);

        return '/'.self::DIR.'/'.$name;
    }

    /** Chỉ xóa file nằm trong thư mục tải lên của banner. */
    private function delete(?string $path): void
    {
        if ($path && str_starts_with($path, '/'.self::DIR.'/')) {
            $full = public_path(ltrim($path, '/'));
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    private function messages(): array
    {
        return [
            'image.required' => 'Vui lòng chọn ảnh banner.',
            'image.mimes' => 'Ảnh phải là JPG, PNG hoặc WebP.',
            'image_mobile.mimes' => 'Ảnh điện thoại phải là JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh tối đa 2 MB – hãy nén ảnh (ví dụ tinypng.com) trước khi tải lên.',
            'image_mobile.max' => 'Ảnh điện thoại tối đa 2 MB.',
            'image.uploaded' => 'Ảnh chưa tải lên được (thường do ảnh lớn hơn 2 MB) – hãy nén ảnh rồi thử lại.',
            'image_mobile.uploaded' => 'Ảnh điện thoại chưa tải lên được (thường do lớn hơn 2 MB).',
            'image.dimensions' => 'Ảnh quá nhỏ so với vị trí đã chọn.',
            'image.extensions' => 'Tên file ảnh phải có đuôi .jpg, .png hoặc .webp.',
            'image_mobile.extensions' => 'Tên file ảnh điện thoại phải có đuôi .jpg, .png hoặc .webp.',
            'link.regex' => 'Link phải bắt đầu bằng "/" (trang trong website) hoặc "https://".',
            'position.in' => 'Vị trí banner không hợp lệ.',
        ];
    }
}
