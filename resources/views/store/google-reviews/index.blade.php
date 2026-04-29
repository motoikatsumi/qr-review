@extends('layouts.store')

@section('title', 'Google口コミ一覧')

@section('content')
<div class="page-header">
    <h1>🌐 Google口コミ一覧</h1>
</div>

@push('styles')
<style>
    .review-card { background: white; border-radius: 10px; padding: 18px 20px; margin-bottom: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.07); }
    .review-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; flex-wrap: wrap; }
    .reviewer-name { font-weight: 700; font-size: 0.95rem; }
    .review-date { font-size: 0.78rem; color: #aaa; }
    .review-comment { font-size: 0.9rem; line-height: 1.7; color: #444; margin-bottom: 12px; }
    .reply-section { background: #f8f9fa; border-radius: 8px; padding: 14px; border-left: 3px solid #059669; }
    .reply-label { font-size: 0.78rem; color: #888; margin-bottom: 6px; }
    .reply-text { font-size: 0.88rem; line-height: 1.7; white-space: pre-wrap; color: #333; }
    .reply-form textarea { width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.88rem; font-family: inherit; min-height: 100px; resize: vertical; }
    .reply-form textarea:focus { border-color: #059669; outline: none; }
</style>
@endpush

<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <select name="rating" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.88rem;">
                <option value="">全評価</option>
                @for($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>★{{ $i }}</option>
                @endfor
            </select>
            <select name="reply_status" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.88rem;">
                <option value="">全て</option>
                <option value="unreplied" {{ request('reply_status') === 'unreplied' ? 'selected' : '' }}>未返信のみ</option>
                <option value="replied"   {{ request('reply_status') === 'replied'   ? 'selected' : '' }}>返信済みのみ</option>
            </select>
            <button class="btn btn-secondary btn-sm" type="submit">絞り込み</button>
            @if(request('rating') || request('reply_status'))
                <a href="/store/google-reviews" class="btn btn-secondary btn-sm">リセット</a>
            @endif
        </form>
    </div>
</div>

@forelse($reviews as $review)
<div class="review-card" id="review-{{ $review->id }}">
    <div class="review-meta">
        <span class="reviewer-name">{{ $review->reviewer_name ?: '匿名ユーザー' }}</span>
        <span class="stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
        <span class="review-date">{{ $review->reviewed_at ? $review->reviewed_at->format('Y/m/d') : '—' }}</span>
        @if($review->reply_comment)
            <span class="badge badge-green">返信済み</span>
        @else
            <span class="badge badge-gray">未返信</span>
        @endif
    </div>

    @if($review->comment)
        <div class="review-comment">{{ $review->comment }}</div>
    @else
        <div class="review-comment" style="color:#aaa;">（コメントなし）</div>
    @endif

    @if($review->reply_comment)
        <div class="reply-section">
            <div class="reply-label">オーナー返信</div>
            <div class="reply-text">{{ $review->reply_comment }}</div>
        </div>
    @else
        <details style="margin-top:8px;">
            <summary style="cursor:pointer;font-size:0.85rem;color:#059669;font-weight:600;">💬 返信を投稿する</summary>
            <div style="margin-top:12px;">
                <div class="reply-form">
                    <form method="POST" action="/store/google-reviews/{{ $review->id }}/reply">
                        @csrf
                        <textarea name="reply_comment" placeholder="返信を入力してください..." rows="4"></textarea>
                        <div style="display:flex;gap:8px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary btn-sm">返信を投稿</button>
                        </div>
                    </form>
                </div>
            </div>
        </details>
    @endif
</div>
@empty
    <div class="card"><div class="card-body" style="color:#888;font-size:0.9rem;">Google口コミはありません。</div></div>
@endforelse

<div style="margin-top:8px;">
    {{ $reviews->withQueryString()->links() }}
</div>
@endsection
