@extends('layouts.admin')

@section('title', '買取投稿詳細')

@section('content')
<div class="page-header">
    <h1>📦 買取投稿詳細</h1>
    <a href="{{ route('admin.purchase-posts.index') }}" class="btn btn-secondary">← 一覧に戻る</a>
</div>

{{-- ステータス概要 --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
    <div class="card" style="border-top:3px solid {{ $purchasePost->wp_status === 'published' ? '#10b981' : ($purchasePost->wp_status === 'failed' ? '#ef4444' : '#d1d5db') }};">
        <div class="card-body" style="text-align:center;padding:16px;">
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;">WordPress</div>
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
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;">Google投稿</div>
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
            <div style="font-size:0.8rem;color:#888;margin-bottom:4px;">Google写真</div>
            @if($purchasePost->google_photo_status === 'published')
                <span class="badge badge-green" style="font-size:0.85rem;">✅ 追加済み</span>
            @elseif($purchasePost->google_photo_status === 'failed')
                <span class="badge badge-red" style="font-size:0.85rem;">❌ 失敗</span>
            @else
                <span class="badge badge-gray" style="font-size:0.85rem;">⏳ 未実行</span>
            @endif
        </div>
    </div>
</div>

{{-- エラー詳細 --}}
@if($purchasePost->wp_error || $purchasePost->google_post_error || $purchasePost->google_photo_error)
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
    @if($purchasePost->wp_status === 'failed' || $purchasePost->google_post_status === 'failed' || $purchasePost->google_photo_status === 'failed')
        <form method="POST" action="{{ route('admin.purchase-posts.retry', $purchasePost) }}">
            @csrf
            <button type="submit" class="btn btn-primary" style="padding:12px 32px;">🔄 失敗分をリトライ</button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.purchase-posts.destroy', $purchasePost) }}" onsubmit="return confirm('この投稿を削除しますか？WordPress・Googleビジネスからも削除されます。');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" style="padding:12px 32px;">🗑 投稿を削除</button>
    </form>
</div>
@endsection
