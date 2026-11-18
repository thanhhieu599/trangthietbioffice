{{--
    Một ô ảnh: xem trước đúng tỉ lệ + chọn ảnh (tự gửi khi chọn) + xóa.
    Biến: $action (URL POST), $url (ảnh hiện tại hoặc null), $label, $size, $ratio, $hint (tùy chọn), $class (độ rộng khung)
--}}
<div class="{{ $class ?? '' }}" x-data="{ preview: null }">
    <p class="mb-1.5 flex items-baseline justify-between gap-2 text-xs">
        <span class="font-semibold text-adm-ink">{{ $label }}</span>
        <span class="text-adm-muted">{{ $size }} px</span>
    </p>
    <form method="post" action="{{ $action }}" enctype="multipart/form-data" x-ref="form">
        @csrf
        <label class="group relative block cursor-pointer overflow-hidden rounded-lg border-2 border-dashed transition {{ $url ? 'border-transparent' : 'border-slate-300 hover:border-primary-500' }} bg-slate-50"
               @style(['aspect-ratio: '.$ratio]) title="Bấm để {{ $url ? 'thay' : 'chọn' }} ảnh">
            <template x-if="preview"><img :src="preview" alt="" class="absolute inset-0 h-full w-full object-cover"></template>
            @if ($url)
                <img x-show="!preview" src="{{ $url }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                <span class="absolute inset-0 flex items-center justify-center bg-slate-900/0 text-xs font-semibold text-white opacity-0 transition group-hover:bg-slate-900/45 group-hover:opacity-100">Thay ảnh</span>
            @else
                <span x-show="!preview" class="absolute inset-0 flex flex-col items-center justify-center gap-1 text-center text-xs text-slate-400 group-hover:text-primary-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V6m0 0-4 4m4-4 4 4M4 18h16"/></svg>
                    Tải ảnh lên
                </span>
            @endif
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only"
                   @change="if ($event.target.files[0]) { preview = URL.createObjectURL($event.target.files[0]); $refs.form.submit() }">
        </label>
    </form>
    <div class="mt-1.5 flex min-h-5 items-center justify-between gap-2 text-xs">
        <span></span>
        @if ($url)
            <form method="post" action="{{ $action }}" onsubmit="return confirm('Xóa ảnh này?')">@csrf
                <input type="hidden" name="remove" value="1">
                <button class="text-primary-600 hover:underline">Xóa</button>
            </form>
        @endif
    </div>
</div>
