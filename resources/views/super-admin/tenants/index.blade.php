@extends('layouts.super-admin')

@section('title', 'テナント一覧')

@section('content')
<style>
    .store-cell { position: relative; }
    .store-tooltip {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        z-index: 10;
        background: #1f2937;
        color: #fff;
        font-size: 0.78rem;
        padding: 8px 12px;
        border-radius: 6px;
        white-space: pre-line;
        line-height: 1.6;
        min-width: 160px;
        max-width: 280px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        pointer-events: none;
    }
    .store-cell:hover .store-tooltip { display: block; }

    /* テナント一覧 専用：横スクロール + 折返し抑止 */
    .tenant-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .tenant-table {
        min-width: 1100px;
        white-space: nowrap;
    }
    .tenant-table th, .tenant-table td {
        padding: 8px 10px !important;
        font-size: 0.83rem;
        vertical-align: middle;
    }
    .tenant-table .actions-cell {
        white-space: nowrap;
    }
    .tenant-table .actions-cell .btn {
        padding: 4px 8px;
        font-size: 0.72rem;
        margin-right: 2px;
    }
    .tenant-table .badge {
        white-space: nowrap;
        font-size: 0.72rem;
        padding: 2px 8px;
    }
</style>
@php
    $activeTenants = $tenants->filter(fn($t) => $t->is_active);
    $totalActive = $activeTenants->count();
    $totalStores = $activeTenants->sum(fn($t) => is_numeric($t->store_count) ? (int) $t->store_count : 0);
    $totalMonthly = $activeTenants->sum(fn($t) => $t->calculateMonthlyFee(is_numeric($t->store_count) ? (int) $t->store_count : 0));
@endphp
<div class="page-header">
    <h1>🏢 テナント一覧</h1>
    <a href="{{ url('/super-admin/tenants/create') }}" class="btn btn-primary">＋ 新規テナント追加</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:14px;margin-bottom:20px;">
    <div style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:18px 22px;border-radius:12px;">
        <div style="font-size:0.78rem;opacity:0.85;">有効テナント</div>
        <div style="font-size:1.6rem;font-weight:700;margin-top:4px;">{{ $totalActive }}<span style="font-size:0.9rem;font-weight:400;margin-left:4px;">社</span></div>
    </div>
    <div style="background:linear-gradient(135deg,#10b981,#059669);color:white;padding:18px 22px;border-radius:12px;">
        <div style="font-size:0.78rem;opacity:0.85;">総店舗数</div>
        <div style="font-size:1.6rem;font-weight:700;margin-top:4px;">{{ $totalStores }}<span style="font-size:0.9rem;font-weight:400;margin-left:4px;">店舗</span></div>
    </div>
    <div style="background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:18px 22px;border-radius:12px;">
        <div style="font-size:0.78rem;opacity:0.85;">月額売上見込み（税抜）</div>
        <div style="font-size:1.6rem;font-weight:700;margin-top:4px;">¥{{ number_format($totalMonthly) }}</div>
    </div>
    <a href="{{ url('/super-admin/invoices') }}" style="text-decoration:none;background:white;border:2px solid #6366f1;color:#1e1b4b;padding:18px 22px;border-radius:12px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;">
        <div style="font-size:1rem;font-weight:600;">📄 請求書管理へ</div>
        <div style="font-size:0.78rem;color:#6b7280;margin-top:4px;">月次請求書の発行・管理</div>
    </a>
</div>

<div class="card">
    <div class="card-body tenant-table-wrap" style="padding:0;">
        <table class="tenant-table">
            <thead>
                <tr>
                    <th>会社名</th>
                    <th>サブドメイン</th>
                    <th>プラン</th>
                    <th>店舗</th>
                    <th style="text-align:right;">月額料金</th>
                    <th>AI利用（今月）</th>
                    <th>状態</th>
                    <th>契約開始</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                <tr>
                    <td><strong>{{ $tenant->company_name }}</strong></td>
                    <td><code>{{ $tenant->subdomain }}</code></td>
                    <td>
                        @if($tenant->plan === 'light')
                            <span class="badge badge-blue">ライト</span>
                        @elseif($tenant->plan === 'standard')
                            <span class="badge badge-green">スタンダード</span>
                        @elseif($tenant->plan === 'pro')
                            <span class="badge badge-yellow">プロ</span>
                        @endif
                    </td>
                    <td>
                        <span style="cursor:default;position:relative;" class="store-cell">
                            <strong>{{ $tenant->store_count ?? '-' }}</strong>店舗
                            @if(!empty($tenant->store_names))
                                <span style="color:#9ca3af;font-size:0.8rem;margin-left:2px;">ℹ️</span>
                                <span class="store-tooltip">{{ implode("\n", $tenant->store_names) }}</span>
                            @endif
                        </span>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        @php
                            $storeCount = is_numeric($tenant->store_count) ? (int) $tenant->store_count : 0;
                            $monthlyFee = $tenant->calculateMonthlyFee($storeCount);
                            $perStore = (int) ($tenant->monthly_fee_per_store ?? 11000);
                            $isOverride = !is_null($tenant->monthly_fee_override) && $tenant->monthly_fee_override > 0;
                        @endphp
                        <strong style="font-size:0.88rem;">¥{{ number_format($monthlyFee) }}</strong>
                        <div style="font-size:0.7rem;color:#9ca3af;">
                            @if($isOverride)
                                固定
                            @else
                                {{ number_format($perStore) }}×{{ $storeCount }}
                            @endif
                        </div>
                    </td>
                    <td>
                        {{ $tenant->ai_used_this_month ?? '-' }} / {{ $tenant->ai_monthly_limit }}
                    </td>
                    <td>
                        @if($tenant->is_active)
                            <span class="badge badge-green">有効</span>
                        @else
                            <span class="badge badge-red">停止</span>
                        @endif
                    </td>
                    <td>{{ $tenant->contract_start?->format('Y/m/d') ?? '-' }}</td>
                    <td class="actions-cell">
                        <a href="{{ url('/super-admin/tenants/' . $tenant->id . '/edit') }}" class="btn btn-secondary">編集</a>
                        <a href="{{ url('/super-admin/tenants/' . $tenant->id . '/ai-usage') }}" class="btn btn-secondary">AI</a>
                        <a href="{{ url('/super-admin/invoices?tenant=' . $tenant->id) }}" class="btn btn-secondary">請求書</a>
                        @if($tenant->is_active)
                        <form method="POST" action="{{ url('/super-admin/tenants/' . $tenant->id . '/impersonate') }}" style="display:inline;"
                              onsubmit="return confirm('{{ $tenant->company_name }} に代理ログインしますか？')">
                            @csrf
                            <button type="submit" class="btn" style="background:#2563eb;color:white;padding:4px 8px;font-size:0.72rem;">代理ログイン</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:30px;color:#6b7280;">テナントがまだ登録されていません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
