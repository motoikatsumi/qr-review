@extends('layouts.super-admin')

@section('title', 'テナント作成完了')

@section('content')
<div class="page-header">
    <h1>🎉 テナント作成完了</h1>
</div>

<div class="card" style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;margin-bottom:20px;">
    <div class="card-body" style="padding:24px;">
        <h2 style="margin:0 0 8px;font-size:1.4rem;">{{ $tenant->company_name }} を作成しました</h2>
        <p style="margin:0;opacity:0.95;">DB 接続テスト → master DB 登録 → マイグレーション → シード → 管理者作成までを自動実行しました。</p>
    </div>
</div>

@if(!empty($errors))
<div class="card" style="background:#fef3c7;border-left:5px solid #f59e0b;margin-bottom:20px;">
    <div class="card-body">
        <strong style="color:#78350f;">⚠️ 一部の処理で警告がありました（手動対応推奨）：</strong>
        <ul style="margin:8px 0 0 20px;color:#78350f;">
            @foreach($errors as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="card" style="border:2px solid #fbbf24;background:#fffbeb;">
    <div class="card-body">
        <h3 style="margin:0 0 14px;color:#78350f;">⚠️ 重要：以下の情報を顧客に伝えてください</h3>
        <p style="font-size:0.85rem;color:#78350f;margin:0 0 14px;">
            <strong>パスワードはこの画面でしか表示されません。</strong>必ずこの場でコピーして顧客にお伝えください。
            ページ離脱後の再表示は不可です（その場合はパスワードリセットを使ってください）。
        </p>

        <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:10px 14px;background:white;border:1px solid #fde68a;border-radius:6px;">
                <div style="font-weight:600;min-width:120px;font-size:0.85rem;">📍 ログイン URL</div>
                <code id="loginUrl" style="font-size:0.82rem;word-break:break-all;flex:1;min-width:200px;">{{ $login_url }}</code>
                <button onclick="copyToClipboard('loginUrl', this)" type="button" class="btn btn-sm btn-secondary">📋 コピー</button>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:10px 14px;background:white;border:1px solid #fde68a;border-radius:6px;">
                <div style="font-weight:600;min-width:120px;font-size:0.85rem;">📧 メールアドレス</div>
                <code id="loginEmail" style="font-size:0.92rem;word-break:break-all;flex:1;min-width:200px;">{{ $admin_email }}</code>
                <button onclick="copyToClipboard('loginEmail', this)" type="button" class="btn btn-sm btn-secondary">📋 コピー</button>
            </div>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:10px 14px;background:white;border:1px solid #fde68a;border-radius:6px;">
                <div style="font-weight:600;min-width:120px;font-size:0.85rem;">🔑 初期パスワード</div>
                <code id="loginPassword" style="font-size:0.95rem;font-weight:600;background:#fef3c7;padding:4px 10px;border-radius:4px;flex:1;min-width:200px;word-break:break-all;">{{ $admin_password }}</code>
                <button onclick="copyToClipboard('loginPassword', this)" type="button" class="btn btn-sm btn-secondary">📋 コピー</button>
            </div>
        </div>

        <div style="margin-top:20px;padding:14px 18px;background:#dbeafe;border-radius:8px;border-left:4px solid #3b82f6;">
            <strong style="color:#1e40af;">📤 顧客にお送りするテンプレート文（コピペ用）</strong>
            <textarea id="emailTemplate" rows="10" style="width:100%;margin-top:8px;padding:10px;border:1px solid #93c5fd;border-radius:6px;font-family:inherit;font-size:0.85rem;background:white;">
{{ $tenant->contact_name ?: $tenant->company_name }} 様

QRレビューのアカウントを発行いたしました。
以下の情報でログインしてください。

【ログイン URL】
{{ $login_url }}

【メールアドレス】
{{ $admin_email }}

【初期パスワード】
{{ $admin_password }}

ログイン後、店舗情報・連携設定はお問い合わせください。

セキュリティのため、初回ログイン後にパスワードを変更してください。
ご不明な点があればお問い合わせください。
</textarea>
            <button onclick="copyToClipboard('emailTemplate', this)" type="button" class="btn btn-primary btn-sm" style="margin-top:8px;">📋 テンプレート全文をコピー</button>
        </div>
    </div>
</div>

<div style="margin-top:20px;display:flex;gap:8px;">
    <a href="/super-admin/tenants" class="btn btn-secondary">← 一覧に戻る</a>
    <form method="POST" action="/super-admin/tenants/{{ $tenant->id }}/impersonate" style="display:inline;">
        @csrf
        <button type="submit" class="btn btn-primary">🔓 このテナントとしてログインして確認</button>
    </form>
</div>

<script>
function copyToClipboard(elementId, btn) {
    const el = document.getElementById(elementId);
    const text = el.tagName === 'TEXTAREA' ? el.value : el.textContent;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✅ コピー済み';
        btn.disabled = true;
        setTimeout(() => {
            btn.textContent = orig;
            btn.disabled = false;
        }, 1500);
    });
}
</script>
@endsection
