@extends('layouts.admin')

@section('title', $store->name . ' - 外部連携設定')

@section('content')
<div class="page-header">
    <h1>🔗 外部連携設定 — {{ $store->name }}</h1>
    <a href="/admin/stores/{{ $store->id }}/edit" class="btn btn-secondary">← 店舗設定に戻る</a>
</div>

@push('styles')
<style>
    .integration-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
    .integration-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
    .integration-header { padding: 16px 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #e5e7eb; }
    .integration-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    .icon-instagram { background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
    .icon-facebook  { background: #1877f2; }
    .icon-wordpress { background: #21759b; }
    .icon-google    { background: #fff; border: 1px solid #e5e7eb; }
    .integration-title { font-weight: 700; font-size: 1rem; }
    .integration-body { padding: 20px; }
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; margin-bottom: 16px; }
    .status-connected { background: #d1fae5; color: #065f46; }
    .status-disconnected { background: #f3f4f6; color: #6b7280; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; }
    .dot-green { background: #10b981; }
    .dot-gray  { background: #9ca3af; }
    .field-row { margin-bottom: 14px; }
    .field-row label { display: block; font-size: 0.82rem; font-weight: 600; color: #555; margin-bottom: 5px; }
    .field-row input { width: 100%; padding: 9px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.88rem; font-family: monospace; }
    .field-row input:focus { border-color: #667eea; outline: none; }
    .action-row { display: flex; gap: 8px; margin-top: 16px; }
    .note { font-size: 0.78rem; color: #888; margin-top: 12px; line-height: 1.6; }
    a.hint-link { color: #667eea; }
    .btn-facebook { display: inline-flex; align-items: center; gap: 8px; background: #1877f2; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
    .btn-facebook:hover { background: #1464d8; color: white; text-decoration: none; }
    .connected-info { font-size: 0.85rem; color: #555; margin-bottom: 12px; line-height: 1.6; }
    .connected-info code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
</style>
@endpush

<div class="integration-grid">

    {{-- =============== Facebook + Instagram（OAuth連携） =============== --}}
    @php
        $fb = $integrations->get('facebook');
        $ig = $integrations->get('instagram');
        $metaConnectUrl = url("/meta/connect/{$store->id}") . '?' . http_build_query(['return_url' => url("/admin/stores/{$store->id}/edit?tab=integrations")]);
    @endphp

    {{-- Facebook カード --}}
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon icon-facebook" style="color:white;">f</div>
            <div>
                <div class="integration-title">Facebook</div>
                <div style="font-size:0.78rem;color:#888;">Graph API v25.0</div>
            </div>
        </div>
        <div class="integration-body">
            @if($fb && $fb->is_active)
                <div class="status-badge status-connected"><span class="status-dot dot-green"></span>連携済み</div>
                <p class="connected-info">
                    ページ名: <code>{{ $fb->extra_data['page_name'] ?? '—' }}</code><br>
                    ページID: <code>{{ $fb->extra_data['page_id'] ?? '—' }}</code>
                </p>
                <div class="action-row">
                    <a href="{{ $metaConnectUrl }}" class="btn btn-secondary btn-sm">再連携</a>
                    <form method="POST" action="{{ url("/admin/stores/{$store->id}/integrations/facebook/disconnect") }}"
                          onsubmit="return confirm('Facebook連携を解除しますか？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">連携解除</button>
                    </form>
                </div>
            @else
                <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未連携</div>
                <a href="{{ $metaConnectUrl }}" class="btn-facebook">
                    <span style="font-size:1.1rem;">📘</span> Facebookと連携する
                </a>
                <p class="note">
                    ※ Facebookアカウントでログインし、連携するページを選択します。<br>
                    ※ Instagramビジネスアカウントが紐付いていれば自動連携されます。
                </p>
            @endif
        </div>
    </div>

    {{-- Instagram カード --}}
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon icon-instagram">📸</div>
            <div>
                <div class="integration-title">Instagram</div>
                <div style="font-size:0.78rem;color:#888;">Graph API v25.0</div>
            </div>
        </div>
        <div class="integration-body">
            @if($ig && $ig->is_active)
                <div class="status-badge status-connected"><span class="status-dot dot-green"></span>連携済み</div>
                <p class="connected-info">
                    @if(!empty($ig->extra_data['ig_username']))
                        アカウント: <code>{{ '@' . $ig->extra_data['ig_username'] }}</code><br>
                    @endif
                    IG User ID: <code>{{ $ig->extra_data['ig_user_id'] ?? '—' }}</code>
                </p>
                <div class="action-row">
                    <form method="POST" action="{{ url("/admin/stores/{$store->id}/integrations/instagram/disconnect") }}"
                          onsubmit="return confirm('Instagram連携を解除しますか？')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm">連携解除</button>
                    </form>
                </div>
            @else
                <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未連携</div>
                @if(!$fb || !$fb->is_active)
                    <a href="{{ $metaConnectUrl }}" class="btn-facebook">
                        <span style="font-size:1.1rem;">📘</span> Facebookと連携する
                    </a>
                    <p class="note">
                        ※ InstagramはFacebookページ経由で連携されます。<br>
                        ※ Facebookと連携すると、紐付いたInstagramビジネスアカウントも自動で連携されます。
                    </p>
                @else
                    <p class="note">
                        Facebookは連携済みですが、Instagramビジネスアカウントが紐付いていませんでした。<br>
                        FacebookページにInstagramビジネスアカウントをリンクしてから、
                        <a href="{{ $metaConnectUrl }}" class="hint-link">再連携</a>してください。
                    </p>
                @endif
            @endif
        </div>
    </div>

    {{-- =============== WordPress =============== --}}
    @php $wp = $integrations->get('wordpress'); @endphp
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon icon-wordpress" style="color:white;font-size:1rem;font-weight:bold;">W</div>
            <div>
                <div class="integration-title">WordPress</div>
                <div style="font-size:0.78rem;color:#888;">REST API（Application Password）</div>
            </div>
        </div>
        <div class="integration-body">
            @if($wp && $wp->is_active)
                <div class="status-badge status-connected"><span class="status-dot dot-green"></span>連携済み</div>
                <p style="font-size:0.85rem;color:#555;margin-bottom:12px;">
                    URL: <code>{{ $wp->extra_data['wp_url'] ?? '—' }}</code><br>
                    ユーザー: <code>{{ $wp->extra_data['wp_username'] ?? '—' }}</code>
                </p>
                <form method="POST" action="/admin/stores/{{ $store->id }}/integrations/wordpress/disconnect"
                      onsubmit="return confirm('WordPress連携を解除しますか？\nこの操作は元に戻せません。')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">連携解除</button>
                </form>
            @else
                <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未連携</div>
                <form method="POST" action="/admin/stores/{{ $store->id }}/integrations/wordpress">
                    @csrf
                    <div class="field-row">
                        <label>WordPress サイトURL</label>
                        <input type="url" name="wp_url" value="{{ old('wp_url') }}" placeholder="https://example.com" required>
                    </div>
                    <div class="field-row">
                        <label>ユーザー名</label>
                        <input type="text" name="wp_username" value="{{ old('wp_username') }}" placeholder="admin" required>
                    </div>
                    <div class="field-row">
                        <label>アプリケーションパスワード</label>
                        <input type="text" name="wp_password" value="{{ old('wp_password') }}" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" required>
                    </div>
                    <div class="action-row">
                        <button class="btn btn-primary btn-sm">接続テスト＆保存</button>
                    </div>
                    <p class="note">
                        ※ WordPress管理画面 → ユーザー → プロフィール →「アプリケーションパスワード」で発行してください。<br>
                        スペース区切りの形式のままコピーしてOKです。
                    </p>
                </form>
            @endif
        </div>
    </div>

    {{-- =============== Google Business =============== --}}
    <div class="integration-card">
        <div class="integration-header">
            <div class="integration-icon icon-google">🌐</div>
            <div>
                <div class="integration-title">Google ビジネスプロフィール</div>
                <div style="font-size:0.78rem;color:#888;">My Business API</div>
            </div>
        </div>
        <div class="integration-body">
            @if($store->google_location_name)
                <div class="status-badge status-connected"><span class="status-dot dot-green"></span>設定済み</div>
                <p style="font-size:0.85rem;color:#555;margin-bottom:12px;">
                    ロケーション名: <code>{{ $store->google_location_name }}</code>
                </p>
            @else
                <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未設定</div>
            @endif
            <p class="note">
                Google連携はシステム全体で1つのGoogleアカウントを使用します。<br>
                OAuth設定は <a href="/admin/google-settings" class="hint-link">⚙️ Google設定</a> で行い、<br>
                この店舗のロケーション名は <a href="/admin/stores/{{ $store->id }}/edit" class="hint-link">店舗編集</a> の「ロケーション名」欄で設定してください。
            </p>
        </div>
    </div>

</div>
@endsection
