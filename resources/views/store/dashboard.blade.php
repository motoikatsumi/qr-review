@extends('layouts.store')

@section('title', 'ダッシュボード')

@section('content')
<div class="page-header">
    <h1>📊 ダッシュボード</h1>
</div>

@push('styles')
<style>
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
    .stat-label { font-size: 0.8rem; color: #888; margin-bottom: 8px; }
    .stat-value { font-size: 2rem; font-weight: 700; color: #065f46; }
    .stat-sub { font-size: 0.78rem; color: #aaa; margin-top: 4px; }
    .stat-warn { color: #d97706; }
</style>
@endpush

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">📝 QR口コミ件数</div>
        <div class="stat-value">{{ $reviewCount }}</div>
        <div class="stat-sub">累計</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">🌐 Google口コミ件数</div>
        <div class="stat-value">{{ $googleReviewCount }}</div>
        <div class="stat-sub">累計</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">💬 未返信（Google）</div>
        <div class="stat-value {{ $unrepliedCount > 0 ? 'stat-warn' : '' }}">{{ $unrepliedCount }}</div>
        <div class="stat-sub">
            @if($unrepliedCount > 0)
                <a href="/store/google-reviews?reply_status=unreplied" style="color:#d97706;">→ 今すぐ返信する</a>
            @else
                全て返信済み
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">🏪 店舗</div>
        <div class="stat-value" style="font-size:1.2rem;padding-top:8px;">{{ $store->name }}</div>
        <div class="stat-sub">{{ $store->businessType->name ?? '業種未設定' }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">最近のQR口コミ（5件）</div>
    <div class="card-body" style="padding:0;">
        @if($recentReviews->isEmpty())
            <p style="padding:20px;color:#888;font-size:0.9rem;">口コミはまだありません。</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>評価</th>
                        <th>コメント</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentReviews as $review)
                    <tr>
                        <td style="white-space:nowrap;">{{ $review->created_at->format('m/d H:i') }}</td>
                        <td><span class="stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span></td>
                        <td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $review->comment ?: '（コメントなし）' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:12px 20px;">
                <a href="/store/reviews" class="btn btn-secondary btn-sm">全件表示 →</a>
            </div>
        @endif
    </div>
</div>

<div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
    <a href="/review/{{ $store->slug }}" target="_blank" class="btn btn-primary">🔗 QRレビューページを開く</a>
    <a href="/store/google-reviews" class="btn btn-secondary">🌐 Google口コミ一覧</a>
    <a href="/store/settings/ai" class="btn btn-secondary">🤖 AI設定</a>
    <a href="/store/settings/keywords" class="btn btn-secondary">🏷️ キーワード</a>
    <a href="/store/settings/integrations" class="btn btn-secondary">🔗 外部連携設定</a>
</div>
@endsection
