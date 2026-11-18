{{--
    Trình soạn thảo nội dung (3 hàng công cụ). Dùng bên trong x-data="adminForm(...)".
    Biến: $name (tên trường gửi lên), $label, $hint (tùy chọn)
--}}
@php
    $ico = [
        'html' => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4"/><path d="m10 11-2 2.5 2 2.5M14 11l2 2.5-2 2.5"/>',
        'ol' => '<path d="M10 6h11M10 12h11M10 18h11"/><path d="M4 4.5h1.2V9M3.8 9h2.6M3.8 14.2c.3-.6 2.6-.9 2.6.6 0 1-2.6 2-2.6 3.2h2.6"/>',
        'ul' => '<path d="M9 6h12M9 12h12M9 18h12"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/>',
        'outdent' => '<path d="M21 5H3M21 19H3M21 12h-9"/><path d="m7 9-3 3 3 3"/>',
        'indent' => '<path d="M21 5H3M21 19H3M21 12h-9"/><path d="m4 9 3 3-3 3"/>',
        'left' => '<path d="M3 5h18M3 10h12M3 15h18M3 20h12"/>',
        'center' => '<path d="M3 5h18M6 10h12M3 15h18M6 20h12"/>',
        'right' => '<path d="M3 5h18M9 10h12M3 15h18M9 20h12"/>',
        'full' => '<path d="M3 5h18M3 10h18M3 15h18M3 20h18"/>',
        'table' => '<rect x="3" y="4" width="18" height="16" rx="1.5"/><path d="M3 9.5h18M3 15h18M9 4v16M15 4v16"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="1.5"/><circle cx="8.5" cy="9.5" r="1.6"/><path d="m21 16-5-5-10 9"/>',
        'brush' => '<path d="M14.5 4.5 19.5 9.5 12 17l-5-5z"/><path d="M7 12c-2.5.3-4 2-4 4.3 0 1-.5 2-1.5 2.7 2.8.8 6.3.3 7.7-1.5.9-1.2 1-2.4.8-3.5"/>',
        'clear' => '<path d="M5 6V4.5h12V6M11 4.5 7.5 19M6 19h4"/><path d="m14 14 6 6M20 14l-6 6"/>',
        'code' => '<rect x="3" y="4" width="18" height="16" rx="1.5"/><path d="m10 10-2.5 2.5L10 15M14 10l2.5 2.5L14 15"/>',
        'link' => '<path d="M10 14a4 4 0 0 0 5.66 0l3.3-3.3a4 4 0 0 0-5.66-5.66l-1 1"/><path d="M14 10a4 4 0 0 0-5.66 0l-3.3 3.3a4 4 0 0 0 5.66 5.66l1-1"/>',
        'unlink' => '<path d="M10 14a4 4 0 0 0 5.66 0l3.3-3.3a4 4 0 0 0-5.66-5.66l-1 1"/><path d="M14 10a4 4 0 0 0-5.66 0l-3.3 3.3a4 4 0 0 0 5.66 5.66l1-1"/><path d="M4 4l16 16"/>',
        'flag' => '<path d="M5 21V4"/><path d="M5 4h12l-2.5 4.5L17 13H5"/>',
        'quote' => '<path d="M5 18c2.5-1 4-3.2 4-6V7H4.5v5H9M15 18c2.5-1 4-3.2 4-6V7h-4.5v5H19"/>',
        'expand' => '<path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/>',
        'shrink' => '<path d="M9 4v5H4M20 9h-5V4M15 20v-5h5M4 15h5v5"/>',
        'lineheight' => '<path d="M11 6h10M11 12h10M11 18h10"/><path d="m3.5 8 2.5-2.5L8.5 8M3.5 16 6 18.5 8.5 16M6 5.5v13"/>',
        'caret' =>'<path d="m7 10 5 5 5-5" fill="currentColor" stroke="none"/>',
    ];
    $i = fn ($n, $c = 'h-[18px] w-[18px]') => '<svg class="'.$c.'" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">'.$ico[$n].'</svg>';
    $b = 'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-1.5 text-slate-700 transition hover:bg-slate-200/70 hover:text-adm-ink';
    $on = 'bg-slate-200 text-adm-ink';
    $sep = '<span class="mx-1 h-5 w-px self-center bg-slate-300"></span>';
    $dd = 'inline-flex h-8 items-center gap-1.5 rounded-md border border-transparent px-2 text-sm text-slate-700 transition hover:border-slate-300 hover:bg-white';
    $menu = 'absolute left-0 top-full z-30 mt-1 min-w-full overflow-hidden rounded-lg border border-adm-line bg-white py-1 text-sm shadow-xl shadow-slate-900/10';
    $item = 'block w-full px-3 py-1.5 text-left whitespace-nowrap hover:bg-slate-100';
