{{-- Khung SEO: từ khóa, tiêu đề/mô tả meta, xem trước Google, bảng chấm điểm. Dùng trong adminForm. --}}
@php($input = 'adm-input')
<section class="adm-card p-5">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-adm-ink">SEO</h2>
        <span class="rounded-full px-2.5 py-0.5 text-sm font-bold text-white" :class="scoreClass" x-text="score + '/100'"></span>
    </div>

    <label class="mt-3 block">
        <span class="mb-1 block text-sm font-medium">Từ khóa chính</span>
        <input type="text" name="focus_keyword" x-model="f.focus_keyword" placeholder="VD: giấy in A4 giá sỉ" class="{{ $input }}">
    </label>

    <label class="mt-3 block">
        <span class="mb-1 flex justify-between text-sm font-medium">Tiêu đề SEO
            <span class="text-xs font-normal" :class="(f.meta_title || '').length > 55 ? 'text-primary-600' : 'text-slate-500'" x-text="(f.meta_title || '').length + ' ký tự'"></span>
        </span>
        <input type="text" name="meta_title" x-model="f.meta_title" maxlength="255" :placeholder="defaultTitle" class="{{ $input }}">
        <span class="mt-0.5 block text-xs text-slate-500">Để trống = dùng tên mặc định. Website tự thêm “| {{ config('site.brand') }}” ở cuối.</span>
    </label>

    <label class="mt-3 block">
        <span class="mb-1 flex justify-between text-sm font-medium">Mô tả meta
            <span class="text-xs font-normal" :class="{ 'text-green-700': (f.meta_description || '').length >= 120 && (f.meta_description || '').length <= 160, 'text-primary-600': (f.meta_description || '').length > 160, 'text-slate-500': (f.meta_description || '').length < 120 }"
                  x-text="(f.meta_description || '').length + '/160'"></span>
        </span>
        <textarea name="meta_description" x-model="f.meta_description" rows="3" maxlength="300" class="{{ $input }}"
                  placeholder="120–160 ký tự, có từ khóa, nêu lợi ích + lời kêu gọi (VD: Báo giá trong 4 giờ, giao tận nơi…)"></textarea>
    </label>

    {{-- Xem trước kết quả trên Google --}}
    <div class="mt-4">
        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Xem trước trên Google</p>
        <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3 font-[arial,sans-serif]">
            <p class="truncate text-xs text-[#202124]" x-text="displayUrl"></p>
            <p class="mt-0.5 line-clamp-1 text-lg leading-snug text-[#1a0dab]" x-text="seoTitle"></p>
            <p class="mt-0.5 line-clamp-2 text-sm text-[#4d5156]" x-text="seoDesc || 'Chưa có mô tả – Google sẽ tự lấy một đoạn trong trang.'"></p>
        </div>
    </div>

    {{-- Bảng kiểm tra --}}
    <ul class="mt-4 space-y-2 text-sm">
        <template x-for="c in checks" :key="c.label">
            <li class="flex gap-2">
                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :class="c.ok ? 'bg-emerald-500' : (c.warn ? 'bg-amber-400' : 'bg-primary-600')"></span>
                <span>
                    <span :class="c.ok ? 'text-slate-700' : 'font-medium text-adm-ink'" x-text="c.label"></span>
                    <span x-show="!c.ok" class="block text-xs text-slate-500" x-text="c.tip"></span>
                </span>
            </li>
        </template>
    </ul>
</section>
