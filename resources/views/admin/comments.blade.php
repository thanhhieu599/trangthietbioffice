@extends('admin.layout')

@section('title', 'Bình luận')
@section('width', 'max-w-6xl')

@section('content')
<h1 class="adm-h1">Bình luận bài viết</h1>
<p class="adm-sub">Bình luận mới luôn chờ duyệt, chỉ hiện trên website sau khi bạn bấm “Duyệt”.</p>

<nav class="mt-5 flex flex-wrap gap-1 rounded-xl bg-slate-200/60 p-1 text-sm w-max">
    @foreach (['pending' => 'Chờ duyệt ('.$counts['pending'].')', 'approved' => 'Đã duyệt ('.$counts['approved'].')', 'all' => 'Tất cả'] as $key => $label)
        <a href="{{ route('admin.comments.index', ['status' => $key]) }}" class="adm-tab {{ $status === $key ? 'adm-tab-active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>

<div class="mt-4 space-y-3">
    @forelse ($comments as $c)
        <div class="adm-card flex flex-wrap gap-4 p-4 {{ $c->is_approved ? '' : 'ring-2 ring-amber-200' }}">
            <div class="min-w-0 flex-1">
                <p class="text-sm">
                    <span class="font-semibold text-adm-ink">{{ $c->name }}</span>
                    <span class="text-adm-muted">· {{ $c->email }} · {{ $c->created_at->format('H:i d/m/Y') }}</span>
                    @if (! $c->is_approved)<span class="adm-badge adm-badge-amber ml-1">Chờ duyệt</span>@endif
                </p>
                <p class="mt-1 text-xs text-adm-muted">Bài: @if ($c->post)<a href="{{ $c->post->url() }}#binh-luan" target="_blank" class="text-primary-600 hover:underline">{{ $c->post->title }}</a>@else (đã xóa) @endif</p>
                <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $c->content }}</p>
            </div>
            <div class="flex shrink-0 items-start gap-2">
                <form method="post" action="{{ route('admin.comments.approve', $c) }}">@csrf
                    <button class="adm-btn {{ $c->is_approved ? 'adm-btn-ghost' : 'adm-btn-primary' }} py-1.5">{{ $c->is_approved ? 'Ẩn' : 'Duyệt' }}</button>
                </form>
                <form method="post" action="{{ route('admin.comments.destroy', $c) }}" onsubmit="return confirm('Xóa bình luận này?')">@csrf
                    <button class="adm-btn adm-btn-ghost py-1.5 text-primary-600">Xóa</button>
                </form>
            </div>
        </div>
    @empty
        <div class="adm-card p-10 text-center text-sm text-adm-muted">Không có bình luận nào.</div>
    @endforelse
</div>
<div class="mt-4">{{ $comments->links() }}</div>
@endsection
