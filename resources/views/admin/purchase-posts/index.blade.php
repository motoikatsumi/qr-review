@extends('layouts.admin')

@section('title', '投稿一覧')

@section('content')
<div class="page-header">
    <h1>📦 投稿一覧</h1>
    <div style="display:flex;gap:12px;align-items:center;">
        @include('admin._partials.trash-filter', ['baseUrl' => route('admin.purchase-posts.index')])
        @if(!$showTrashed)
        <a href="{{ route('admin.purchase-posts.create') }}" class="btn btn-primary">＋ 新規投稿</a>
        @endif
    </div>
</div>

@if(!$showTrashed)
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
            <input type="search" id="postSearchInput" placeholder="🔍 ブランド名・商品名・カテゴリで絞り込み"
                   style="flex:1;min-width:240px;padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:0.85rem;outline:none;"
                   oninput="filterPostRows(this.value)">
            <span id="postSearchHint" style="font-size:0.74rem;color:#9ca3af;"></span>
        </form>
    </div>
</div>
@endif

@if($posts->isEmpty())
    <div class="card">
        <div class="card-body" style="text-align:center;padding:60px 20px;color:#888;">
            @if($showTrashed)
                <p style="font-size:1.1rem;">🗑 ゴミ箱は空です。</p>
            @else
                <div style="font-size:3rem;margin-bottom:12px;">📦</div>
                <p style="font-size:1.05rem;color:#374151;font-weight:600;margin-bottom:6px;">まだ投稿がありません</p>
                <p style="font-size:0.85rem;color:#6b7280;margin-bottom:18px;">買取商品を投稿すると WordPress / Instagram / Facebook へ一括公開できます</p>
                <a href="{{ route('admin.purchase-posts.create') }}" class="btn btn-primary" style="padding:10px 24px;">＋ 最初の投稿を作成</a>
            @endif
        </div>
    </div>
