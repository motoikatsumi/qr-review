@extends('layouts.super-admin')

@section('title', 'テナント編集')

@section('content')
<div class="page-header">
    <h1>🏢 テナント編集: {{ $tenant->company_name }}</h1>
    <a href="{{ url('/super-admin/tenants') }}" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ url('/super-admin/tenants/' . $tenant->id) }}">
            @csrf
            @method('PUT')

            <div class="two-col">
                <div class="form-group">
                    <label for="company_name">会社名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $tenant->company_name) }}" required>
                    @error('company_name') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="subdomain">サブドメイン <span style="color:#ef4444">*</span></label>
                    <input type="text" id="subdomain" name="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" required>
                    @error('subdomain') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="db_name">データベース名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="db_name" name="db_name" value="{{ old('db_name', $tenant->db_name) }}" required>
                    @error('db_name') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="plan">プラン <span style="color:#ef4444">*</span></label>
                    <select id="plan" name="plan" required>
                        <option value="light" {{ old('plan', $tenant->plan) === 'light' ? 'selected' : '' }}>ライト（AI 50回/月）</option>
                        <option value="standard" {{ old('plan', $tenant->plan) === 'standard' ? 'selected' : '' }}>スタンダード（AI 200回/月）</option>
                        <option value="pro" {{ old('plan', $tenant->plan) === 'pro' ? 'selected' : '' }}>プロ（AI 無制限）</option>
                    </select>
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="db_username">DBユーザー名</label>
                    <input type="text" id="db_username" name="db_username" value="{{ old('db_username', $tenant->db_username) }}">
                </div>
                <div class="form-group">
                    <label for="db_password">DBパスワード</label>
                    <input type="password" id="db_password" name="db_password" value="{{ old('db_password', $tenant->db_password) }}">
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="contact_email">連絡先メール <span style="color:#ef4444">*</span></label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $tenant->contact_email) }}" required>
                    @error('contact_email') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="contact_name">担当者名</label>
                    <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $tenant->contact_name) }}">
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="contract_start">契約開始日</label>
                    <input type="date" id="contract_start" name="contract_start" value="{{ old('contract_start', $tenant->contract_start?->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label for="contract_end">契約終了日</label>
                    <input type="date" id="contract_end" name="contract_end" value="{{ old('contract_end', $tenant->contract_end?->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="form-group">
                <label for="ai_monthly_limit">AI月間利用上限</label>
                <input type="number" id="ai_monthly_limit" name="ai_monthly_limit" value="{{ old('ai_monthly_limit', $tenant->ai_monthly_limit) }}" min="0">
            </div>

            <h3 style="margin-top:24px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">💴 料金設定</h3>
            <div class="two-col">
                <div class="form-group">
                    <label for="monthly_fee_per_store">店舗あたりの月額料金（円）</label>
                    <input type="number" id="monthly_fee_per_store" name="monthly_fee_per_store" value="{{ old('monthly_fee_per_store', $tenant->monthly_fee_per_store ?? 11000) }}" min="0" step="100">
                    <p class="form-hint">デフォルト: 11,000 円。割引等で変更可</p>
                </div>
                <div class="form-group">
                    <label for="monthly_fee_override">固定料金（カスタム）</label>
                    <input type="number" id="monthly_fee_override" name="monthly_fee_override" value="{{ old('monthly_fee_override', $tenant->monthly_fee_override) }}" min="0" step="100" placeholder="空欄で店舗数 × 単価を自動計算">
                    <p class="form-hint">指定すると店舗数に関係なくこの金額が請求される</p>
                </div>
            </div>

            <h3 style="margin-top:24px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">📮 請求先情報（任意）</h3>
            <div class="form-group">
                <label for="billing_company_name">請求書宛名</label>
                <input type="text" id="billing_company_name" name="billing_company_name" value="{{ old('billing_company_name', $tenant->billing_company_name) }}" placeholder="会社名と異なる場合のみ入力">
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label for="billing_postal_code">郵便番号</label>
                    <input type="text" id="billing_postal_code" name="billing_postal_code" value="{{ old('billing_postal_code', $tenant->billing_postal_code) }}" placeholder="例: 890-0053" maxlength="10">
                </div>
                <div class="form-group">
                    <label for="billing_address">住所</label>
                    <input type="text" id="billing_address" name="billing_address" value="{{ old('billing_address', $tenant->billing_address) }}" placeholder="例: 鹿児島県鹿児島市中央町20">
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}>
                    テナント有効
                </label>
            </div>

            <div class="form-group">
                <label for="notes">備考</label>
                <textarea id="notes" name="notes" rows="3">{{ old('notes', $tenant->notes) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">更新</button>
        </form>
    </div>
</div>

{{-- テナント削除 --}}
<div class="card" style="border: 2px solid #fecaca; margin-top: 24px;">
    <div class="card-header" style="background:#fef2f2; color:#991b1b;">⚠️ 危険な操作</div>
    <div class="card-body">
        <p style="font-size:0.9rem;color:#666;margin-bottom:16px;">テナントを削除すると、テナント情報がマスターDBから除去されます。</p>
        <form method="POST" action="{{ url('/super-admin/tenants/' . $tenant->id) }}"
              onsubmit="return confirm('本当に {{ $tenant->company_name }} を削除しますか？\nこの操作は取り消せません。')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn" style="background:#dc2626;color:white;">テナントを削除</button>
        </form>
    </div>
</div>
@endsection
