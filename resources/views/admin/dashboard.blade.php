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
        font-size: 0.72rem;
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
        font-size: 0.8rem;
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
        font-size: 0.8rem;
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
    }
    .daily-bar-count {
        font-size: 0.65rem;
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
        font-size: 0.6rem;
        color: #aaa;
        margin-top: 4px;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        height: 36px;
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
        font-size: 0.7rem;
        white-space: nowrap;
        z-index: 10;
    }
    .daily-bar:hover .daily-tooltip {
        display: block;
    }
    .full-width-chart {
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .summary-compare { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .charts-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>📊 統計ダッシュボード</h1>
</div>

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
    </form>
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
            <h3>📈 直近30日の口コミ推移</h3>
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
            <h3>📈 Google口コミ 直近30日の推移</h3>
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
</script>
@endpush
@endsection
