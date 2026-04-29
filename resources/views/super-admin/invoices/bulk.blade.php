@extends('layouts.super-admin')

@section('title', '月次一括発行')

@section('content')
<div class="page-header">
    <h1>📅 月次請求書 一括発行</h1>
    <a href="/super-admin/invoices" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<form method="POST" action="/super-admin/invoices/bulk">
    @csrf

    <div class="card" style="margin-bottom:20px;">
        <div class="card-body">
            <h3 style="margin-top:0;">📅 請求対象 / 発行設定</h3>
            <div class="two-col">
                <div class="form-group">
                    <label>対象年月</label>
                    <div style="display:flex;gap:8px;">
                        <input type="number" name="period_year" value="{{ now()->year }}" min="2020" max="2100" style="width:100px;" required>
                        <span style="line-height:38px;">年</span>
                        <input type="number" name="period_month" value="{{ now()->month }}" min="1" max="12" style="width:70px;" required>
                        <span style="line-height:38px;">月</span>
                    </div>
                    <p class="form-hint">この月の利用料として請求します</p>
                </div>
                <div class="form-group">
                    <label>発行日</label>
                    <input type="date" name="issue_date" value="{{ now()->endOfMonth()->toDateString() }}" required>
                    <p class="form-hint">通常は対象月の月末日</p>
                </div>
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label>支払期限（発行日からの日数）</label>
                    <input type="number" name="due_days" value="30" min="0" max="90" required>
                    <p class="form-hint">発行日 + N日 が支払期限</p>
                </div>
                <div class="form-group">
                    <label>消費税率（％）</label>
                    <input type="number" name="tax_rate" value="10" min="0" max="100" step="0.01" required>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 style="margin-top:0;">🏢 発行対象テナント</h3>
            <p style="font-size:0.85rem;color:#6b7280;margin-bottom:14px;">
                既に同じ月の請求書が発行済みのテナントは自動でスキップされます（重複防止）。
            </p>

            <style>
                .bulk-tenant-table-wrap { overflow-x: auto; }
                .bulk-tenant-table { min-width: 700px; }
                .bulk-tenant-table th, .bulk-tenant-table td {
                    padding: 8px 10px;
                    font-size: 0.83rem;
                    vertical-align: middle;
                    white-space: nowrap;
                }
                .bulk-tenant-table .col-name { white-space: normal; }
            </style>

            <div class="bulk-tenant-table-wrap">
            <table class="bulk-tenant-table">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="checkAll" checked onchange="document.querySelectorAll('input[name=&quot;tenant_ids[]&quot;]').forEach(c => c.checked = !c.disabled && this.checked)"></th>
                        <th class="col-name">会社名</th>
                        <th>店舗数</th>
                        <th style="text-align:right;">請求予定額（税抜）</th>
                        <th>状態</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $t)
                    <tr style="{{ $t->_already_invoiced ? 'opacity:0.5;background:#f9fafb;' : '' }}">
                        <td>
                            <input type="checkbox" name="tenant_ids[]" value="{{ $t->id }}" {{ $t->_already_invoiced ? 'disabled' : 'checked' }}>
                        </td>
                        <td>
                            <strong>{{ $t->company_name }}</strong><br>
                            <code style="font-size:0.78rem;color:#6b7280;">{{ $t->subdomain }}</code>
                        </td>
                        <td>{{ $t->_store_count }} 店舗</td>
                        <td style="text-align:right;font-weight:600;">¥{{ number_format($t->_monthly_fee) }}</td>
                        <td>
                            @if($t->_already_invoiced)
                            <span style="background:#9ca3af;color:white;padding:3px 10px;border-radius:12px;font-size:0.78rem;">⚠️ 今月発行済み</span>
                            @else
                            <span style="background:#10b981;color:white;padding:3px 10px;border-radius:12px;font-size:0.78rem;">✅ 発行可</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;color:#6b7280;white-space:normal;">有効なテナントがありません</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            @php
                $totalEligible = $tenants->where('_already_invoiced', false)->sum('_monthly_fee');
            @endphp
            <div style="margin-top:14px;padding:14px 18px;background:#dbeafe;border-radius:8px;text-align:right;">
                <strong>発行可能テナントの合計（税抜）: ¥{{ number_format($totalEligible) }}</strong>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;text-align:center;">
        <button type="submit" class="btn btn-primary" style="padding:14px 36px;font-size:1rem;background:linear-gradient(135deg,#10b981,#059669);"
                onclick="return confirm('選択したテナントに対して一括で請求書を発行します。よろしいですか？')">
            ✨ 選択したテナントの請求書を一括発行
        </button>
    </div>
</form>
@endsection