@else
    <div class="card" style="overflow-x:auto;">
        <table style="width:100%;white-space:nowrap;font-size:0.85rem;">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>店舗</th>
                    <th>商品名</th>
                    <th>カテゴリ</th>
                    <th style="text-align:center;">@if($wpUrl)<a href="{{ $wpUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:2px;opacity:0.85;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'"><svg viewBox="0 0 122.52 122.523" width="16" height="16" style="vertical-align:middle;"><g><path d="M8.708 61.26c0 20.802 12.089 38.779 29.619 47.298l-25.069-68.686C9.554 46.161 8.708 53.548 8.708 61.26z" fill="#21759b"/><path d="M96.74 58.608c0-6.495-2.333-10.993-4.334-14.494-2.664-4.329-5.161-7.995-5.161-12.324 0-4.831 3.664-9.328 8.825-9.328.233 0 .454.029.681.042-9.35-8.566-21.807-13.796-35.489-13.796-18.36 0-34.513 9.42-43.91 23.688 1.233.037 2.395.063 3.382.063 5.497 0 14.006-.668 14.006-.668 2.833-.167 3.167 3.994.337 4.329 0 0-2.847.335-6.015.501L48.2 93.547l11.501-34.493-8.188-22.434c-2.83-.166-5.511-.501-5.511-.501-2.832-.166-2.5-4.496.332-4.329 0 0 8.679.668 13.843.668 5.496 0 14.006-.668 14.006-.668 2.834-.167 3.168 3.994.337 4.329 0 0-2.853.335-6.015.501l18.992 56.494 5.242-17.517c2.272-7.269 4.001-12.49 4.001-16.989z" fill="#21759b"/><path d="M62.184 65.857l-15.768 45.819c4.708 1.384 9.687 2.141 14.846 2.141 6.12 0 11.989-1.058 17.452-2.979-.14-.225-.269-.464-.374-.724L62.184 65.857z" fill="#21759b"/><path d="M107.376 36.046c.226 1.674.354 3.471.354 5.404 0 5.333-.996 11.328-3.996 18.824l-16.053 46.449c15.624-9.111 26.133-26.038 26.133-45.463.001-9.137-2.333-17.729-6.438-25.214z" fill="#21759b"/><path d="M61.262 0C27.483 0 0 27.481 0 61.26c0 33.783 27.483 61.263 61.262 61.263 33.778 0 61.258-27.48 61.258-61.263C122.52 27.481 95.04 0 61.262 0zM61.262 119.715c-32.23 0-58.453-26.223-58.453-58.455 0-32.23 26.222-58.451 58.453-58.451 32.229 0 58.45 26.221 58.45 58.451 0 32.232-26.221 58.455-58.45 58.455z" fill="#21759b"/></g></svg><svg viewBox="0 0 24 24" width="10" height="10" style="vertical-align:middle;"><path d="M14 3h7v7h-2V6.41L8.59 16.83 7.17 15.41 17.59 5H14V3z" fill="#21759b" opacity="0.7"/></svg></a>@else<span title="WordPress"><svg viewBox="0 0 122.52 122.523" width="16" height="16" style="vertical-align:middle;opacity:0.5;"><g><path d="M8.708 61.26c0 20.802 12.089 38.779 29.619 47.298l-25.069-68.686C9.554 46.161 8.708 53.548 8.708 61.26z" fill="#21759b"/><path d="M96.74 58.608c0-6.495-2.333-10.993-4.334-14.494-2.664-4.329-5.161-7.995-5.161-12.324 0-4.831 3.664-9.328 8.825-9.328.233 0 .454.029.681.042-9.35-8.566-21.807-13.796-35.489-13.796-18.36 0-34.513 9.42-43.91 23.688 1.233.037 2.395.063 3.382.063 5.497 0 14.006-.668 14.006-.668 2.833-.167 3.167 3.994.337 4.329 0 0-2.847.335-6.015.501L48.2 93.547l11.501-34.493-8.188-22.434c-2.83-.166-5.511-.501-5.511-.501-2.832-.166-2.5-4.496.332-4.329 0 0 8.679.668 13.843.668 5.496 0 14.006-.668 14.006-.668 2.834-.167 3.168 3.994.337 4.329 0 0-2.853.335-6.015.501l18.992 56.494 5.242-17.517c2.272-7.269 4.001-12.49 4.001-16.989z" fill="#21759b"/><path d="M62.184 65.857l-15.768 45.819c4.708 1.384 9.687 2.141 14.846 2.141 6.12 0 11.989-1.058 17.452-2.979-.14-.225-.269-.464-.374-.724L62.184 65.857z" fill="#21759b"/><path d="M107.376 36.046c.226 1.674.354 3.471.354 5.404 0 5.333-.996 11.328-3.996 18.824l-16.053 46.449c15.624-9.111 26.133-26.038 26.133-45.463.001-9.137-2.333-17.729-6.438-25.214z" fill="#21759b"/><path d="M61.262 0C27.483 0 0 27.481 0 61.26c0 33.783 27.483 61.263 61.262 61.263 33.778 0 61.258-27.48 61.258-61.263C122.52 27.481 95.04 0 61.262 0zM61.262 119.715c-32.23 0-58.453-26.223-58.453-58.455 0-32.23 26.222-58.451 58.453-58.451 32.229 0 58.45 26.221 58.45 58.451 0 32.232-26.221 58.455-58.45 58.455z" fill="#21759b"/></g></svg></span>@endif</th>
                    <th style="text-align:center;"><span style="display:inline-flex;align-items:center;gap:3px;"><svg viewBox="0 0 24 24" width="16" height="16"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>投稿</span></th>
                    <th style="text-align:center;"><span style="display:inline-flex;align-items:center;gap:3px;"><svg viewBox="0 0 24 24" width="16" height="16"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>写真</span></th>
                    <th style="text-align:center;">@if($facebookUrl ?? false)<a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" title="Facebookページを開く" style="display:inline-flex;align-items:center;gap:2px;opacity:0.85;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'"><svg viewBox="0 0 24 24" width="16" height="16" style="vertical-align:middle;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/></svg><svg viewBox="0 0 24 24" width="10" height="10" style="vertical-align:middle;"><path d="M14 3h7v7h-2V6.41L8.59 16.83 7.17 15.41 17.59 5H14V3z" fill="#1877F2" opacity="0.7"/></svg></a>@else<span title="Facebook"><svg viewBox="0 0 24 24" width="16" height="16" style="vertical-align:middle;opacity:0.5;"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/></svg></span>@endif</th>
                    <th style="text-align:center;">@if($instagramUrl ?? false)<a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" title="Instagramを開く" style="display:inline-flex;align-items:center;gap:2px;opacity:0.85;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'"><svg viewBox="0 0 24 24" width="16" height="16" style="vertical-align:middle;"><defs><radialGradient id="ig1" cx="30%" cy="107%" r="150%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></radialGradient></defs><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.17.054 1.97.24 2.43.403a4.08 4.08 0 0 1 1.47.957c.453.454.793.898.957 1.47.163.46.35 1.26.403 2.43.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.054 1.17-.24 1.97-.403 2.43a4.08 4.08 0 0 1-.957 1.47 4.08 4.08 0 0 1-1.47.957c-.46.163-1.26.35-2.43.403-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.054-1.97-.24-2.43-.403a4.08 4.08 0 0 1-1.47-.957 4.08 4.08 0 0 1-.957-1.47c-.163-.46-.35-1.26-.403-2.43C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.054-1.17.24-1.97.403-2.43a4.08 4.08 0 0 1 .957-1.47A4.08 4.08 0 0 1 5.063 2.293c.46-.163 1.26-.35 2.43-.403C8.759 1.832 9.139 1.82 12.343 1.82zM12 0C8.741 0 8.333.014 7.053.072 5.775.13 4.902.333 4.14.63a5.88 5.88 0 0 0-2.126 1.384A5.88 5.88 0 0 0 .63 4.14C.333 4.902.13 5.775.072 7.053.014 8.333 0 8.741 0 12s.014 3.667.072 4.947c.058 1.278.261 2.15.558 2.913a5.88 5.88 0 0 0 1.384 2.126A5.88 5.88 0 0 0 4.14 23.37c.763.297 1.636.5 2.913.558C8.333 23.986 8.741 24 12 24s3.667-.014 4.947-.072c1.278-.058 2.15-.261 2.913-.558a5.88 5.88 0 0 0 2.126-1.384 5.88 5.88 0 0 0 1.384-2.126c.297-.763.5-1.636.558-2.913.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.058-1.278-.261-2.15-.558-2.913a5.88 5.88 0 0 0-1.384-2.126A5.88 5.88 0 0 0 19.86.63c-.763-.297-1.636-.5-2.913-.558C15.667.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" fill="url(#ig1)"/></svg><svg viewBox="0 0 24 24" width="10" height="10" style="vertical-align:middle;"><path d="M14 3h7v7h-2V6.41L8.59 16.83 7.17 15.41 17.59 5H14V3z" fill="#d6249f" opacity="0.7"/></svg></a>@else<span title="Instagram"><svg viewBox="0 0 24 24" width="16" height="16" style="vertical-align:middle;opacity:0.5;"><defs><radialGradient id="ig1" cx="30%" cy="107%" r="150%"><stop offset="0%" stop-color="#fdf497"/><stop offset="5%" stop-color="#fdf497"/><stop offset="45%" stop-color="#fd5949"/><stop offset="60%" stop-color="#d6249f"/><stop offset="90%" stop-color="#285AEB"/></radialGradient></defs><path d="M12 2.163c3.204 0 3.584.012 4.85.07 1.17.054 1.97.24 2.43.403a4.08 4.08 0 0 1 1.47.957c.453.454.793.898.957 1.47.163.46.35 1.26.403 2.43.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.054 1.17-.24 1.97-.403 2.43a4.08 4.08 0 0 1-.957 1.47 4.08 4.08 0 0 1-1.47.957c-.46.163-1.26.35-2.43.403-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.054-1.97-.24-2.43-.403a4.08 4.08 0 0 1-1.47-.957 4.08 4.08 0 0 1-.957-1.47c-.163-.46-.35-1.26-.403-2.43C2.175 15.584 2.163 15.204 2.163 12s.012-3.584.07-4.85c.054-1.17.24-1.97.403-2.43a4.08 4.08 0 0 1 .957-1.47A4.08 4.08 0 0 1 5.063 2.293c.46-.163 1.26-.35 2.43-.403C8.759 1.832 9.139 1.82 12.343 1.82zM12 0C8.741 0 8.333.014 7.053.072 5.775.13 4.902.333 4.14.63a5.88 5.88 0 0 0-2.126 1.384A5.88 5.88 0 0 0 .63 4.14C.333 4.902.13 5.775.072 7.053.014 8.333 0 8.741 0 12s.014 3.667.072 4.947c.058 1.278.261 2.15.558 2.913a5.88 5.88 0 0 0 1.384 2.126A5.88 5.88 0 0 0 4.14 23.37c.763.297 1.636.5 2.913.558C8.333 23.986 8.741 24 12 24s3.667-.014 4.947-.072c1.278-.058 2.15-.261 2.913-.558a5.88 5.88 0 0 0 2.126-1.384 5.88 5.88 0 0 0 1.384-2.126c.297-.763.5-1.636.558-2.913.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.058-1.278-.261-2.15-.558-2.913a5.88 5.88 0 0 0-1.384-2.126A5.88 5.88 0 0 0 19.86.63c-.763-.297-1.636-.5-2.913-.558C15.667.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" fill="url(#ig1)"/></svg></span>@endif</th>
                    <th>投稿日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posts as $post)
                <tr>
                    <td>{{ $posts->firstItem() + $loop->index }}</td>
                    <td>{{ $post->store->name ?? '-' }}</td>
                    <td style="white-space:normal;word-break:break-all;min-width:180px;max-width:260px;">{{ $post->brand_name }} {{ $post->product_name }}</td>
                    <td><span class="badge badge-gray">{{ $post->category }}</span></td>
                    <td>
                        @if($post->wp_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->wp_status === 'failed')
                            <span class="badge badge-red">❌ 失敗</span>
                            <div style="font-size:0.75rem;color:#dc2626;margin-top:2px;">{{ Str::limit($post->wp_error, 40) }}</div>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td>
                        @if($post->google_post_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->google_post_status === 'failed')
                            <span class="badge badge-red">❌ 失敗</span>
                            <div style="font-size:0.75rem;color:#dc2626;margin-top:2px;">{{ Str::limit($post->google_post_error, 40) }}</div>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td>
                        @if($post->google_photo_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->google_photo_status === 'failed')
                            <span class="badge badge-red">❌ 失敗</span>
                            <div style="font-size:0.75rem;color:#dc2626;margin-top:2px;">{{ Str::limit($post->google_photo_error, 40) }}</div>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td>
                        @if($post->facebook_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->facebook_status === 'failed')
                            <span class="badge badge-red">❌ 失敗</span>
                            <div style="font-size:0.75rem;color:#dc2626;margin-top:2px;">{{ Str::limit($post->facebook_error, 40) }}</div>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td>
                        @if($post->instagram_status === 'published')
                            <span class="badge badge-green">✅ 完了</span>
                        @elseif($post->instagram_status === 'failed')
                            <span class="badge badge-red">❌ 失敗</span>
                            <div style="font-size:0.75rem;color:#dc2626;margin-top:2px;">{{ Str::limit($post->instagram_error, 40) }}</div>
                        @else
                            <span class="badge badge-gray">⏳ 未実行</span>
                        @endif
                    </td>
                    <td style="color:#888;">{{ $post->created_at->format('m/d H:i') }}</td>
                    <td>
                        @if($showTrashed)
                        <div class="btn-group">
                            <span style="font-size:0.74rem;color:#92400e;display:block;margin-bottom:4px;">削除日: {{ $post->deleted_at?->format('Y/n/j H:i') }}</span>
                            <form method="POST" action="{{ route('admin.purchase-posts.restore', $post->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">↩ 復元</button>
                            </form>
                            <form method="POST" action="{{ route('admin.purchase-posts.force-delete', $post->id) }}" style="display:inline;"
                                  onsubmit="return confirm('「{{ $post->brand_name }} {{ $post->product_name }}」を完全に削除します。\nこの操作は元に戻せません。');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">🗑 完全削除</button>
                            </form>
                        </div>
                        @else
                        <div class="btn-group">
                            <a href="{{ route('admin.purchase-posts.show', $post) }}" class="btn btn-info btn-sm">詳細</a>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script>
    function filterPostRows(query) {
        var q = (query || '').toLowerCase().trim();
        var rows = document.querySelectorAll('tbody tr.searchable-row, tbody tr');
        var hint = document.getElementById('postSearchHint');
        var visibleCount = 0;
        var totalCount = 0;
        rows.forEach(function(row) {
            // ヘッダ・ページネーション行を除外
            if (!row.querySelector('td')) return;
            totalCount++;
            var text = row.textContent.toLowerCase();
            var match = q === '' || text.indexOf(q) !== -1;
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });
        if (hint) hint.textContent = q === '' ? '' : (visibleCount + ' / ' + totalCount + ' 件');
    }
    </script>

    @if($posts->hasPages())
        <div style="margin-top:20px;">
            {{ $posts->withQueryString()->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endif
@endsection
