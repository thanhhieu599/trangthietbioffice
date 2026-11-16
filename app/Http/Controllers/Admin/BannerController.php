<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\SiteImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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
            'banners' => Banner::orderBy('sort_order')->orderBy('id')->get(),
            'folderBanners' => SiteImage::banners(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=800'],
            'image_mobile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], $this->messages());

        Banner::create([
            'image' => $this->save($request->file('image')),
            'image_mobile' => $request->hasFile('image_mobile') ? $this->save($request->file('image_mobile')) : null,
            'title' => $data['title'] ?? null,
            'link' => $data['link'] ?? null,
            'sort_order' => $data['sort_order'] ?? (Banner::max('sort_order') + 1),
            'is_active' => true,
        ]);

        return back()->with('ok', 'Đã thêm banner.');
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'image_mobile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->messages());

        if ($request->hasFile('image_mobile')) {
            $this->delete($banner->image_mobile);
            $data['image_mobile'] = $this->save($request->file('image_mobile'));
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
        $name = now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension());
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
            'image.dimensions' => 'Ảnh quá nhỏ: chiều ngang tối thiểu 800 px (khuyến nghị 1170 × 500 px).',
        ];
    }
}
