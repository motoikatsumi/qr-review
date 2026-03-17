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
            <form method="POST" action="/admin/google-settings/disconnect" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-danger" onclick="return confirm('連携を解除しますか？')">連携解除</button>
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
@endsection
