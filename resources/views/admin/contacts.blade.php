@extends('admin.layout')

@section('title', 'Liên hệ')
@section('width', 'max-w-6xl')


@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="adm-h1">Liên hệ</h1>
    </div>
    <a href="{{ route('contact') }}" target="_blank" class="adm-btn adm-btn-ghost">Xem trang Liên hệ ↗</a>
</div>

{{-- ===== Tin nhắn ===== --}}
<section class="mt-6">
    <nav class="flex w-max flex-wrap gap-1 rounded-xl bg-slate-200/60 p-1 text-sm">
        @foreach (['new' => 'Chưa xử lý ('.$counts['new'].')', 'all' => 'Tất cả ('.$counts['all'].')'] as $key => $text)
            <a href="{{ route('admin.contacts.index', ['status' => $key]) }}" class="adm-tab {{ $status === $key ? 'adm-tab-active' : '' }}">{{ $text }}</a>
        @endforeach
    </nav>

    <div class="mt-4 space-y-3">
        @forelse ($messages as $m)
            <div class="adm-card flex flex-wrap gap-4 p-4 {{ $m->is_read ? 'opacity-70' : 'ring-2 ring-amber-200' }}">
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                        <span class="font-semibold text-adm-ink">{{ $m->name }}</span>
                        <span class="adm-badge {{ $m->is_read ? 'adm-badge-gray' : 'adm-badge-amber' }}">{{ $m->topicLabel() }}</span>
                        <span class="text-xs text-adm-muted">{{ $m->created_at->format('H:i d/m/Y') }}</span>
                    </p>
                    <p class="mt-1 text-sm">
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $m->phone) }}" class="font-medium text-primary-600 hover:underline">{{ $m->phone }}</a>
                        <span class="text-adm-muted">·</span>
                        <a href="mailto:{{ $m->email }}" class="text-slate-600 hover:underline">{{ $m->email }}</a>
                    </p>
                    <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $m->content }}</p>
                </div>
                <div class="flex shrink-0 items-start gap-2">
                    <form method="post" action="{{ route('admin.contacts.read', $m) }}">@csrf
                        <button class="adm-btn {{ $m->is_read ? 'adm-btn-ghost' : 'adm-btn-primary' }} py-1.5">{{ $m->is_read ? 'Đánh dấu chưa xử lý' : 'Đã xử lý' }}</button>
                    </form>
                    <form method="post" action="{{ route('admin.contacts.destroy', $m) }}" onsubmit="return confirm('Xóa tin nhắn này?')">@csrf
                        <button class="adm-btn adm-btn-ghost py-1.5 text-primary-600">Xóa</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="adm-card p-10 text-center text-sm text-adm-muted">{{ $status === 'new' ? 'Không có tin nhắn mới.' : 'Chưa có tin nhắn nào.' }}</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $messages->links() }}</div>
</section>

@endsection
