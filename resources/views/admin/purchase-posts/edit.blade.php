@extends('layouts.admin')

@section('title', '投稿を編集')

@push('styles')
<style>
    .block-section {
        background: #fafbfc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .block-section h3 {
        font-size: 0.95rem;
        color: #4338ca;
        margin-bottom: 12px;
    }
    .preview-box {
        background: #f8f9fa;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        white-space: pre-wrap;
        font-size: 0.9rem;
        line-height: 1.7;
        min-height: 100px;
        max-height: 400px;
        overflow-y: auto;
    }
    .two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 768px) {
        .two-col { grid-template-columns: 1fr; }
    }
    .char-count {
        font-size: 0.75rem;
        color: #888;
        text-align: right;
        margin-top: 4px;
    }
    .current-image {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #e5e7eb;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>✏️ 投稿を編集</h1>
    <a href="{{ route('admin.purchase-posts.show', $purchasePost) }}" class="btn btn-secondary">← 詳細に戻る</a>
</div>

{{-- 店舗データをJSに渡す --}}
<script>
var storeData = {};
@foreach($stores as $s)
    storeData[{{ $s->id }}] = {
        name: @json($s->name),
        use_product_rank: {{ ($s->businessType->use_product_rank ?? false) ? 'true' : 'false' }},
        post_categories: @json($s->businessType->post_categories ?? []),
        post_status_options: @json($s->businessType->post_status_options ?? []),
    };
@endforeach
var currentCategory = @json(old('category', $purchasePost->category));
var currentStatus = @json(old('product_status', $purchasePost->product_status));
</script>

{{-- 投稿失敗があればリトライボタンを表示(編集フォームとは別フォームにする) --}}
@php
    $failedTargets = collect([
        'WordPress' => $purchasePost->wp_status === 'failed',
        'Googleビジネス投稿' => $purchasePost->google_post_status === 'failed',
        'Googleビジネス写真' => ($purchasePost->google_photo_status ?? null) === 'failed',
        'Instagram' => $purchasePost->instagram_status === 'failed',
        'Facebook' => $purchasePost->facebook_status === 'failed',
    ])->filter()->keys();
@endphp
@if($failedTargets->count() > 0)
<div class="card" style="margin-bottom:20px;border-left:4px solid #ef4444;background:#fef2f2;">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div style="flex:1;min-width:240px;">
            <div style="font-weight:600;color:#991b1b;margin-bottom:4px;">⚠️ 投稿失敗があります</div>
            <div style="font-size:0.85rem;color:#7f1d1d;">失敗した投稿先: <strong>{{ $failedTargets->implode(' / ') }}</strong></div>
        </div>
        <form method="POST" action="{{ route('admin.purchase-posts.retry', $purchasePost) }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;background:#dc2626;border-color:#dc2626;">
                🔄 失敗分をリトライ
            </button>
        </form>
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.purchase-posts.update', $purchasePost) }}" enctype="multipart/form-data" id="editForm">
    @csrf
    @method('PUT')

    {{-- 基本情報 --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">📋 基本情報</div>
        <div class="card-body">
            <div class="two-col">
                <div class="form-group">
                    <label for="store_id">投稿店舗 <span style="color:#ef4444">*</span></label>
                    <select id="store_id" name="store_id" required onchange="onStoreChange()">
                        <option value="">店舗を選択</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id', $purchasePost->store_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                    @error('store_id') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="category">カテゴリ <span style="color:#ef4444">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">カテゴリを選択</option>
                    </select>
                    @error('category') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="brand_name">ブランド名（メーカー） <span style="color:#ef4444">*</span></label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name', $purchasePost->brand_name) }}" required>
                    @error('brand_name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label for="product_name">商品名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="product_name" name="product_name" value="{{ old('product_name', $purchasePost->product_name) }}" required>
                    @error('product_name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="product_status">状態 <span style="color:#ef4444">*</span></label>
                    <select id="product_status" name="product_status" required>
                        <option value="">状態を選択</option>
                    </select>
                </div>
                <div class="form-group" id="rankGroup">
                    <label for="rank">ランク</label>
                    <select id="rank" name="rank">
                        <option value="" {{ old('rank', $purchasePost->rank) === '' || old('rank', $purchasePost->rank) === null ? 'selected' : '' }}>選択してください</option>
                        <option value="S" {{ old('rank', $purchasePost->rank) === 'S' ? 'selected' : '' }}>S（新品・未使用）</option>
                        <option value="A" {{ old('rank', $purchasePost->rank) === 'A' ? 'selected' : '' }}>A（美品）</option>
                        <option value="B" {{ old('rank', $purchasePost->rank) === 'B' ? 'selected' : '' }}>B（良品）</option>
                        <option value="C" {{ old('rank', $purchasePost->rank) === 'C' ? 'selected' : '' }}>C（一般的な使用感）</option>
                        <option value="D" {{ old('rank', $purchasePost->rank) === 'D' ? 'selected' : '' }}>D（ジャンク品）</option>
                    </select>
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="wp_tag_name">タグ（ブランド名）</label>
                    <input type="text" id="wp_tag_name" name="wp_tag_name" value="{{ old('wp_tag_name', $purchasePost->wp_tag_name) }}" placeholder="例：ブランド名やメーカー名">
                </div>
            </div>

            <div class="form-group">
                <label>画像</label>
                <div style="display:flex;gap:20px;align-items:flex-start;">
                    @if($purchasePost->wp_image_url)
                        <img src="{{ $purchasePost->wp_image_url }}" alt="現在の画像" class="current-image">
                    @elseif($purchasePost->image_path)
                        <img src="{{ asset('storage/' . $purchasePost->image_path) }}" alt="現在の画像" class="current-image">
                    @endif
                    <div>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png">
                        <p class="form-hint" style="margin-top:8px;">画像を変更する場合のみ選択してください<br>推奨: 1080×1080px（正方形）、JPG/PNG、10MB以内</p>
                        @error('image') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ブロック① --}}
    <div class="block-section">
        <h3>🔷 ブロック①：タイトル行</h3>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block1_text" name="block1_text" rows="3" required>{{ old('block1_text', $purchasePost->block1_text) }}</textarea>
            <div class="char-count"><span id="block1Count">0</span> / 1,500</div>
        </div>
        @error('block1_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- ブロック② --}}
    <div class="block-section">
        <h3>🔷 ブロック②：エピソード</h3>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block2_text" name="block2_text" rows="10" required>{{ old('block2_text', $purchasePost->block2_text) }}</textarea>
            <div class="char-count"><span id="block2Count">0</span> / 1,500</div>
        </div>
        @error('block2_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- ブロック③ --}}
    <div class="block-section">
        <h3>🔷 ブロック③：店舗別エリアフッター</h3>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block3_text" name="block3_text" rows="3" required>{{ old('block3_text', $purchasePost->block3_text) }}</textarea>
            <div class="char-count"><span id="block3Count">0</span> / 1,500</div>
        </div>
        @error('block3_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- プレビュー --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">👁️ 投稿プレビュー</div>
        <div class="card-body">
            <div class="preview-box" id="previewBox"></div>
            <div class="char-count" style="margin-top:8px;">合計文字数: <strong id="totalCount">0</strong> / 1,500</div>
        </div>
    </div>

    {{-- 注意書き --}}
    <div class="card" style="margin-bottom:20px;border-left:4px solid #f59e0b;">
        <div class="card-body" style="padding:14px 20px;">
            <p style="font-size:0.85rem;color:#92400e;margin:0;">⚠️ テキストの編集はローカルデータのみ更新されます。WordPress・Googleビジネスに投稿済みの内容は変更されません。</p>
        </div>
    </div>

    {{-- 保存ボタン --}}
    <div style="display:flex;gap:12px;justify-content:center;margin-bottom:40px;">
        <button type="submit" class="btn btn-primary" style="padding:14px 40px;font-size:1rem;" id="submitBtn">
            💾 変更を保存する
        </button>
        <a href="{{ route('admin.purchase-posts.show', $purchasePost) }}" class="btn btn-secondary" style="padding:14px 40px;font-size:1rem;">キャンセル</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function onStoreChange() {
        var storeId = document.getElementById('store_id').value;
        var sd = storeData[storeId] || null;

        // ランク表示/非表示
        var rankGroup = document.getElementById('rankGroup');
        if (sd && sd.use_product_rank) {
            rankGroup.style.display = '';
        } else {
            rankGroup.style.display = 'none';
        }

        // カテゴリ更新
        var catSel = document.getElementById('category');
        catSel.innerHTML = '<option value="">カテゴリを選択</option>';
        if (sd && sd.post_categories) {
            sd.post_categories.forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.name;
                opt.textContent = c.name;
                if (c.name === currentCategory) opt.selected = true;
                catSel.appendChild(opt);
            });
        }

        // ステータス更新
        var statusSel = document.getElementById('product_status');
        statusSel.innerHTML = '<option value="">状態を選択</option>';
        if (sd && sd.post_status_options) {
            sd.post_status_options.forEach(function(s) {
                var opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                if (s === currentStatus) opt.selected = true;
                statusSel.appendChild(opt);
            });
        }
    }

    function updateCounts() {
        var b1 = document.getElementById('block1_text').value;
        var b2 = document.getElementById('block2_text').value;
        var b3 = document.getElementById('block3_text').value;
        document.getElementById('block1Count').textContent = b1.length;
        document.getElementById('block2Count').textContent = b2.length;
        document.getElementById('block3Count').textContent = b3.length;

        var total = b1.length + b2.length + b3.length;
        document.getElementById('totalCount').textContent = total;

        var preview = '';
        if (b1) preview += b1;
        if (b2) preview += '\n\n' + b2;
        if (b3) preview += '\n\n' + b3;
        document.getElementById('previewBox').textContent = preview || '';
    }

    document.getElementById('block1_text').addEventListener('input', updateCounts);
    document.getElementById('block2_text').addEventListener('input', updateCounts);
    document.getElementById('block3_text').addEventListener('input', updateCounts);

    // 二重送信防止
    document.getElementById('editForm').addEventListener('submit', function() {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '保存中...';
    });

    // 初期化
    onStoreChange();
    updateCounts();
</script>
@endpush
