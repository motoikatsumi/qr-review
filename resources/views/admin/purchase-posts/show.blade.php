@extends('layouts.admin')

@section('title', '投稿詳細')

@section('content')
<div class="page-header">
    <h1>📦 投稿詳細</h1>
    <a href="{{ route('admin.purchase-posts.index') }}" class="btn btn-secondary">← 一覧に戻る</a>
</div>

{{-- ステータス概要 --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px;">
    <div class="card" style="border-top:3px solid {{ $purchasePost->wp_status === 'published' ? '#10b981' : ($purchasePost->wp_status === 'failed' ? '#ef4444' : '#d1d5db') }};">
        <div class="card-body" style="text-align:center;padding:16px;">
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><svg viewBox="0 0 122.52 122.523" width="14" height="14" style="vertical-align:middle;"><g><path d="M8.708 61.26c0 20.802 12.089 38.779 29.619 47.298l-25.069-68.686C9.554 46.161 8.708 53.548 8.708 61.26z" fill="#21759b"/><path d="M96.74 58.608c0-6.495-2.333-10.993-4.334-14.494-2.664-4.329-5.161-7.995-5.161-12.324 0-4.831 3.664-9.328 8.825-9.328.233 0 .454.029.681.042-9.35-8.566-21.807-13.796-35.489-13.796-18.36 0-34.513 9.42-43.91 23.688 1.233.037 2.395.063 3.382.063 5.497 0 14.006-.668 14.006-.668 2.833-.167 3.167 3.994.337 4.329 0 0-2.847.335-6.015.501L48.2 93.547l11.501-34.493-8.188-22.434c-2.83-.166-5.511-.501-5.511-.501-2.832-.166-2.5-4.496.332-4.329 0 0 8.679.668 13.843.668 5.496 0 14.006-.668 14.006-.668 2.834-.167 3.168 3.994.337 4.329 0 0-2.853.335-6.015.501l18.992 56.494 5.242-17.517c2.272-7.269 4.001-12.49 4.001-16.989z" fill="#21759b"/><path d="M62.184 65.857l-15.768 45.819c4.708 1.384 9.687 2.141 14.846 2.141 6.12 0 11.989-1.058 17.452-2.979-.14-.225-.269-.464-.374-.724L62.184 65.857z" fill="#21759b"/><path d="M107.376 36.046c.226 1.674.354 3.471.354 5.404 0 5.333-.996 11.328-3.996 18.824l-16.053 46.449c15.624-9.111 26.133-26.038 26.133-45.463.001-9.137-2.333-17.729-6.438-25.214z" fill="#21759b"/><path d="M61.262 0C27.483 0 0 27.481 0 61.26c0 33.783 27.483 61.263 61.262 61.263 33.778 0 61.258-27.48 61.258-61.263C122.52 27.481 95.04 0 61.262 0zM61.262 119.715c-32.23 0-58.453-26.223-58.453-58.455 0-32.23 26.222-58.451 58.453-58.451 32.229 0 58.45 26.221 58.45 58.451 0 32.232-26.221 58.455-58.45 58.455z" fill="#21759b"/></g></svg> WordPress</div>
            @if($purchasePost->wp_status === 'published')
                <span class="badge badge-green" style="font-size:0.85rem;">✅ 投稿済み</span>
            @elseif($purchasePost->wp_status === 'failed')
                <span class="badge badge-red" style="font-size:0.85rem;">❌ 失敗</span>
            @else
                <span class="badge badge-gray" style="font-size:0.85rem;">⏳ 未実行</span>
            @endif
        </div>
    </div>
    <div class="card" style="border-top:3px solid {{ $purchasePost->google_post_status === 'published' ? '#10b981' : ($purchasePost->google_post_status === 'failed' ? '#ef4444' : '#d1d5db') }};">
        <div class="card-body" style="text-align:center;padding:16px;">
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:middle;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> 投稿</div>
            @if($purchasePost->google_post_status === 'published')
                <span class="badge badge-green" style="font-size:0.85rem;">✅ 投稿済み</span>
            @elseif($purchasePost->google_post_status === 'failed')
                <span class="badge badge-red" style="font-size:0.85rem;">❌ 失敗</span>
            @else
                <span class="badge badge-gray" style="font-size:0.85rem;">⏳ 未実行</span>
            @endif
        </div>
    </div>
    <div class="card" style="border-top:3px solid {{ $purchasePost->google_photo_status === 'published' ? '#10b981' : ($purchasePost->google_photo_status === 'failed' ? '#ef4444' : '#d1d5db') }};">
        <div class="card-body" style="text-align:center;padding:16px;">
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:middle;"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg> 写真</div>
            @if($purchasePost->google_photo_status === 'published')
                <span class="badge badge-green" style="font-size:0.85rem;">✅ 追加済み</span>
            @elseif($purchasePost->google_photo_status === 'failed')
                <span class="badge badge-red" style="font-size:0.85rem;">❌ 失敗</span>
            @else
                <span class="badge badge-gray" style="font-size:0.85rem;">⏳ 未実行</span>
            @endif
        </div>
    </div>
    <div class="card" style="border-top:3px solid {{ $purchasePost->facebook_status === 'published' ? '#10b981' : ($purchasePost->facebook_status === 'failed' ? '#ef4444' : '#d1d5db') }};">
        <div class="card-body" style="text-align:center;padding:16px;">
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:middle;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/></svg> Facebook</div>
            @if($purchasePost->facebook_status === 'published')
                <span class="badge badge-green" style="font-size:0.85rem;">✅ 投稿済み</span>
            @elseif($purchasePost->facebook_status === 'failed')
                <span class="badge badge-red" style="font-size:0.85rem;">❌ 失敗</span>
            @else
                <span class="badge badge-gray" style="font-size:0.85rem;">⏳ 未実行</span>
            @endif
        </div>
    </div>
    <div class="card" style="border-top:3px solid {{ $purchasePost->instagram_status === 'published' ? '#10b981' : ($purchasePost->instagram_status === 'failed' ? '#ef4444' : '#d1d5db') }};">
        <div class="card-body" style="text-align:center;padding:16px;">
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:middle;"><defs><radialGradient id="ig2" cx="30%" cy="107%" r="150%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></radialGradient></defs><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.17.054 1.97.24 2.43.403a4.08 4.08 0 0 1 1.47.957c.453.454.793.898.957 1.47.163.46.35 1.26.403 2.43.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.054 1.17-.24 1.97-.403 2.43a4.08 4.08 0 0 1-.957 1.47 4.08 4.08 0 0 1-1.47.957c-.46.163-1.26.35-2.43.403-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.054-1.97-.24-2.43-.403a4.08 4.08 0 0 1-1.47-.957 4.08 4.08 0 0 1-.957-1.47c-.163-.46-.35-1.26-.403-2.43C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.054-1.17.24-1.97.403-2.43a4.08 4.08 0 0 1 .957-1.47A4.08 4.08 0 0 1 5.063 2.293c.46-.163 1.26-.35 2.43-.403C8.759 1.832 9.139 1.82 12.343 1.82zM12 0C8.741 0 8.333.014 7.053.072 5.775.13 4.902.333 4.14.63a5.88 5.88 0 0 0-2.126 1.384A5.88 5.88 0 0 0 .63 4.14C.333 4.902.13 5.775.072 7.053.014 8.333 0 8.741 0 12s.014 3.667.072 4.947c.058 1.278.261 2.15.558 2.913a5.88 5.88 0 0 0 1.384 2.126A5.88 5.88 0 0 0 4.14 23.37c.763.297 1.636.5 2.913.558C8.333 23.986 8.741 24 12 24s3.667-.014 4.947-.072c1.278-.058 2.15-.261 2.913-.558a5.88 5.88 0 0 0 2.126-1.384 5.88 5.88 0 0 0 1.384-2.126c.297-.763.5-1.636.558-2.913.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.058-1.278-.261-2.15-.558-2.913a5.88 5.88 0 0 0-1.384-2.126A5.88 5.88 0 0 0 19.86.63c-.763-.297-1.636-.5-2.913-.558C15.667.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" fill="url(#ig2)"/></svg> Instagram</div>
            @if($purchasePost->instagram_status === 'published')
                <span class="badge badge-green" style="font-size:0.85rem;">✅ 投稿済み</span>
            @elseif($purchasePost->instagram_status === 'failed')
                <span class="badge badge-red" style="font-size:0.85rem;">❌ 失敗</span>
            @else
                <span class="badge badge-gray" style="font-size:0.85rem;">⏳ 未実行</span>
            @endif
        </div>
    </div>
</div>
@if($purchasePost->wp_error || $purchasePost->google_post_error || $purchasePost->google_photo_error || $purchasePost->facebook_error || $purchasePost->instagram_error)
<div class="card" style="margin-bottom:20px;border-left:4px solid #ef4444;">
    <div class="card-header" style="background:#fef2f2;color:#991b1b;">⚠️ エラー詳細</div>
    <div class="card-body">
        @if($purchasePost->wp_error)
            <div style="margin-bottom:8px;">
                <strong>WordPress:</strong> <span style="color:#dc2626;">{{ $purchasePost->wp_error }}</span>
            </div>
        @endif
        @if($purchasePost->google_post_error)
            <div style="margin-bottom:8px;">
                <strong>Google投稿:</strong> <span style="color:#dc2626;">{{ $purchasePost->google_post_error }}</span>
            </div>
        @endif
        @if($purchasePost->google_photo_error)
            <div style="margin-bottom:8px;">
                <strong>Google写真:</strong> <span style="color:#dc2626;">{{ $purchasePost->google_photo_error }}</span>
            </div>
        @endif
        @if($purchasePost->facebook_error)
            <div style="margin-bottom:8px;">
                <strong>Facebook:</strong> <span style="color:#dc2626;">{{ $purchasePost->facebook_error }}</span>
            </div>
        @endif
        @if($purchasePost->instagram_error)
            <div style="margin-bottom:8px;">
                <strong>Instagram:</strong> <span style="color:#dc2626;">{{ $purchasePost->instagram_error }}</span>
            </div>
        @endif
    </div>
</div>
@endif

{{-- 商品情報 --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">📋 商品情報</div>
    <div class="card-body">
        <div style="display:flex;gap:24px;">
            @if($purchasePost->wp_image_url)
                <img src="{{ $purchasePost->wp_image_url }}" alt="商品画像" style="width:200px;height:200px;object-fit:cover;border-radius:10px;">
            @elseif($purchasePost->image_path)
                <img src="{{ asset('storage/' . $purchasePost->image_path) }}" alt="商品画像" style="width:200px;height:200px;object-fit:cover;border-radius:10px;">
            @endif
            <table style="flex:1;">
                <tr><td style="width:120px;color:#888;font-weight:600;">店舗</td><td>{{ $purchasePost->store->name ?? '-' }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">ブランド名</td><td>{{ $purchasePost->brand_name }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">商品名</td><td>{{ $purchasePost->product_name }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">状態</td><td>{{ $purchasePost->product_status }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">ランク</td><td>{{ $purchasePost->rank ?? '-' }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">カテゴリ</td><td><span class="badge badge-gray">{{ $purchasePost->category }}</span></td></tr>
                <tr><td style="color:#888;font-weight:600;">WPカテゴリ</td><td>{{ $purchasePost->wp_category_slug ?? '-' }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">WPタグ</td><td>{{ $purchasePost->wp_tag_name ?? '-' }}</td></tr>
                <tr><td style="color:#888;font-weight:600;">投稿日時</td><td>{{ $purchasePost->published_at?->format('Y/m/d H:i') ?? '-' }}</td></tr>
            </table>
        </div>
    </div>
</div>

{{-- 投稿本文 --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">📝 投稿本文</div>
    <div class="card-body">
        <div style="background:#f8f9fa;border-radius:8px;padding:16px;white-space:pre-wrap;line-height:1.8;font-size:0.9rem;">{{ $purchasePost->full_text }}</div>
    </div>
</div>

{{-- 操作ボタン --}}
<div style="display:flex;gap:12px;justify-content:center;margin-bottom:40px;">
    <a href="{{ route('admin.purchase-posts.edit', $purchasePost) }}" class="btn btn-primary" style="padding:12px 32px;">✏️ 編集</a>
    @if($purchasePost->wp_status === 'failed' || $purchasePost->google_post_status === 'failed' || $purchasePost->google_photo_status === 'failed' || $purchasePost->instagram_status === 'failed')
        <form method="POST" action="{{ route('admin.purchase-posts.retry', $purchasePost) }}">
            @csrf
            <button type="submit" class="btn btn-primary" style="padding:12px 32px;">🔄 失敗分をリトライ</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.purchase-posts.destroy', $purchasePost) }}" onsubmit="return confirm('この投稿を削除しますか？\nWordPress・Googleビジネスからも削除されます。\nこの操作は元に戻せません。');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" style="padding:12px 32px;">🗑 投稿を削除</button>
    </form>
</div>
@endsection
