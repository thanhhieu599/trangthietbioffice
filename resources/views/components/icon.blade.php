{{-- Bộ icon nét (inline SVG, không tải thư viện ngoài). Dùng: <x-icon name="search" class="h-5 w-5" /> --}}
@props(['name'])
<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
@switch($name)
    @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>@break
    @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
    @case('close')<path d="M6 6l12 12M18 6 6 18"/>@break
    @case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
    @case('chevron-right')<path d="m9 6 6 6-6 6"/>@break
    @case('chevron-left')<path d="m15 6-6 6 6 6"/>@break
    @case('headset')<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="4" height="6" rx="1.5"/><rect x="17" y="14" width="4" height="6" rx="1.5"/>@break
    @case('cart')<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.6 12.2a1.5 1.5 0 0 0 1.5 1.2h8.7a1.5 1.5 0 0 0 1.5-1.1L21 8H6"/>@break
    @case('clipboard')<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/>@break
    @case('plus-circle')<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>@break
    @case('check-circle')<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>@break
    @case('truck')<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>@break
    @case('receipt')<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>@break
    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>@break
    @case('shield')<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>@break
    @case('phone')<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>@break
    @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>@break
    @case('pin')<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>@break
    @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
    @case('grid')<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>@break
    @case('percent')<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>@break
    @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
    @case('building')<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M16 9h2a2 2 0 0 1 2 2v10M3 21h18M8 7h4M8 11h4M8 15h4"/>@break
    @case('facebook')<path d="M15 3h-2.5A4.5 4.5 0 0 0 8 7.5V10H6v4h2v7h4v-7h3l1-4h-4V7.5a.5.5 0 0 1 .5-.5H15z"/>@break
    @case('youtube')<rect x="2.5" y="5" width="19" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>@break
    @case('instagram')<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>@break
    @case('tiktok')<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5M14 3c.6 2.6 2.4 4.4 5 4.8"/>@break
    @case('image')<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>@break
@endswitch
</svg>
