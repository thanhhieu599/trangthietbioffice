{{-- Thông báo đầu form. $inline = true: lỗi đã hiện dưới từng ô, chỉ tóm tắt một dòng. --}}
@if (session('ok'))
    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        @if ($inline ?? false)
            Vui lòng kiểm tra lại {{ count($errors->all()) }} thông tin được đánh dấu bên dưới.
        @else
            <ul class="list-disc space-y-0.5 pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </div>
@endif