@endphp
<div>
    <span class="mb-1.5 block text-sm font-medium">{{ $label }}</span>

    <div :class="fullscreen ? 'fixed inset-0 z-[60] flex flex-col bg-white' : 'rounded-lg border border-slate-300 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)] focus-within:border-primary-600 focus-within:ring-4 focus-within:ring-primary-600/10'">
        {{-- ===== Thanh công cụ ===== --}}
        <div class="relative z-20 space-y-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5" :class="fullscreen ? '' : 'rounded-t-lg'"
             @mousedown="$event.target.closest('button') && $event.preventDefault()">

            {{-- Hàng 1 --}}
            <div class="flex flex-wrap items-center gap-0.5">
                <button type="button" class="{{ $b }} gap-1.5 px-2 text-sm" :class="htmlMode && '{{ $on }}'" @click="toggleHtml()" title="Xem / sửa mã HTML">
                    {!! $i('html') !!} <span x-text="htmlMode ? 'Soạn thảo' : 'Mã HTML'"></span>
                </button>
                {!! $sep !!}
                <template x-if="!htmlMode"><div class="contents">
                    <div class="relative" x-data="{ o: false }" @click.outside="o = false">
                        <button type="button" class="{{ $dd }} w-[6.5rem] justify-between" @click="o = !o" title="Phông chữ">Phông {!! $i('caret', 'h-4 w-4 text-slate-500') !!}</button>
                        <div x-show="o" x-cloak class="{{ $menu }} w-52">
                            <template x-for="[name, fam] in fonts" :key="name">
                                <button type="button" class="{{ $item }}" :style="fam && `font-family:${fam}`" @click="setFont(fam); o = false" x-text="name"></button>
                            </template>
                        </div>
                    </div>
                    {!! $sep !!}
                    <div class="relative" x-data="{ o: false }" @click.outside="o = false">
                        <button type="button" class="{{ $dd }} w-[7.25rem] justify-between" @click="o = !o" title="Định dạng đoạn"><span x-text="blockLabel"></span> {!! $i('caret', 'h-4 w-4 text-slate-500') !!}</button>
                        <div x-show="o" x-cloak class="{{ $menu }} w-56">
                            <button type="button" class="{{ $item }}" @click="block('p'); o = false">Đoạn văn</button>
                            <button type="button" class="{{ $item }} text-xl font-semibold" @click="block('h2'); o = false">Tiêu đề 2 (H2)</button>
                            <button type="button" class="{{ $item }} text-lg font-semibold" @click="block('h3'); o = false">Tiêu đề 3 (H3)</button>
                            <button type="button" class="{{ $item }} font-semibold" @click="block('h4'); o = false">Tiêu đề 4 (H4)</button>
                            <button type="button" class="{{ $item }} border-l-4 border-slate-300 italic" @click="block('blockquote'); o = false">Trích dẫn</button>
                            <button type="button" class="{{ $item }} font-mono text-xs" @click="block('pre'); o = false">Mã (pre)</button>
                        </div>
                    </div>
                    {!! $sep !!}
                    <div class="relative" x-data="{ o: false }" @click.outside="o = false">
                        <button type="button" class="{{ $dd }} w-[5.75rem] justify-between" @click="o = !o" title="Cỡ chữ">Cỡ chữ {!! $i('caret', 'h-4 w-4 text-slate-500') !!}</button>
                        <div x-show="o" x-cloak class="{{ $menu }} max-h-72 w-28 overflow-y-auto">
                            <button type="button" class="{{ $item }} text-slate-500" @click="setSize(null); o = false">Mặc định</button>
                            <template x-for="s in sizes" :key="s">
                                <button type="button" class="{{ $item }}" @click="setSize(s); o = false" x-text="s + 'px'"></button>
                            </template>
                        </div>
                    </div>
                    {!! $sep !!}
                    @foreach (['fore' => ['Màu chữ', 'foreColor'], 'back' => ['Màu nền chữ', 'backColor']] as $kind => [$title, $var])
                        <div class="relative flex items-center" x-data="{ o: false }" @click.outside="o = false">
                            <button type="button" class="{{ $b }} rounded-r-none pr-0.5" @click="setColor('{{ $kind }}', {{ $var }})" title="{{ $title }}">
                                @if ($kind === 'fore')
                                    <span class="flex flex-col items-center leading-none"><span class="text-[15px] font-bold">A</span><span class="mt-0.5 h-[3px] w-4 rounded-full" :style="`background:${foreColor}`"></span></span>
                                @else
                                    <span class="flex flex-col items-center leading-none"><span class="flex h-[15px] w-4 items-center justify-center rounded-[3px] bg-slate-700 text-[11px] font-bold text-white">A</span><span class="mt-0.5 h-[3px] w-4 rounded-full ring-1 ring-black/10" :style="`background:${backColor}`"></span></span>
                                @endif
                            </button>
                            <button type="button" class="{{ $b }} min-w-0 rounded-l-none px-0.5" @click="o = !o" title="Chọn {{ mb_strtolower($title) }}">{!! $i('caret', 'h-4 w-4 text-slate-500') !!}</button>
                            <div x-show="o" x-cloak class="{{ $menu }} w-[15.5rem] p-2">
                                <p class="mb-1.5 text-xs font-medium text-slate-500">{{ $title }}</p>
                                <div class="grid grid-cols-8 gap-1">
                                    <template x-for="c in palette" :key="c">
                                        <button type="button" class="h-6 w-6 rounded border border-black/10 transition hover:scale-110" :style="`background:${c}`" :title="c" @click="setColor('{{ $kind }}', c); o = false"></button>
                                    </template>
                                </div>
                                <button type="button" class="mt-2 w-full rounded-md border border-adm-line px-2 py-1 text-xs hover:bg-slate-50" @click="setColor('{{ $kind }}', ''); o = false">Bỏ màu</button>
                            </div>
                        </div>
                    @endforeach
                </div></template>
            </div>

            {{-- Hàng 2 & 3 --}}
            <template x-if="!htmlMode"><div class="space-y-1">
                <div class="flex flex-wrap items-center gap-0.5">
                    <button type="button" class="{{ $b }} text-[15px] font-bold" :class="fmt.bold && '{{ $on }}'" @click="cmd('bold')" title="In đậm (Ctrl+B)">B</button>
                    <button type="button" class="{{ $b }} font-serif text-[15px] italic" :class="fmt.italic && '{{ $on }}'" @click="cmd('italic')" title="In nghiêng (Ctrl+I)">I</button>
                    <button type="button" class="{{ $b }} text-[15px] underline underline-offset-2" :class="fmt.underline && '{{ $on }}'" @click="cmd('underline')" title="Gạch chân (Ctrl+U)">U</button>
                    {!! $sep !!}
                    <button type="button" class="{{ $b }}" :class="fmt.ol && '{{ $on }}'" @click="cmd('insertOrderedList')" title="Danh sách số">{!! $i('ol') !!}</button>
                    <button type="button" class="{{ $b }}" :class="fmt.ul && '{{ $on }}'" @click="cmd('insertUnorderedList')" title="Danh sách chấm">{!! $i('ul') !!}</button>
                    {!! $sep !!}
                    <button type="button" class="{{ $b }}" @click="cmd('outdent')" title="Giảm thụt lề">{!! $i('outdent') !!}</button>
                    <button type="button" class="{{ $b }}" @click="cmd('indent')" title="Tăng thụt lề">{!! $i('indent') !!}</button>
                    {!! $sep !!}
                    <button type="button" class="{{ $b }}" :class="fmt.left && '{{ $on }}'" @click="align('left')" title="Căn trái">{!! $i('left') !!}</button>
                    <button type="button" class="{{ $b }}" :class="fmt.center && '{{ $on }}'" @click="align('center')" title="Căn giữa">{!! $i('center') !!}</button>
                    <button type="button" class="{{ $b }}" :class="fmt.right && '{{ $on }}'" @click="align('right')" title="Căn phải">{!! $i('right') !!}</button>
                    <button type="button" class="{{ $b }}" :class="fmt.full && '{{ $on }}'" @click="align('full')" title="Căn đều hai bên">{!! $i('full') !!}</button>
                    {!! $sep !!}
                    <div class="relative" x-data="{ o: false, r: 0, c: 0 }" @click.outside="o = false">
                        <button type="button" class="{{ $b }}" @click="o = !o" title="Chèn bảng">{!! $i('table') !!}</button>
                        <div x-show="o" x-cloak class="{{ $menu }} w-auto p-2" @mouseleave="r = 0; c = 0">
                            <div class="grid grid-cols-8 gap-0.5">
                                <template x-for="n in 64" :key="n">
                                    <button type="button" class="h-4 w-4 rounded-sm border"
                                            :class="(Math.ceil(n / 8) <= r && ((n - 1) % 8) + 1 <= c) ? 'border-primary-600 bg-primary-100' : 'border-slate-300 bg-white'"
                                            @mouseenter="r = Math.ceil(n / 8); c = ((n - 1) % 8) + 1"
                                            @click="insertTable(r, c); o = false"></button>
                                </template>
                            </div>
                            <p class="mt-1.5 text-center text-xs text-slate-500" x-text="r ? `${r} hàng × ${c} cột` : 'Chọn số hàng × cột'"></p>
                        </div>
                    </div>
                    <button type="button" class="{{ $b }}" @click="pickImage()" :disabled="uploading" title="Chèn ảnh (tải lên)">
                        <span x-show="!uploading">{!! $i('image') !!}</span>
                        <span x-show="uploading" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-primary-600"></span>
                    </button>
                    <button type="button" class="{{ $b }}" :class="painter && 'bg-amber-100 text-amber-800'" @click="togglePainter()"
                            :title="painter ? 'Đã sao chép định dạng – bôi đen chữ cần áp dụng (bấm lại để hủy)' : 'Sao chép định dạng (đặt con trỏ vào chữ mẫu rồi bấm)'">{!! $i('brush') !!}</button>
                    <button type="button" class="{{ $b }}" @click="clearFormat()" title="Xóa định dạng chữ">{!! $i('clear') !!}</button>
                    {!! $sep !!}
                    <div class="relative" x-data="{ o: false }" @click.outside="o = false">
                        <button type="button" class="{{ $dd }} gap-0.5 px-1.5" @click="o = !o" title="Giãn dòng">{!! $i('lineheight') !!}{!! $i('caret', 'h-4 w-4 text-slate-500') !!}</button>
                        <div x-show="o" x-cloak class="{{ $menu }} w-28">
                            <button type="button" class="{{ $item }} text-slate-500" @click="setLineHeight(''); o = false">Mặc định</button>
                            <template x-for="v in lineHeights" :key="v">
                                <button type="button" class="{{ $item }}" @click="setLineHeight(v); o = false" x-text="v"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-0.5">
                    <button type="button" class="{{ $b }}" @click="embed()" title="Chèn video YouTube / bản đồ Google Maps">{!! $i('code') !!}</button>
                    {!! $sep !!}
                    <button type="button" class="{{ $b }}" @click="link()" title="Chèn link (bôi đen chữ trước)">{!! $i('link') !!}</button>
                    <button type="button" class="{{ $b }}" @click="cmd('unlink')" title="Bỏ link">{!! $i('unlink') !!}</button>
                    <button type="button" class="{{ $b }}" @click="anchor()" title="Gắn neo cho mục (làm mục lục / link nhảy tới mục)">{!! $i('flag') !!}</button>
                    <button type="button" class="{{ $b }}" :class="fmt.block === 'blockquote' && '{{ $on }}'" @click="block(fmt.block === 'blockquote' ? 'p' : 'blockquote')" title="Trích dẫn">{!! $i('quote') !!}</button>
                    {!! $sep !!}
                    <button type="button" class="{{ $b }}" :class="fullscreen && '{{ $on }}'" @click="fullscreen = !fullscreen" :title="fullscreen ? 'Thu nhỏ (Esc)' : 'Toàn màn hình'">
                        <span x-show="!fullscreen">{!! $i('expand') !!}</span><span x-show="fullscreen" x-cloak>{!! $i('shrink') !!}</span>
                    </button>
                </div>
            </div></template>
        </div>

        {{-- ===== Vùng soạn thảo ===== --}}
        <div x-ref="editor" x-show="!htmlMode" contenteditable="true"
             class="prose-content overflow-y-auto px-5 py-4 focus:outline-none"
             :class="[fullscreen ? 'mx-auto w-full max-w-4xl flex-1' : 'h-[420px] min-h-[200px] resize-y', painter ? 'cursor-copy' : '']"
             @input="syncFromEditor()" @keyup="keepSelection()" @mouseup="keepSelection(); painter && applyPainter()"
             @paste="onPaste($event)" @dblclick="editAlt($event)"></div>
        <textarea name="{{ $name }}" x-show="htmlMode" x-model="f.content" spellcheck="false"
                  class="block w-full resize-y bg-slate-950 px-5 py-4 font-mono text-[13px] leading-relaxed text-slate-100 focus:outline-none"
                  :class="fullscreen ? 'flex-1' : 'h-[420px] min-h-[200px]'"></textarea>
        <input type="file" x-ref="imageInput" accept="image/jpeg,image/png,image/webp" class="hidden" @change="uploadImage($event)">

        {{-- ===== Chân khung ===== --}}
        <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-500" :class="fullscreen ? '' : 'rounded-b-lg'">
            <span x-show="painter" x-cloak class="font-medium text-amber-700">Bôi đen đoạn chữ để dán định dạng đã sao chép…</span>
            <span x-show="!painter"><span x-text="wordCount"></span> từ</span>
            <span :class="htmlLength > maxHtml && 'font-semibold text-primary-600'">HTML: <span x-text="htmlLength.toLocaleString('vi-VN')"></span>/<span x-text="maxHtml.toLocaleString('vi-VN')"></span></span>
        </div>
    </div>
</div>
