@extends('layouts.super-admin')

@section('title', '新規テナント追加')

@section('content')
<div class="page-header">
    <h1>🏢 新規テナント追加</h1>
    <a href="{{ url('/super-admin/tenants') }}" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<div class="card" style="background:#dbeafe;border-left:4px solid #3b82f6;margin-bottom:20px;">
    <div class="card-body">
        <h3 style="margin-top:0;color:#1e40af;">📋 事前準備（ロリポップ管理画面で済ませる作業）</h3>
        <ol style="margin:0;padding-left:20px;color:#1e3a8a;font-size:0.9rem;line-height:1.8;">
            <li><strong>サブドメイン作成</strong>（ハイスピードプランでワイルドカード設定済みなら不要）<br>
                例: <code>kakaku.review.assist-grp.net</code> → 公開フォルダ <code>/qr-review/public</code></li>
            <li><strong>データベース作成</strong><br>
                例: <code>LAA1386365-kakaku</code> → このフォームの「データベース名」欄に入力</li>
        </ol>
        <p style="margin:10px 0 0;font-size:0.82rem;color:#1e40af;">
            ✨ 上記が済んでいれば、このフォームの「テナントを追加」ボタン 1 つで以下が自動実行されます：<br>
            DB 接続テスト → master DB 登録 → マイグレーション → 業種・口コミテーマ シード → 初期管理者作成 → ログイン情報表示
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ url('/super-admin/tenants') }}">
            @csrf

            <div class="two-col">
                <div class="form-group">
                    <label for="company_name">会社名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                    @error('company_name') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="subdomain">サブドメイン <span style="color:#ef4444">*</span></label>
                    <input type="text" id="subdomain" name="subdomain" value="{{ old('subdomain') }}" required placeholder="例: company-a">
                    <p class="form-hint">半角英数字とハイフンのみ。例: company-a → company-a.qr-review.jp</p>
                    @error('subdomain') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="db_name">データベース名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="db_name" name="db_name" value="{{ old('db_name') }}" required placeholder="例: qr_company_a">
                    <p class="form-hint">ロリポップで作成したDB名を入力</p>
                    @error('db_name') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="plan">プラン <span style="color:#ef4444">*</span></label>
                    <select id="plan" name="plan" required>
                        <option value="light" {{ old('plan') === 'light' ? 'selected' : '' }}>ライト（AI 50回/月）</option>
                        <option value="standard" {{ old('plan', 'standard') === 'standard' ? 'selected' : '' }}>スタンダード（AI 200回/月）</option>
                        <option value="pro" {{ old('plan') === 'pro' ? 'selected' : '' }}>プロ（AI 無制限）</option>
                    </select>
                    @error('plan') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="db_username">DBユーザー名</label>
                    <input type="text" id="db_username" name="db_username" value="{{ old('db_username') }}">
                    <p class="form-hint">空欄ならデフォルトのDB認証情報を使用</p>
                </div>
                <div class="form-group">
                    <label for="db_password">DBパスワード</label>
                    <input type="password" id="db_password" name="db_password" value="{{ old('db_password') }}">
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="contact_email">連絡先メール <span style="color:#ef4444">*</span></label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email') }}" required>
                    @error('contact_email') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="contact_name">担当者名</label>
                    <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}">
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="contract_start">契約開始日</label>
                    <input type="date" id="contract_start" name="contract_start" value="{{ old('contract_start') }}">
                </div>
                <div class="form-group">
                    <label for="contract_end">契約終了日</label>
                    <input type="date" id="contract_end" name="contract_end" value="{{ old('contract_end') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="ai_monthly_limit">AI月間利用上限（カスタム）</label>
                <input type="number" id="ai_monthly_limit" name="ai_monthly_limit" value="{{ old('ai_monthly_limit') }}" min="0">
                <p class="form-hint">空欄ならプランのデフォルト値を使用</p>
            </div>

            <h3 style="margin-top:24px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">💴 料金設定</h3>
            <div class="two-col">
                <div class="form-group">
                    <label for="monthly_fee_per_store">店舗あたりの月額料金（円）</label>
                    <input type="number" id="monthly_fee_per_store" name="monthly_fee_per_store" value="{{ old('monthly_fee_per_store', 11000) }}" min="0" step="100">
                    <p class="form-hint">デフォルト: 11,000 円。割引等で変更可</p>
                </div>
                <div class="form-group">
                    <label for="monthly_fee_override">固定料金（カスタム）</label>
                    <input type="number" id="monthly_fee_override" name="monthly_fee_override" value="{{ old('monthly_fee_override') }}" min="0" step="100" placeholder="空欄で店舗数 × 単価を自動計算">
                    <p class="form-hint">指定すると店舗数に関係なくこの金額が請求される</p>
                </div>
            </div>

            <h3 style="margin-top:24px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">📮 請求先情報（任意）</h3>
            <div class="form-group">
                <label for="billing_company_name">請求書宛名</label>
                <input type="text" id="billing_company_name" name="billing_company_name" value="{{ old('billing_company_name') }}" placeholder="会社名と異なる場合のみ入力">
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label for="billing_postal_code">郵便番号</label>
                    <input type="text" id="billing_postal_code" name="billing_postal_code" value="{{ old('billing_postal_code') }}" placeholder="例: 890-0053" maxlength="10">
                </div>
                <div class="form-group">
                    <label for="billing_address">住所</label>
                    <input type="text" id="billing_address" name="billing_address" value="{{ old('billing_address') }}" placeholder="例: 鹿児島県鹿児島市中央町20">
                </div>
            </div>

            <div class="form-group">
                <label for="admin_password">初期管理者パスワード</label>
                <input type="text" id="admin_password" name="admin_password" value="{{ old('admin_password') }}" placeholder="空欄で自動生成（推奨）">
                <p class="form-hint">8 文字以上。空欄で自動生成（次の画面で表示）</p>
                @error('admin_password') <p style="color:#ef4444;font-size:0.8rem;">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
                <label for="notes">備考</label>
                <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-size:1rem;background:linear-gradient(135deg,#10b981 0%,#059669 100%);">
                ✨ テナントを追加（自動セットアップ）
            </button>
        </form>
    </div>
</div>
@endsection
