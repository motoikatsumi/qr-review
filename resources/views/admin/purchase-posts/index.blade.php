@extends('layouts.admin')

@section('title', '買取投稿一覧')

@section('content')
<div class="page-header">
    <h1>📦 買取投稿一覧</h1>
    <a href="{{ route('admin.purchase-posts.create') }}" class="btn btn-primary">＋ 新規投稿</a>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:14px 20px;">
        <form method="GET" action="{{ route('admin.purchase-posts.index') }}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <select name="store_id" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.85rem;">
                <option value="">すべての店舗</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                @endforeach
            </select>
            <select name="status" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.85rem;">
                <option value="">すべてのステータス</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>✅ 全て成功</option>
                <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>⚠️ 一部失敗</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ 失敗あり</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">絞り込む</button>
        </form>
    </div>
</div>

@if($posts->isEmpty())
    <div class="card">
        <div class="card-body" style="text-align:center;padding:60px 20px;color:#888;">
            <p style="font-size:1.1rem;">まだ買取投稿がありません</p>
            <a href="{{ route('admin.purchase-posts.create') }}" class="btn btn-primary" style="margin-top:16px;">最初の投稿を作成する</a>
        </div>
    </div>
@else
    <div class="card">
        <table style="width:100%;white-space:nowrap;font-size:0.85rem;">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>店舗</th>
                    <th>商品名</th>
                    <th>カテゴリ</th>
                    <th>WP</th>
                    <th>G投稿</th>
                    <th>G写真</th>
                    <th>投稿日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posts as $post)
                <tr>
                    <td>{{ $posts->firstItem() + $loop->index }}</td>
                    <td>{{ $post->store->name ?? '-' }}</td>
                    <td style="white-space:normal;word-break:break-all;">{{ $post->brand_name }} {{ $post->product_name }}</td>
                    <td><span class="badge badge-gray">{{ $post->category }}</span></td>
                    <td>
                        @if($post->wp_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->wp_status === 'failed')
                            <span class="badge badge-red" title="{{ $post->wp_error }}">❌ 失敗</span>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td>
                        @if($post->google_post_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->google_post_status === 'failed')
                            <span class="badge badge-red" title="{{ $post->google_post_error }}">❌ 失敗</span>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td>
                        @if($post->google_photo_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->google_photo_status === 'failed')
                            <span class="badge badge-red" title="{{ $post->google_photo_error }}">❌ 失敗</span>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td style="color:#888;">{{ $post->created_at->format('m/d H:i') }}</td>
                    <td>
                        <div class="btn-group">
                            <a href="{{ route('admin.purchase-posts.show', $post) }}" class="btn btn-info btn-sm">詳細</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($posts->hasPages())
        <div style="margin-top:20px;">
            {{ $posts->withQueryString()->links() }}
        </div>
    @endif
@endif
@endsection
