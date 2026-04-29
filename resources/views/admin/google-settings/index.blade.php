@extends('layouts.admin')
@section('title', 'Google連携設定')

@section('content')
<div class="page-header">
    <h1>⚙️ Google連携設定</h1>
</div>

{{-- ステップ1: API認証情報 --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">1. Google Cloud API認証情報</div>
    <div class="card-body">
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 16px;">
            Google Cloud Consoleで「Business Profile API」を有効化し、OAuthクライアントIDを作成してください。<br>
            リダイレクトURIには <code style="background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ url('/admin/google-settings/callback') }}</code> を設定してください。
        </p>
        <form method="POST" action="/admin/google-settings/credentials">
            @csrf
            <div class="form-group">
                <label>クライアントID</label>
                <input type="text" name="client_id" value="{{ $settings['client_id'] }}" placeholder="xxxx.apps.googleusercontent.com">
                @error('client_id') <div class="form-hint" style="color:#dc2626;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>クライアントシークレット</label>
                <input type="password" name="client_secret" value="{{ $settings['client_secret'] }}" placeholder="GOCSPX-xxxx">
                @error('client_secret') <div class="form-hint" style="color:#dc2626;">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary">保存</button>
        </form>
    </div>
</div>

{{-- ステップ2: Googleアカウント連携 --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">2. Googleアカウント連携</div>
    <div class="card-body">
        @if($settings['is_connected'])
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                <span class="badge badge-green">✓ 連携済み</span>
                @if($settings['account_id'])
                    <span style="font-size:0.85rem; color:#666;">アカウント: {{ $settings['account_id'] }}</span>
                @endif
            </div>
            @if(!$settings['account_id'])
                <div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:8px; padding:12px 16px; margin-bottom:16px;">
                    <div style="font-size:0.85rem; color:#92400e; margin-bottom:8px;">
                        ⚠️ アカウントIDが自動取得できませんでした。下のボタンでAPI接続テストを行うか、手動で入力してください。
                    </div>
                    <div style="margin-bottom:10px;">
                        <button type="button" class="btn btn-info btn-sm" onclick="testConnection()">🔍 API接続テスト（アカウントID検出）</button>
                        <span id="test-loading" style="display:none; font-size:0.8rem; color:#667eea; margin-left:8px;">テスト中...</span>
                    </div>
                    <div id="test-result" style="display:none; margin-bottom:10px;"></div>
                    <form method="POST" action="/admin/google-settings/account" style="display:flex; gap:8px; align-items:center;">
                        @csrf
                        <input type="text" name="account_id" id="account-id-input" placeholder="accounts/123456789"
                               style="flex:1; padding:8px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.85rem;">
                        <button type="submit" class="btn btn-primary btn-sm">保存</button>
                    </form>
                </div>
            @endif
            <form method="POST" action="/admin/google-settings/disconnect" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('連携を解除しますか？\nこの操作は元に戻せません。')">連携解除</button>
            </form>
        @else
            @if($settings['client_id'] && $settings['client_secret'])
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 16px;">
                    Googleアカウントでログインして、ビジネスプロフィールへのアクセスを許可してください。
                </p>
                <a href="/admin/google-settings/authorize" class="btn btn-primary">🔗 Googleアカウントと連携</a>
            @else
                <p style="font-size: 0.85rem; color: #999;">先にAPI認証情報を保存してください。</p>
            @endif
        @endif
    </div>
</div>

{{-- ステップ3: 店舗とロケーションの紐付け --}}
@if($settings['is_connected'])
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">3. 店舗とGoogleロケーションの紐付け</div>
    <div class="card-body">
        @if(count($locations) > 0)
            <form method="POST" action="/admin/google-settings/location-mapping">
                @csrf
                <table>
                    <thead>
                        <tr>
                            <th>店舗名</th>
                            <th>Googleロケーション</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stores as $i => $store)
                        <tr>
                            <td>{{ $store->name }}</td>
                            <td>
                                <input type="hidden" name="mappings[{{ $i }}][store_id]" value="{{ $store->id }}">
                                <select name="mappings[{{ $i }}][location_name]" style="width:100%; padding:8px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.85rem;">
                                    <option value="">-- 未紐付け --</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc['name'] ?? '' }}"
                                            {{ ($store->google_location_name ?? '') === ($loc['name'] ?? '') ? 'selected' : '' }}>
                                            {{ $loc['title'] ?? $loc['name'] ?? '不明' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div style="margin-top: 16px;">
                    <button type="submit" class="btn btn-primary">紐付けを保存</button>
                </div>
            </form>
        @else
            <p style="font-size: 0.85rem; color: #999;">
                ロケーション情報を取得できませんでした。Googleビジネスプロフィールにロケーションが登録されているか確認してください。
            </p>
        @endif
    </div>
</div>
@endif

@push('styles')
<style>
    code {
        font-family: 'SF Mono', 'Monaco', 'Menlo', monospace;
        font-size: 0.8rem;
    }
</style>
@endpush

@push('scripts')
<script>
function testConnection() {
    var loading = document.getElementById('test-loading');
    var resultDiv = document.getElementById('test-result');
    loading.style.display = 'inline';
    resultDiv.style.display = 'none';

    fetch('/admin/google-settings/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        loading.style.display = 'none';
        resultDiv.style.display = 'block';

        var html = '<div style="background:#f8f9fa; border-radius:8px; padding:12px; font-size:0.8rem;">';

        // Account Management API
        var am = data.account_management || {};
        html += '<div style="margin-bottom:8px;"><strong>Account Management API:</strong> ';
        html += '<span style="color:' + (am.status === 200 ? '#059669' : '#dc2626') + ';">' + am.status + '</span>';
        if (am.status !== 200 && am.body && am.body.error) {
            html += ' - ' + (am.body.error.message || '').substring(0, 100);
        }
        html += '</div>';

        // v4 Legacy
        var v4 = data.v4_legacy || {};
        html += '<div style="margin-bottom:8px;"><strong>v4 Legacy API:</strong> ';
        html += '<span style="color:' + (v4.status === 200 ? '#059669' : '#dc2626') + ';">' + v4.status + '</span>';
        if (v4.status !== 200 && v4.body && v4.body.error) {
            html += ' - ' + (v4.body.error.message || '').substring(0, 100);
        }
        html += '</div>';

        // Business Info Locations
        if (data.business_info_locations) {
            var bi = data.business_info_locations;
            html += '<div style="margin-bottom:8px;"><strong>Business Information API:</strong> ';
            html += '<span style="color:' + (bi.status === 200 ? '#059669' : '#dc2626') + ';">' + bi.status + '</span>';
            html += '</div>';
        }

        // 検出結果
        if (data.detected_account_id) {
            html += '<div style="margin-top:8px; padding:8px; background:#d1fae5; border-radius:6px; color:#065f46;">';
            html += '✅ アカウントID検出: <strong>' + data.detected_account_id + '</strong>';
            html += ' <button type="button" onclick="document.getElementById(\'account-id-input\').value=\'' + data.detected_account_id + '\'" style="margin-left:8px; padding:2px 8px; background:#059669; color:white; border:none; border-radius:4px; cursor:pointer; font-size:0.75rem;">入力欄にセット</button>';
            html += '</div>';
        } else {
            html += '<div style="margin-top:8px; padding:8px; background:#fee2e2; border-radius:6px; color:#991b1b;">';
            html += '❌ アカウントIDを自動検出できませんでした。APIの割り当て承認待ちの可能性があります。';
            html += '</div>';
        }

        html += '</div>';
        resultDiv.innerHTML = html;
    })
    .catch(function(err) {
        loading.style.display = 'none';
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<div style="color:#dc2626; font-size:0.85rem;">通信エラーが発生しました。</div>';
    });
}
</script>
@endpush
@endsection
