{{-- Ô tìm kiếm có gợi ý tức thì. $mobile = true: bản cho điện thoại (dưới logo). --}}
@php($mobile = $mobile ?? false)
<form action="{{ route('search') }}" method="get" role="search"
      x-data="searchSuggest(@js(route('search.suggest')), @js((string) request('q')))"
      @submit="submit($event)" @click.outside="close()" @keydown.escape="close()"
      class="relative {{ $mobile ? 'flex px-4 pb-3 md:hidden' : 'hidden flex-1 md:flex' }}">
    <label for="{{ $mobile ? 'q-mobile' : 'q' }}" class="sr-only">Tìm sản phẩm</label>
    <input id="{{ $mobile ? 'q-mobile' : 'q' }}" name="q" type="search" autocomplete="off" maxlength="100"
           x-model="q" @input="onInput()" @focus="ready && (products.length || categories.length) && (open = true)"
           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
           role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="{{ $mobile ? 'goi-y-m' : 'goi-y' }}"
           placeholder="{{ $mobile ? 'Bạn tìm sản phẩm gì?' : 'Bạn tìm giấy in, bút, bìa hồ sơ, băng keo…' }}"
           class="min-w-0 flex-1 rounded-l-md border border-r-0 border-neutral-300 {{ $mobile ? 'px-3 py-2' : 'px-4 py-2.5' }} text-sm focus:border-primary-600 focus:outline-none">
    <button type="submit" class="rounded-r-md bg-primary-600 {{ $mobile ? 'px-4' : 'px-5' }} text-white hover:bg-primary-700" aria-label="Tìm kiếm">
        <x-icon name="search" />
    </button>

    {{-- ===== Khung gợi ý ===== --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.150ms id="{{ $mobile ? 'goi-y-m' : 'goi-y' }}" role="listbox"
         class="absolute top-full z-50 mt-1.5 overflow-hidden rounded-lg border border-neutral-200 bg-white text-sm shadow-2xl shadow-ink-900/10 {{ $mobile ? 'inset-x-4' : 'inset-x-0' }}">

        <div x-show="loading && !products.length" class="flex items-center gap-2 px-4 py-4 text-neutral-500">
            <svg class="h-4 w-4 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Đang tìm…
        </div>

        <template x-if="!loading && !products.length && !categories.length">
            <div class="px-4 py-5 text-center text-neutral-500">
                Không tìm thấy sản phẩm cho “<span class="font-semibold text-ink-900" x-text="q.trim()"></span>”.
                <a href="{{ route('contact') }}#gui-tin-nhan" class="mt-1 block font-semibold text-primary-600 hover:underline">Gửi yêu cầu tìm hàng →</a>
            </div>
        </template>

        <template x-if="products.length">
            <div>
                <p class="bg-neutral-50 px-4 py-2 text-[11px] font-semibold tracking-wider text-neutral-500 uppercase">Sản phẩm</p>
                <ul class="max-h-[60vh] overflow-y-auto">
                    <template x-for="(p, i) in products" :key="p.url">
                        <li role="option" :aria-selected="active === i">
                            <a :href="p.url" @mouseenter="active = i"
                               class="flex items-center gap-3 px-4 py-2.5 transition" :class="active === i ? 'bg-primary-50' : ''">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border border-neutral-100 bg-white">
                                    <template x-if="p.image"><img :src="p.image" alt="" class="h-full w-full object-contain" loading="lazy"></template>
                                    <template x-if="!p.image"><x-icon name="image" class="h-5 w-5 text-neutral-300" /></template>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="line-clamp-2 leading-snug text-ink-900" x-html="highlight(p.name)"></span>
                                    <span class="mt-0.5 block text-xs text-neutral-400" x-text="'Mã: ' + p.sku"></span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="block font-bold whitespace-nowrap" :class="p.old_price || p.price !== 'Liên hệ báo giá' ? 'text-primary-600' : 'text-xs text-neutral-500'" x-text="p.price"></span>
                                    <span x-show="p.old_price" class="block text-xs text-neutral-400 line-through" x-text="p.old_price"></span>
                                </span>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>

        <template x-if="categories.length">
            <div class="border-t border-neutral-100">
                <p class="bg-neutral-50 px-4 py-2 text-[11px] font-semibold tracking-wider text-neutral-500 uppercase">Danh mục</p>
                <ul class="flex flex-wrap gap-2 px-4 py-3">
                    <template x-for="(c, j) in categories" :key="c.url">
                        <li role="option" :aria-selected="active === products.length + j">
                            <a :href="c.url" @mouseenter="active = products.length + j" x-html="highlight(c.name)"
                               class="block rounded-full border px-3 py-1 text-[13px] transition"
                               :class="active === products.length + j ? 'border-primary-600 bg-primary-50' : 'border-neutral-200 hover:border-primary-600'"></a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>

        <template x-if="products.length">
            <button type="submit" class="flex w-full items-center justify-center gap-1 border-t border-neutral-100 px-4 py-3 font-semibold text-primary-600 hover:bg-neutral-50">
                <span x-text="total > products.length ? `Xem tất cả ${total} kết quả` : 'Xem trang kết quả'"></span> →
            </button>
        </template>
    </div>
</form>
