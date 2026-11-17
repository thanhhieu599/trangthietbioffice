{{-- Chữ "FLASH SALE" có tia sét. $size: 'md' | 'lg' --}}
@php($lg = ($size ?? 'md') === 'lg')
<span class="inline-flex items-center gap-1 font-black italic tracking-tight select-none {{ $lg ? 'text-4xl md:text-5xl' : 'text-[22px] md:text-2xl' }}">
    <span class="bg-gradient-to-r from-primary-600 to-[#ff5a1f] bg-clip-text text-transparent {{ ($light ?? false) ? '!text-white !bg-none' : '' }}">FLASH</span>
    <svg class="{{ $lg ? 'h-10 w-7 md:h-12 md:w-8' : 'h-7 w-5' }} -mx-0.5 shrink-0 drop-shadow-[0_1px_2px_rgba(0,0,0,.2)]" viewBox="0 0 20 30" aria-hidden="true">
        <path d="M12.5 0 1 17h7l-2.5 13L19 11h-7.5L12.5 0z" fill="#ffd400" stroke="#f59e0b" stroke-width=".8" stroke-linejoin="round"/>
    </svg>
    <span class="bg-gradient-to-r from-[#ff5a1f] to-primary-600 bg-clip-text text-transparent {{ ($light ?? false) ? '!text-white !bg-none' : '' }}">SALE</span>
</span>
