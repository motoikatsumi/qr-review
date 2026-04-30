@extends('layouts.admin')

@section('title', '統計ダッシュボード')

@push('styles')
<style>
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        margin-bottom: 24px;
        padding: 16px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    /* サマリー比較エリア */
    .summary-compare {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 28px;
    }
    .summary-panel {
        background: white;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
    }
    .summary-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }
    .summary-panel.system::before {
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    .summary-panel.google::before {
        background: linear-gradient(90deg, #10b981, #059669);
    }
    .summary-panel-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e1b4b;
        margin-bottom: 16px;
    }
    .summary-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    .summary-stat {
        text-align: center;
        padding: 12px 8px;
        background: #f8f9ff;
        border-radius: 10px;
    }
    .summary-panel.google .summary-stat {
        background: #f0fdf4;
    }
    .summary-stat .s-value {
        font-size: 1.6rem;
        font-weight: 800;
        color: #1e1b4b;
        line-height: 1.2;
    }
    .summary-stat .s-label {
        font-size: 0.82rem;
        color: #888;
        margin-top: 2px;
    }

    /* タブ切り替え */
    .detail-tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 20px;
        background: white;
        border-radius: 10px;
        padding: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        width: fit-content;
    }
    .detail-tab {
        padding: 10px 24px;
        border-radius: 8px;
        border: none;
        background: transparent;
        font-size: 0.9rem;
        font-weight: 600;
        color: #888;
        cursor: pointer;
        transition: all 0.2s;
    }
    .detail-tab.active {
        background: #667eea;
        color: white;
    }
    .detail-tab.active.tab-google {
        background: #10b981;
    }
    .detail-tab:hover:not(.active) {
        background: #f3f4f6;
        color: #555;
    }
    .detail-panel { display: none; }
    .detail-panel.active { display: block; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        text-align: center;
    }
    .stat-card .stat-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
    }
    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1e1b4b;
        line-height: 1.2;
    }
    .stat-card .stat-label {
        font-size: 0.85rem;
        color: #888;
        margin-top: 4px;
    }
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }
    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .chart-card h3 {
        font-size: 1rem;
        color: #333;
        margin-bottom: 20px;
    }
    .rating-bars {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .rating-bar-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .rating-bar-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #555;
        min-width: 36px;
        text-align: right;
    }
    .rating-bar-track {
        flex: 1;
        height: 24px;
        background: #f0f0f0;
        border-radius: 6px;
        overflow: hidden;
    }
    .rating-bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 0.6s ease;
        min-width: 2px;
    }
    .rating-bar-fill.star-5 { background: linear-gradient(135deg, #10b981, #059669); }
    .rating-bar-fill.star-4 { background: linear-gradient(135deg, #34d399, #10b981); }
    .rating-bar-fill.star-3 { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
    .rating-bar-fill.star-2 { background: linear-gradient(135deg, #fb923c, #f97316); }
    .rating-bar-fill.star-1 { background: linear-gradient(135deg, #f87171, #ef4444); }
    .rating-bar-count {
        font-size: 0.85rem;
        color: #888;
        min-width: 40px;
    }
    .status-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: #f8f9ff;
        border-radius: 10px;
    }
    .status-item .status-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
    }
    .status-item .status-count {
        font-size: 1.1rem;
        font-weight: 700;
        color: #667eea;
    }
    .daily-chart {
        width: 100%;
        overflow-x: auto;
    }
    .daily-bars {
        display: flex;
        align-items: flex-end;
        gap: 3px;
        height: 160px;
        padding-top: 10px;
    }
    .daily-bar-wrapper {
        flex: 1;
        min-width: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        height: 100%;
        justify-content: flex-end;
        position: relative;
        padding-bottom: 44px; /* 日付ラベル領域を予約 → 全棒で底位置を統一 */
    }
    .daily-bar-count {
        font-size: 0.72rem;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 2px;
    }
    .daily-bar {
        width: 100%;
        max-width: 24px;
        background: linear-gradient(180deg, #667eea, #764ba2);
        border-radius: 4px 4px 0 0;
        transition: height 0.4s ease;
        cursor: pointer;
        position: relative;
    }
    .daily-bar:hover {
        opacity: 0.8;
    }
    .daily-bar-date {
        font-size: 0.68rem;
        color: #aaa;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        height: 40px;
        margin-top: 0;
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
    }
    .daily-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        background: #1e1b4b;
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.78rem;
        white-space: nowrap;
        z-index: 10;
    }
    .daily-bar:hover .daily-tooltip {
        display: block;
    }
    .full-width-chart {
        grid-column: 1 / -1;
    }

    /* 期間切替（日別/月別/年別） */
    .period-tabs {
        display: inline-flex;
        gap: 2px;
        background: #f3f4f6;
        border-radius: 8px;
        padding: 3px;
        margin-left: 12px;
    }
    .period-tab {
        padding: 5px 14px;
        border-radius: 6px;
        border: none;
        background: transparent;
        font-size: 0.78rem;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s;
    }
    .period-tab.active {
        background: white;
        color: #1e1b4b;
        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
    }
    .period-tab:hover:not(.active) {
        color: #1e1b4b;
    }
    .period-panel { display: none; }
    .period-panel.active { display: block; }

    .chart-card-header {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .chart-card-header h3 {
        margin: 0;
    }

    /* 月別/年別バー（差分付き） */
    .daily-bar-diff {
        font-size: 0.66rem;
        font-weight: 700;
        margin-bottom: 2px;
        line-height: 1;
    }
    .daily-bar-diff.up { color: #059669; }
    .daily-bar-diff.down { color: #dc2626; }
    .daily-bar-diff.flat { color: #9ca3af; }

    /* 月別/年別はバー数が少ないので横書き表示 */
    .period-panel:not([id$="-day"]) .daily-bar-wrapper {
        padding-bottom: 22px; /* 横書きの日付高さに合わせて縮める */
    }
    .period-panel:not([id$="-day"]) .daily-bar-date {
        writing-mode: horizontal-tb;
        text-orientation: unset;
        height: auto;
        font-size: 0.75rem;
        white-space: nowrap;
    }
    .period-panel:not([id$="-day"]) .daily-bar-wrapper {
        min-width: 40px;
    }
    .period-panel:not([id$="-day"]) .daily-bars {
        gap: 8px;
    }

    @media (max-width: 768px) {
        .summary-compare { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .charts-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('styles')
<style>
    /* === 今日やること セクション === */
    .todo-section {
        margin-bottom: 28px;
    }
    .todo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        grid-auto-rows: 1fr; /* 同じ行のすべてのカードを同じ高さに強制 */
        gap: 14px;
    }
    .todo-card {
        display: flex;
        gap: 14px;
        padding: 18px 20px;
        border-radius: 14px;
        background: white;
        border: 2px solid transparent;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        text-decoration: none;
        transition: all 0.2s ease;
        align-items: center;
        height: 100%;
        box-sizing: border-box;
    }
    .todo-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.12);
    }
    .todo-card.warn { border-color: #fbbf24; background: linear-gradient(135deg, #fffbeb, #fef3c7); }
    .todo-card.alert { border-color: #fb7185; background: linear-gradient(135deg, #fff1f2, #ffe4e6); }
    .todo-card.info { border-color: #a5b4fc; background: linear-gradient(135deg, #eef2ff, #e0e7ff); }
    .todo-card.success { border-color: #86efac; background: linear-gradient(135deg, #f0fdf4, #dcfce7); }
    .todo-card-icon { font-size: 2rem; flex-shrink: 0; }
    .todo-card-body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 2px;
    }
    .todo-card-count {
        font-size: 1.6rem;
        font-weight: 700;
        color: #1e1b4b;
        line-height: 1.2;
    }
    .todo-card-label {
        font-size: 0.85rem;
        color: #4b5563;
        font-weight: 500;
        line-height: 1.4;
    }
    .todo-card-link {
        font-size: 0.78rem;
        color: #6366f1;
        font-weight: 600;
        line-height: 1.4;
    }

    /* === クイックアクション === */
    .quick-actions {
        margin-bottom: 28px;
    }
    .quick-actions h2 {
        font-size: 1.05rem;
        color: #1e1b4b;
        margin-bottom: 12px;
        font-weight: 700;
    }
    .quick-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }
    .quick-action {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        text-decoration: none;
        color: #1e1b4b;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.15s ease;
        border: 1px solid #e5e7eb;
    }
    .quick-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border-color: #a5b4fc;
        color: #4338ca;
    }
    .quick-action-icon { font-size: 1.3rem; }

    /* === 設定アラート === */
    .setup-alerts {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 28px;
    }
    .setup-alerts h3 {
        font-size: 0.95rem;
        color: #92400e;
        margin: 0 0 10px 0;
        font-weight: 700;
    }
    .setup-alerts ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .setup-alerts li {
        padding: 6px 0;
        font-size: 0.85rem;
        color: #78350f;
    }
    .setup-alerts a {
        color: #78350f;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .setup-alerts a:hover { text-decoration: underline; }

    /* === 統計セクションの見出し === */
    .section-divider {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 32px 0 16px;
    }
    .section-divider h2 {
        font-size: 1rem;
        color: #6b7280;
        margin: 0;
        font-weight: 600;
    }
    .section-divider-line {
        flex: 1;
        height: 1px;
        background: #e5e7eb;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>🏠 ホーム</h1>
    @if(\App\Http\Controllers\Admin\OnboardingController::isCompleted())
        <a href="/admin/onboarding" class="btn btn-secondary btn-sm" style="font-size:0.78rem;">🚀 セットアップを再表示</a>
    @endif
</div>

@if(!\App\Http\Controllers\Admin\OnboardingController::isCompleted())
<div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border-radius:14px;padding:22px 26px;margin-bottom:20px;color:white;display:flex;align-items:center;gap:18px;box-shadow:0 6px 20px rgba(102,126,234,0.25);">
    <div style="font-size:2.6rem;">🚀</div>
    <div style="flex:1;">
        <div style="font-size:1.1rem;font-weight:600;margin-bottom:4px;">初期セットアップウィザードを開始しましょう</div>
        <div style="font-size:0.88rem;opacity:0.9;">5 ステップで「業種選択 → 店舗作成 → 連携 → AI 設定 → 動作確認」までガイドします。</div>
    </div>
    <a href="/admin/onboarding" class="btn" style="background:white;color:#667eea;font-weight:600;padding:10px 20px;flex-shrink:0;">セットアップを開始 →</a>
</div>
@endif

{{-- 未払い請求書アラート --}}
@if(isset($unpaidInvoices) && $unpaidInvoices->count() > 0)
@php
    $totalUnpaid = $unpaidInvoices->sum('total_amount');
    $hasOverdue = $unpaidInvoices->contains(fn($i) => $i->due_date->isPast());
@endphp
<div style="background:{{ $hasOverdue ? 'linear-gradient(135deg,#fee2e2,#fecaca)' : 'linear-gradient(135deg,#fef3c7,#fde68a)' }};border-left:5px solid {{ $hasOverdue ? '#dc2626' : '#f59e0b' }};border-radius:12px;padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;gap:16px;">
    <div style="font-size:2rem;">{{ $hasOverdue ? '🚨' : '💴' }}</div>
    <div style="flex:1;color:{{ $hasOverdue ? '#7f1d1d' : '#78350f' }};">
        <div style="font-size:1rem;font-weight:600;">
            未払いの請求書が {{ $unpaidInvoices->count() }} 件あります（合計 ¥{{ number_format($totalUnpaid) }}）
        </div>
        <div style="font-size:0.85rem;margin-top:4px;">
            @if($hasOverdue)
                <strong>支払期限が過ぎている請求書があります。</strong>
            @else
                早めにご確認・お振込みをお願いいたします。
            @endif
        </div>
    </div>
    <a href="/admin/invoices" class="btn" style="background:white;color:{{ $hasOverdue ? '#dc2626' : '#92400e' }};font-weight:600;padding:10px 20px;flex-shrink:0;">📄 請求書を確認 →</a>
</div>
@endif

{{-- フィルター --}}
<div class="filter-bar">
    <form method="GET" action="/admin/dashboard" style="display:flex;gap:12px;align-items:flex-end;width:100%;">
        <div class="form-group" style="margin:0;flex:1;max-width:300px;">
            <label>店舗で絞り込み</label>
            <select name="store_id" onchange="this.form.submit()">
                <option value="">すべての店舗</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="margin-bottom:1px;">適用</button>
    </form>
</div>

{{-- ============================== --}}
{{-- 今日やること --}}
{{-- ============================== --}}
<div class="todo-section">
    <div class="section-divider" style="margin-top:0;">
        <h2>📌 今日の状況</h2>
        <div class="section-divider-line"></div>
    </div>
    <div class="todo-grid">
        <a href="/admin/google-reviews{{ $storeId ? '?store_id=' . $storeId : '' }}" class="todo-card {{ $unrepliedGoogleCount > 0 ? 'warn' : 'success' }}">
            <div class="todo-card-icon">💬</div>
            <div class="todo-card-body">
                <div class="todo-card-count">{{ $unrepliedGoogleCount }}<span style="font-size:0.85rem;font-weight:normal;margin-left:4px;">件</span></div>
                <div class="todo-card-label">未返信の Google 口コミ</div>
                <div class="todo-card-link">→ 返信する</div>
            </div>
        </a>

        <a href="/admin/reviews?rating_filter=low&from_date={{ now()->subDays(7)->format('Y-m-d') }}{{ $storeId ? '&store_id=' . $storeId : '' }}" class="todo-card {{ $lowRatingReviewCount > 0 ? 'alert' : 'success' }}">
            <div class="todo-card-icon">⚠️</div>
            <div class="todo-card-body">
                <div class="todo-card-count">{{ $lowRatingReviewCount }}<span style="font-size:0.85rem;font-weight:normal;margin-left:4px;">件</span></div>
                <div class="todo-card-label">直近 7 日の低評価口コミ</div>
                <div class="todo-card-link">→ 確認する</div>
            </div>
        </a>

        <a href="/admin/purchase-posts{{ $storeId ? '?store_id=' . $storeId : '' }}" class="todo-card {{ $failedPostCount > 0 ? 'alert' : 'success' }}">
            <div class="todo-card-icon">📦</div>
            <div class="todo-card-body">
                <div class="todo-card-count">{{ $failedPostCount }}<span style="font-size:0.85rem;font-weight:normal;margin-left:4px;">件</span></div>
                <div class="todo-card-label">投稿失敗の買取投稿</div>
                <div class="todo-card-link">→ リトライ</div>
            </div>
        </a>

        <div class="todo-card info">
            <div class="todo-card-icon">📝</div>
            <div class="todo-card-body">
                <div class="todo-card-count">{{ $todayReviewCount + $todayGoogleReviewCount }}<span style="font-size:0.85rem;font-weight:normal;margin-left:4px;">件</span></div>
                <div class="todo-card-label">本日の新着口コミ</div>
                <div class="todo-card-link">内部 {{ $todayReviewCount }} / Google {{ $todayGoogleReviewCount }}</div>
            </div>
        </div>
    </div>
</div>

{{-- 設定アラート --}}
@if (count($setupAlerts) > 0)
<div class="setup-alerts">
    <h3>⚠️ 設定が未完了の項目</h3>
    <ul>
        @foreach ($setupAlerts as $alert)
            <li><a href="{{ $alert['url'] }}">{{ $alert['icon'] }} {{ $alert['label'] }} →</a></li>
        @endforeach
    </ul>
</div>
@endif

{{-- クイックアクション --}}
<div class="quick-actions">
    <div class="section-divider">
        <h2>🚀 よく使う操作</h2>
        <div class="section-divider-line"></div>
    </div>
    <div class="quick-action-grid">
        <a href="/admin/reviews" class="quick-action">
            <span class="quick-action-icon">📝</span>
            <span>口コミ一覧を見る</span>
        </a>
        <a href="/admin/google-reviews" class="quick-action">
            <span class="quick-action-icon">🌐</span>
            <span>Google 口コミを返信</span>
        </a>
        <a href="/admin/purchase-posts/create" class="quick-action">
            <span class="quick-action-icon">📦</span>
            <span>買取投稿を作る</span>
        </a>
        <a href="/admin/stores" class="quick-action">
            <span class="quick-action-icon">🏪</span>
            <span>店舗設定</span>
        </a>
        <a href="/admin/suggestion-themes" class="quick-action">
            <span class="quick-action-icon">🏷️</span>
            <span>テーマ管理</span>
        </a>
        <a href="/admin/business-types" class="quick-action">
            <span class="quick-action-icon">🏢</span>
            <span>業種設定</span>
        </a>
    </div>
</div>

<div class="section-divider">
    <h2>📊 統計情報</h2>
    <div class="section-divider-line"></div>
</div>

{{-- ============================== --}}
{{-- サマリー比較（左: システム / 右: Google） --}}
{{-- ============================== --}}
<div class="summary-compare">
    <div class="summary-panel system">
        <div class="summary-panel-title">📝 システム口コミ</div>
        <div class="summary-stats">
            <div class="summary-stat">
                <div class="s-value">{{ $totalReviews }}</div>
                <div class="s-label">総口コミ数</div>
            </div>
            <div class="summary-stat">
                <div class="s-value">{{ $totalReviews > 0 ? number_format($avgRating, 1) : '-' }}</div>
                <div class="s-label">平均評価</div>
            </div>
            <div class="summary-stat">
                <div class="s-value">{{ $highRatingRate }}%</div>
                <div class="s-label">高評価率（4★以上）</div>
            </div>
            <div class="summary-stat">
                <div class="s-value">{{ $googleRate }}%</div>
                <div class="s-label">Google誘導率</div>
            </div>
        </div>
    </div>
    <div class="summary-panel google">
        <div class="summary-panel-title">🌐 Google口コミ</div>
        <div class="summary-stats">
            <div class="summary-stat">
                <div class="s-value">{{ $gTotalReviews }}</div>
                <div class="s-label">口コミ数</div>
            </div>
            <div class="summary-stat">
                <div class="s-value">{{ $gTotalReviews > 0 ? number_format($gAvgRating, 1) : '-' }}</div>
                <div class="s-label">平均評価</div>
            </div>
            <div class="summary-stat">
                <div class="s-value">{{ $gReplyRate }}%</div>
                <div class="s-label">返信率（{{ $gRepliedCount }}/{{ $gTotalReviews }}）</div>
            </div>
            <div class="summary-stat">
                <div class="s-value">{{ $gHighRatingRate }}%</div>
                <div class="s-label">高評価率（4★以上）</div>
            </div>
        </div>
    </div>
</div>

{{-- ============================== --}}
{{-- 詳細チャートタブ --}}
{{-- ============================== --}}
<div class="detail-tabs">
    <button class="detail-tab active" onclick="switchTab('system', this)">📝 システム口コミ</button>
    <button class="detail-tab tab-google" onclick="switchTab('google', this)">🌐 Google口コミ</button>
</div>

{{-- システム口コミ詳細 --}}
<div class="detail-panel active" id="panel-system">
    <div class="charts-grid">
        <div class="chart-card">
            <h3>⭐ 評価分布</h3>
            <div class="rating-bars">
                @for ($i = 5; $i >= 1; $i--)
                    @php $pct = $totalReviews > 0 ? ($ratingCounts[$i] / $totalReviews * 100) : 0; @endphp
                    <div class="rating-bar-row">
                        <span class="rating-bar-label">{{ $i }}★</span>
                        <div class="rating-bar-track">
                            <div class="rating-bar-fill star-{{ $i }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="rating-bar-count">{{ $ratingCounts[$i] }}件</span>
                    </div>
                @endfor
            </div>
        </div>

        <div class="chart-card">
            <h3>📋 ステータス分布</h3>
            <div class="status-list">
                <div class="status-item">
                    <span class="status-name">📍 Google誘導</span>
                    <span class="status-count">{{ $statusDistribution['redirected_to_google'] ?? 0 }}件</span>
                </div>
                <div class="status-item">
                    <span class="status-name">📧 メール送信（低評価）</span>
                    <span class="status-count">{{ $statusDistribution['email_sent'] ?? 0 }}件</span>
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3>👥 性別分布</h3>
            @php $genderTotal = array_sum($genderDistribution); @endphp
            @if($genderTotal > 0)
                <div class="rating-bars">
                    @foreach($genderDistribution as $gender => $count)
                        @php $pct = $genderTotal > 0 ? ($count / $genderTotal * 100) : 0; @endphp
                        <div class="rating-bar-row">
                            <span class="rating-bar-label" style="min-width:44px;">{{ $gender }}</span>
                            <div class="rating-bar-track">
                                <div class="rating-bar-fill" style="width: {{ $pct }}%; background: linear-gradient(135deg, {{ $gender === '男性' ? '#3b82f6, #2563eb' : '#ec4899, #db2777' }});"></div>
                            </div>
                            <span class="rating-bar-count">{{ $count }}件（{{ round($pct) }}%）</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="text-align:center;color:#aaa;padding:30px 0;">性別データなし</p>
            @endif
        </div>

        <div class="chart-card">
            <h3>📅 年代分布</h3>
            @php $ageTotal = array_sum($ageDistribution); @endphp
            @if($ageTotal > 0)
                <div class="rating-bars">
                    @foreach($ageDistribution as $age => $count)
                        @php $pct = $ageTotal > 0 ? ($count / $ageTotal * 100) : 0; @endphp
                        <div class="rating-bar-row">
                            <span class="rating-bar-label" style="min-width:44px;">{{ $age }}代</span>
                            <div class="rating-bar-track">
                                <div class="rating-bar-fill" style="width: {{ $pct }}%; background: linear-gradient(135deg, #8b5cf6, #6d28d9);"></div>
                            </div>
                            <span class="rating-bar-count">{{ $count }}件（{{ round($pct) }}%）</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="text-align:center;color:#aaa;padding:30px 0;">年代データなし</p>
            @endif
        </div>

        <div class="chart-card full-width-chart">
            <div class="chart-card-header">
                <h3>📈 口コミ推移</h3>
                <div class="period-tabs">
                    <button class="period-tab active" onclick="switchPeriod('system','day',this)">日別</button>
                    <button class="period-tab" onclick="switchPeriod('system','month',this)">月別</button>
                    <button class="period-tab" onclick="switchPeriod('system','year',this)">年別</button>
                </div>
            </div>

            {{-- 日別 --}}
            <div class="period-panel active" id="period-system-day">
                @if($dailyReviews->count() > 0)
                    @php $maxCount = $dailyReviews->max('count') ?: 1; @endphp
                    <div class="daily-chart">
                        <div class="daily-bars">
                            @foreach($dailyReviews as $day)
                                @php $height = max(4, ($day->count / $maxCount) * 140); @endphp
                                <div class="daily-bar-wrapper">
                                    <span class="daily-bar-count">{{ $day->count }}</span>
                                    <div class="daily-bar" style="height: {{ $height }}px;">
                                        <div class="daily-tooltip">
                                            {{ \Carbon\Carbon::parse($day->date)->format('n/j') }}：{{ $day->count }}件（平均{{ number_format($day->avg_rating, 1) }}★）
                                        </div>
                                    </div>
                                    <span class="daily-bar-date">{{ \Carbon\Carbon::parse($day->date)->format('n/j') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p style="text-align:center;color:#aaa;padding:40px 0;">直近30日の口コミデータがありません</p>
                @endif
            </div>

            {{-- 月別 --}}
            <div class="period-panel" id="period-system-month">
                @php $monthMax = collect($monthlyReviews)->max('count') ?: 1; @endphp
                <div class="daily-chart">
                    <div class="daily-bars">
                        @foreach($monthlyReviews as $m)
                            @php
                                $height = max(4, ($m['count'] / $monthMax) * 140);
                                $tip = $m['label'] . '：' . $m['count'] . '件';
                                if ($m['avg_rating'] !== null) {
                                    $tip .= '（平均' . number_format($m['avg_rating'], 1) . '★）';
                                }
                                if ($m['diff'] !== null) {
                                    $tip .= ' / 前月比 ' . ($m['diff'] >= 0 ? '+' : '') . $m['diff'] . '件';
                                }
                            @endphp
                            <div class="daily-bar-wrapper">
                                @if($m['diff'] === null)
                                    <span class="daily-bar-diff flat">―</span>
                                @elseif($m['diff'] > 0)
                                    <span class="daily-bar-diff up">+{{ $m['diff'] }}</span>
                                @elseif($m['diff'] < 0)
                                    <span class="daily-bar-diff down">{{ $m['diff'] }}</span>
                                @else
                                    <span class="daily-bar-diff flat">±0</span>
                                @endif
                                <span class="daily-bar-count">{{ $m['count'] }}</span>
                                <div class="daily-bar" style="height: {{ $height }}px;">
                                    <div class="daily-tooltip">{{ $tip }}</div>
                                </div>
                                <span class="daily-bar-date">{{ $m['short'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 年別 --}}
            <div class="period-panel" id="period-system-year">
                @php $yearMax = collect($yearlyReviews)->max('count') ?: 1; @endphp
                <div class="daily-chart">
                    <div class="daily-bars">
                        @foreach($yearlyReviews as $y)
                            @php
                                $height = max(4, ($y['count'] / $yearMax) * 140);
                                $tip = $y['label'] . '：' . $y['count'] . '件';
                                if ($y['avg_rating'] !== null) {
                                    $tip .= '（平均' . number_format($y['avg_rating'], 1) . '★）';
                                }
                                if ($y['diff'] !== null) {
                                    $tip .= ' / 前年比 ' . ($y['diff'] >= 0 ? '+' : '') . $y['diff'] . '件';
                                }
                            @endphp
                            <div class="daily-bar-wrapper">
                                @if($y['diff'] === null)
                                    <span class="daily-bar-diff flat">―</span>
                                @elseif($y['diff'] > 0)
                                    <span class="daily-bar-diff up">+{{ $y['diff'] }}</span>
                                @elseif($y['diff'] < 0)
                                    <span class="daily-bar-diff down">{{ $y['diff'] }}</span>
                                @else
                                    <span class="daily-bar-diff flat">±0</span>
                                @endif
                                <span class="daily-bar-count">{{ $y['count'] }}</span>
                                <div class="daily-bar" style="height: {{ $height }}px;">
                                    <div class="daily-tooltip">{{ $tip }}</div>
                                </div>
                                <span class="daily-bar-date">{{ $y['short'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Google口コミ詳細 --}}
<div class="detail-panel" id="panel-google">
    <div class="charts-grid">
        <div class="chart-card">
            <h3>⭐ Google評価分布</h3>
            <div class="rating-bars">
                @for ($i = 5; $i >= 1; $i--)
                    @php $pct = $gTotalReviews > 0 ? ($gRatingCounts[$i] / $gTotalReviews * 100) : 0; @endphp
                    <div class="rating-bar-row">
                        <span class="rating-bar-label">{{ $i }}★</span>
                        <div class="rating-bar-track">
                            <div class="rating-bar-fill star-{{ $i }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="rating-bar-count">{{ $gRatingCounts[$i] }}件</span>
                    </div>
                @endfor
            </div>
        </div>

        <div class="chart-card">
            <h3>📋 返信状況</h3>
            <div class="status-list">
                <div class="status-item">
                    <span class="status-name">💬 返信済み</span>
                    <span class="status-count">{{ $gRepliedCount }}件</span>
                </div>
                <div class="status-item">
                    <span class="status-name">⏳ 未返信</span>
                    <span class="status-count">{{ $gTotalReviews - $gRepliedCount }}件</span>
                </div>
            </div>
        </div>

        <div class="chart-card full-width-chart">
            <div class="chart-card-header">
                <h3>📈 Google口コミ推移</h3>
                <div class="period-tabs">
                    <button class="period-tab active" onclick="switchPeriod('google','day',this)">日別</button>
                    <button class="period-tab" onclick="switchPeriod('google','month',this)">月別</button>
                    <button class="period-tab" onclick="switchPeriod('google','year',this)">年別</button>
                </div>
            </div>

            {{-- 日別 --}}
            <div class="period-panel active" id="period-google-day">
                @if($gDailyReviews->count() > 0)
                    @php $gMaxCount = $gDailyReviews->max('count') ?: 1; @endphp
                    <div class="daily-chart">
                        <div class="daily-bars">
                            @foreach($gDailyReviews as $day)
                                @php $height = max(4, ($day->count / $gMaxCount) * 140); @endphp
                                <div class="daily-bar-wrapper">
                                    <span class="daily-bar-count">{{ $day->count }}</span>
                                    <div class="daily-bar" style="height: {{ $height }}px; background: linear-gradient(180deg, #10b981, #059669);">
                                        <div class="daily-tooltip">
                                            {{ \Carbon\Carbon::parse($day->date)->format('n/j') }}：{{ $day->count }}件（平均{{ number_format($day->avg_rating, 1) }}★）
                                        </div>
                                    </div>
                                    <span class="daily-bar-date">{{ \Carbon\Carbon::parse($day->date)->format('n/j') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p style="text-align:center;color:#aaa;padding:40px 0;">直近30日のGoogle口コミデータがありません</p>
                @endif
            </div>

            {{-- 月別 --}}
            <div class="period-panel" id="period-google-month">
                @if(!empty($gAvailableYears))
                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-bottom:10px;font-size:0.85rem;">
                        <label for="googleYearSelect" style="color:#374151;">表示年:</label>
                        <select id="googleYearSelect" onchange="onGoogleYearChange(this.value)"
                            style="padding:4px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;font-size:0.85rem;">
                            <option value="" {{ $gSelectedYear === null ? 'selected' : '' }}>直近12ヶ月</option>
                            @foreach($gAvailableYears as $y)
                                <option value="{{ $y }}" {{ $gSelectedYear === $y ? 'selected' : '' }}>{{ $y }}年(1〜12月)</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @php $gMonthMax = collect($gMonthlyReviews)->max('count') ?: 1; @endphp
                <div class="daily-chart">
                    <div class="daily-bars">
                        @foreach($gMonthlyReviews as $m)
                            @php
                                $height = max(4, ($m['count'] / $gMonthMax) * 140);
                                $tip = $m['label'] . '：' . $m['count'] . '件';
                                if ($m['avg_rating'] !== null) {
                                    $tip .= '（平均' . number_format($m['avg_rating'], 1) . '★）';
                                }
                                if ($m['diff'] !== null) {
                                    $tip .= ' / 前月比 ' . ($m['diff'] >= 0 ? '+' : '') . $m['diff'] . '件';
                                }
                            @endphp
                            <div class="daily-bar-wrapper">
                                @if($m['diff'] === null)
                                    <span class="daily-bar-diff flat">―</span>
                                @elseif($m['diff'] > 0)
                                    <span class="daily-bar-diff up">+{{ $m['diff'] }}</span>
                                @elseif($m['diff'] < 0)
                                    <span class="daily-bar-diff down">{{ $m['diff'] }}</span>
                                @else
                                    <span class="daily-bar-diff flat">±0</span>
                                @endif
                                <span class="daily-bar-count" style="color:#059669;">{{ $m['count'] }}</span>
                                <div class="daily-bar" style="height: {{ $height }}px; background: linear-gradient(180deg, #10b981, #059669);">
                                    <div class="daily-tooltip">{{ $tip }}</div>
                                </div>
                                <span class="daily-bar-date">{{ $m['short'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 年別 --}}
            <div class="period-panel" id="period-google-year">
                @php $gYearMax = collect($gYearlyReviews)->max('count') ?: 1; @endphp
                <div class="daily-chart">
                    <div class="daily-bars">
                        @foreach($gYearlyReviews as $y)
                            @php
                                $height = max(4, ($y['count'] / $gYearMax) * 140);
                                $tip = $y['label'] . '：' . $y['count'] . '件';
                                if ($y['avg_rating'] !== null) {
                                    $tip .= '（平均' . number_format($y['avg_rating'], 1) . '★）';
                                }
                                if ($y['diff'] !== null) {
                                    $tip .= ' / 前年比 ' . ($y['diff'] >= 0 ? '+' : '') . $y['diff'] . '件';
                                }
                            @endphp
                            <div class="daily-bar-wrapper">
                                @if($y['diff'] === null)
                                    <span class="daily-bar-diff flat">―</span>
                                @elseif($y['diff'] > 0)
                                    <span class="daily-bar-diff up">+{{ $y['diff'] }}</span>
                                @elseif($y['diff'] < 0)
                                    <span class="daily-bar-diff down">{{ $y['diff'] }}</span>
                                @else
                                    <span class="daily-bar-diff flat">±0</span>
                                @endif
                                <span class="daily-bar-count" style="color:#059669;">{{ $y['count'] }}</span>
                                <div class="daily-bar" style="height: {{ $height }}px; background: linear-gradient(180deg, #10b981, #059669);">
                                    <div class="daily-tooltip">{{ $tip }}</div>
                                </div>
                                <span class="daily-bar-date">{{ $y['short'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.detail-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.detail-panel').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
}

function switchPeriod(group, period, btn) {
    var tabs = btn.parentElement.querySelectorAll('.period-tab');
    tabs.forEach(function(t) { t.classList.remove('active'); });
    btn.classList.add('active');
    ['day','month','year'].forEach(function(p) {
        var el = document.getElementById('period-' + group + '-' + p);
        if (el) el.classList.remove('active');
    });
    var target = document.getElementById('period-' + group + '-' + period);
    if (target) target.classList.add('active');
}

// Google口コミ月別グラフの年度切替(URL クエリで再読み込み)
function onGoogleYearChange(year) {
    var url = new URL(window.location.href);
    if (year) {
        url.searchParams.set('google_year', year);
    } else {
        url.searchParams.delete('google_year');
    }
    // タブが「月別」のままになるよう hash で誘導(任意)
    url.hash = 'period-google-month';
    window.location.href = url.toString();
}
</script>
@endpush
@endsection
