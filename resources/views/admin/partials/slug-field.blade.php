{{-- Ô đường dẫn (slug). Dùng trong adminForm. --}}
@php($input = 'adm-input')
<label class="block">
    <span class="mb-1 block text-sm font-medium">Đường dẫn (URL)</span>
    <div class="flex items-center overflow-hidden rounded-lg border border-slate-300 bg-slate-50 shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition focus-within:border-primary-600 focus-within:ring-4 focus-within:ring-primary-600/10">
        <span class="hidden shrink-0 border-r border-slate-200 px-3 py-2 text-sm text-slate-500 sm:inline" x-text="prefix"></span>
        <input type="text" name="slug" x-model="f.slug" @input="slugTouched = true" @blur="f.slug = slugify(f.slug)"
               class="w-full min-w-0 bg-white px-3 py-2 text-sm text-adm-ink focus:outline-none" placeholder="tu-dong-tao-tu-tieu-de">
    </div>
    <span class="mt-0.5 block text-xs text-slate-500">Ngắn gọn, không dấu, chứa từ khóa. Tự tạo từ tiêu đề nếu để trống.</span>
    <span x-show="slugChanged" x-cloak class="mt-1.5 block rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-inset ring-amber-600/20">
        Bạn đang đổi đường dẫn. Khi lưu, link cũ sẽ tự chuyển hướng 301 sang link mới (không mất thứ hạng Google).
    </span>
</label>
