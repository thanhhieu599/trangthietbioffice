{{-- $items: [['name' => ..., 'url' => ...], ...] (phần tử cuối là trang hiện tại) --}}
@php($trail = array_merge([['name' => 'Trang chủ', 'url' => route('home')]], $items))
<nav aria-label="Breadcrumb" class="mb-4 text-sm text-neutral-500">
    <ol class="flex flex-wrap items-center gap-1">
        @foreach ($trail as $i => $crumb)
            <li class="flex items-center gap-1">
                @if ($i > 0)<span aria-hidden="true">/</span>@endif
                @if ($loop->last)
                    <span class="text-neutral-700" aria-current="page">{{ $crumb['name'] }}</span>
                @else
                    <a href="{{ $crumb['url'] }}" class="hover:text-primary-600">{{ $crumb['name'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@push('schema')
{!! \App\Support\JsonLd::script(\App\Support\JsonLd::breadcrumb($trail)) !!}
@endpush
