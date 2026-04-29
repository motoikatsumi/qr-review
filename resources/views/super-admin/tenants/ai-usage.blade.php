@extends('layouts.super-admin')

@section('title', 'AI利用ログ - ' . $tenant->company_name)

@section('content')
<div class="page-header">
    <h1>🤖 AI利用ログ: {{ $tenant->company_name }}</h1>
    <a href="{{ url('/super-admin/tenants/' . $tenant->id . '/edit') }}" class="btn btn-secondary">← テナント編集に戻る</a>
</div>

{{-- 今月のサマリー --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#c2410c;">{{ $monthlyTotal }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">今月の利用回数</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#2563eb;">{{ $tenant->ai_monthly_limit }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">月間上限</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;">
            @php $pct = $tenant->ai_monthly_limit > 0 ? round($monthlyTotal / $tenant->ai_monthly_limit * 100) : 0; @endphp
            <div style="font-size:2rem;font-weight:700;color:{{ $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#f59e0b' : '#16a34a') }};">{{ $pct }}%</div>
            <div style="font-size:0.85rem;color:#6b7280;">利用率</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;">
    {{-- 日別推移（過去30日） --}}
    <div class="card">
        <div class="card-header">📈 日別利用数（過去30日）</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>回数</th>
                        <th>グラフ</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxDaily = $dailyUsage->max('count') ?: 1; @endphp
                    @forelse($dailyUsage as $day)
                    <tr>
                        <td>{{ $day->date }}</td>
                        <td>{{ $day->count }}</td>
                        <td>
                            <div style="background:#dbeafe;border-radius:4px;height:16px;width:{{ round($day->count / $maxDaily * 200) }}px;min-width:4px;"></div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center;padding:20px;color:#6b7280;">データなし</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- アクション別 --}}
    <div class="card">
        <div class="card-header">🏷️ アクション別（今月）</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>アクション</th>
                        <th>回数</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($actionUsage as $action)
                    <tr>
                        <td>{{ $action->action }}</td>
                        <td><strong>{{ $action->count }}</strong></td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="text-align:center;padding:20px;color:#6b7280;">データなし</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- 最近のログ --}}
<div class="card" style="margin-top:20px;">
    <div class="card-header">📋 最近のログ（直近50件）</div>
    <div class="card-body" style="padding:0;">
        <table>
            <thead>
                <tr>
                    <th>日時</th>
                    <th>アクション</th>
                    <th>店舗ID</th>
                    <th>ユーザーID</th>
                    <th>トークン数</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentLogs as $log)
                <tr>
                    <td style="font-size:0.8rem;">{{ $log->created_at }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->store_id ?? '-' }}</td>
                    <td>{{ $log->user_id ?? '-' }}</td>
                    <td>{{ $log->tokens_used ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:20px;color:#6b7280;">ログなし</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
