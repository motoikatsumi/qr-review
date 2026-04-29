@extends('layouts.super-admin')

@section('title', 'ダッシュボード')

@section('content')
<div class="page-header">
    <h1>📊 ダッシュボード</h1>
</div>

{{-- 統計カード --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#c2410c;">{{ $totalTenants }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">総テナント数</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#16a34a;">{{ $activeTenants }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">有効テナント</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#dc2626;">{{ $stoppedTenants }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">停止テナント</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#7c3aed;">{{ $totalStores }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">総店舗数</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;">
            <div style="font-size:2rem;font-weight:700;color:#2563eb;">{{ $totalAiUsage }}</div>
            <div style="font-size:0.85rem;color:#6b7280;">AI利用合計（今月）</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    {{-- AI利用ランキング --}}
    <div class="card">
        <div class="card-header">🤖 AI利用状況（今月）</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>テナント</th>
                        <th>利用 / 上限</th>
                        <th>利用率</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aiRankings as $rank)
                    <tr>
                        <td>{{ $rank['tenant']->company_name }}</td>
                        <td>{{ $rank['used'] }} / {{ $rank['limit'] }}</td>
                        <td>
                            <div style="background:#e5e7eb;border-radius:9999px;height:8px;width:100px;display:inline-block;vertical-align:middle;">
                                <div style="background:{{ $rank['pct'] >= 90 ? '#dc2626' : ($rank['pct'] >= 70 ? '#f59e0b' : '#16a34a') }};height:8px;border-radius:9999px;width:{{ min($rank['pct'], 100) }}px;"></div>
                            </div>
                            <span style="font-size:0.8rem;margin-left:4px;">{{ $rank['pct'] }}%</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align:center;padding:20px;color:#6b7280;">データなし</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 契約まもなく終了 --}}
    <div class="card">
        <div class="card-header">⏰ 契約終了まもなく（30日以内）</div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>テナント</th>
                        <th>プラン</th>
                        <th>契約終了日</th>
                        <th>残日数</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expiringSoon as $tenant)
                    <tr>
                        <td>
                            <a href="{{ url('/super-admin/tenants/' . $tenant->id . '/edit') }}" style="color:#c2410c;text-decoration:none;">
                                {{ $tenant->company_name }}
                            </a>
                        </td>
                        <td>
                            @if($tenant->plan === 'light')
                                <span class="badge badge-blue">ライト</span>
                            @elseif($tenant->plan === 'standard')
                                <span class="badge badge-green">スタンダード</span>
                            @else
                                <span class="badge badge-yellow">プロ</span>
                            @endif
                        </td>
                        <td>{{ $tenant->contract_end->format('Y/m/d') }}</td>
                        <td>
                            @php $days = now()->diffInDays($tenant->contract_end, false); @endphp
                            <span style="color:{{ $days <= 7 ? '#dc2626' : '#f59e0b' }};font-weight:600;">
                                {{ $days }}日
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:#6b7280;">該当なし</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- プラン別分布 --}}
<div class="card" style="margin-top:20px;">
    <div class="card-header">📋 プラン別テナント数</div>
    <div class="card-body">
        <div style="display:flex;gap:32px;">
            <div>
                <span class="badge badge-blue" style="font-size:0.9rem;padding:4px 12px;">ライト</span>
                <span style="font-size:1.2rem;font-weight:600;margin-left:8px;">{{ $planCounts['light'] ?? 0 }}</span> 社
            </div>
            <div>
                <span class="badge badge-green" style="font-size:0.9rem;padding:4px 12px;">スタンダード</span>
                <span style="font-size:1.2rem;font-weight:600;margin-left:8px;">{{ $planCounts['standard'] ?? 0 }}</span> 社
            </div>
            <div>
                <span class="badge badge-yellow" style="font-size:0.9rem;padding:4px 12px;">プロ</span>
                <span style="font-size:1.2rem;font-weight:600;margin-left:8px;">{{ $planCounts['pro'] ?? 0 }}</span> 社
            </div>
        </div>
    </div>
</div>
@endsection
