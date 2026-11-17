@if (session('ok'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('ok') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-md border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700">
        <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
