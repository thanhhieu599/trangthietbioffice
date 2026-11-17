{{-- Ô đếm ngược. Dùng bên trong x-data="countdown('...')". $dark: nền tối (trên banner đỏ) --}}
@php($box = ($big ?? false) ? 'h-11 min-w-11 text-xl' : 'h-8 min-w-8 text-[15px]')
<span class="inline-flex items-center gap-1 font-bold tabular-nums" role="timer" aria-live="off"
      :aria-label="`Còn ${d > 0 ? d + ' ngày ' : ''}${h} giờ ${m} phút ${s} giây`">
    <template x-if="d > 0">
        <span class="mr-1 inline-flex {{ $box }} items-center justify-center rounded-md bg-ink-900 px-1.5 text-white shadow-sm"><span x-text="d"></span>&nbsp;<span class="text-[11px] font-semibold">ngày</span></span>
    </template>
    <span class="inline-flex {{ $box }} items-center justify-center rounded-md bg-ink-900 px-1 text-white shadow-sm" x-text="h">00</span>
    <span class="{{ ($dark ?? false) ? 'text-white' : 'text-ink-900' }}">:</span>
    <span class="inline-flex {{ $box }} items-center justify-center rounded-md bg-ink-900 px-1 text-white shadow-sm" x-text="m">00</span>
    <span class="{{ ($dark ?? false) ? 'text-white' : 'text-ink-900' }}">:</span>
    <span class="inline-flex {{ $box }} items-center justify-center rounded-md bg-primary-600 px-1 text-white shadow-sm" x-text="s">00</span>
</span>
