@extends('layouts.admin')

@section('title', '口コミ一覧')

@section('content')
<div class="page-header">
    <h1>📝 口コミ一覧 <span style="font-size:0.6em;color:#888;font-weight:400;">（総件数: {{ $totalCount }}件）</span></h1>
    <a href="/admin/reviews/export?{{ http_build_query(request()->query()) }}" class="btn btn-secondary">📥 CSVエクスポート</a>
</div>

{{-- フィルター --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="/admin/reviews" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin:0;flex:1;min-width:150px;">
                <label>店舗</label>
                <select name="store_id">
                    <option value="">すべて</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:150px;">
                <label>評価フィルター</label>
                <select name="rating_filter">
                    <option value="">すべて</option>
                    <option value="low" {{ request('rating_filter') === 'low' ? 'selected' : '' }}>低評価（1〜3星）</option>
                    <option value="high" {{ request('rating_filter') === 'high' ? 'selected' : '' }}>高評価（4〜5星）</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" style="height:38px;">絞り込む</button>
        </form>
    </div>
</div>

<style>
    .review-list { display: flex; flex-direction: column; gap: 12px; }
    .review-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 20px; display: flex; gap: 16px; align-items: flex-start; transition: box-shadow 0.15s; }
    .review-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .review-meta { width: 140px; flex-shrink: 0; }
    .review-meta .date { font-size: 0.78rem; color: #9ca3af; }
    .review-meta .store-name { font-weight: 700; font-size: 0.9rem; margin-top: 4px; }
    .review-meta .stars { font-size: 1rem; margin-top: 4px; display: block; }
    .review-meta .persona { font-size: 0.78rem; color: #6b7280; margin-top: 6px; display: flex; flex-wrap: nowrap; gap: 4px; }
    .review-meta .persona span { display: inline-block; border-radius: 4px; padding: 1px 6px; font-weight: 600; white-space: nowrap; }
    .persona-male { background: #dbeafe; color: #2563eb; }
    .persona-female { background: #fce7f3; color: #db2777; }
    .persona-other { background: #f3f4f6; color: #6b7280; }
    .persona-age-10 { background: #fef9c3; color: #a16207; }
    .persona-age-20 { background: #d1fae5; color: #059669; }
    .persona-age-30 { background: #dbeafe; color: #2563eb; }
    .persona-age-40 { background: #e0e7ff; color: #4338ca; }
    .persona-age-50 { background: #ede9fe; color: #7c3aed; }
    .persona-age-60 { background: #fce7f3; color: #be185d; }
    .persona-age-default { background: #f3f4f6; color: #6b7280; }
    .persona-new { background: #fef3c7; color: #d97706; }
    .persona-repeat { background: #d1fae5; color: #059669; }
    .review-body { flex: 1; min-width: 0; }
    .review-comment { font-size: 0.9rem; line-height: 1.7; color: #374151; white-space: pre-wrap; word-break: break-word; }
    .review-footer { display: flex; gap: 8px; align-items: center; margin-top: 8px; flex-wrap: wrap; }
    .review-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .review-badge-red { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .review-badge-green { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .review-badge-gray { background: #f9fafb; color: #6b7280; border: 1px solid #e5e7eb; }
    .review-badge-ai { background: #eef2ff; color: #667eea; border: 1px solid #c7d2fe; }
    .rating-bar { display: inline-block; width: 4px; height: 28px; border-radius: 2px; margin-right: 12px; flex-shrink: 0; }
    .rating-low { background: #ef4444; }
    .rating-high { background: #22c55e; }
    .rating-mid { background: #f59e0b; }
</style>

<div class="review-list">
    @forelse($reviews as $review)
    <div class="review-item">
        <span class="rating-bar {{ $review->rating <= 2 ? 'rating-low' : ($review->rating <= 3 ? 'rating-mid' : 'rating-high') }}"></span>
        <div class="review-meta">
            <div class="date">{{ $review->created_at->format('Y/m/d H:i') }}</div>
            <div class="store-name">{{ $review->store->name }}</div>
            <span class="stars" style="color: {{ $review->rating <= 3 ? '#ef4444' : '#f59e0b' }};">
                {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
            </span>
            <div class="persona">
                <span class="{{ $review->gender === '男性' ? 'persona-male' : ($review->gender === '女性' ? 'persona-female' : 'persona-other') }}">{{ $review->gender ?: '-' }}</span>
                @php $ageClass = match($review->age) { '10' => 'persona-age-10', '20' => 'persona-age-20', '30' => 'persona-age-30', '40' => 'persona-age-40', '50' => 'persona-age-50', '60' => 'persona-age-60', default => 'persona-age-default' }; @endphp
                <span class="{{ $ageClass }}">{{ $review->age ? $review->age . '代' : '-' }}</span>
                @if($review->visit_type)
                <span class="{{ $review->visit_type === '新規' ? 'persona-new' : 'persona-repeat' }}">{{ $review->visit_type }}</span>
                @endif
            </div>
        </div>
        <div class="review-body">
            <div class="review-comment">{{ trim($review->comment) }}</div>
            <div class="review-footer">
                @if($review->status === 'email_sent')
                    <span class="review-badge review-badge-red">📧 メール送信</span>
                @elseif($review->status === 'redirected_to_google')
                    <span class="review-badge review-badge-green">🔗 Google誘導</span>
                @endif
                @if($review->ai_generated_text)
                    <span class="review-badge review-badge-ai">🤖 AI生成</span>
                @endif
                @if(Auth::user()->isAdmin())
                <form action="/admin/reviews/{{ $review->id }}" method="POST" style="display:inline;margin-left:auto;" onsubmit="return confirm('この口コミを削除しますか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="padding:2px 10px;font-size:0.75rem;">🗑 削除</button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;padding:40px;color:#888;">
        口コミはまだありません。
    </div>
    @endforelse
</div>

@if($reviews->hasPages())
<div style="margin-top:20px; display:flex; justify-content:center;">
    <style>
        nav[role="navigation"] { width: 100%; }
        nav[role="navigation"] p { display: none; }
        .pagination { display: flex; list-style: none; padding: 0; margin: 0; justify-content: center; gap: 5px; }
        .pagination li a, .pagination li span { display: block; padding: 8px 12px; border: 1px solid #ddd; background: #fff; color: #667eea; text-decoration: none; border-radius: 4px; font-size: 0.9rem; }
        .pagination li.active span { background: #667eea; color: #fff; border-color: #667eea; }
        .pagination li.disabled span { color: #aaa; background: #f9f9f9; }
        .pagination li a:hover { background: #f3f4f6; }
    </style>
    {{ $reviews->appends(request()->query())->links('pagination::bootstrap-4') }}
</div>
@endif
@endsection
