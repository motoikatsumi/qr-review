@extends('layouts.admin')

@section('title', '投稿作成')

@push('styles')
<style>
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
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-generate {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-generate:hover {
        box-shadow: 0 4px 12px rgba(245,158,11,0.4);
        transform: translateY(-1px);
    }
    .btn-generate:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    .image-preview {
        width: 200px;
        height: 200px;
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fafafa;
        cursor: pointer;
    }
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .image-preview .placeholder {
        text-align: center;
        color: #999;
        font-size: 0.85rem;
    }
    .two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 768px) {
        .two-col { grid-template-columns: 1fr; }
    }
    .spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .char-count {
        font-size: 0.75rem;
        color: #888;
        text-align: right;
        margin-top: 4px;
    }
    .tag-btn {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.15s;
    }
    .tag-btn:hover {
        background: #e5e7eb;
    }
    .tag-btn.active {
        background: #dbeafe;
        border-color: #3b82f6;
        color: #1d4ed8;
        font-weight: 600;
    }
    .dynamic-section { transition: opacity 0.2s; }
    .dynamic-section.hidden { display: none !important; }
</style>
@endpush

@push('styles')
<style>
    /* === ステップインジケータ（スクロール追従） === */
    .step-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 12px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        position: sticky;
        top: 10px;
        z-index: 50;
        border: 1px solid #e5e7eb;
    }
    .step-item {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 20px;
        white-space: nowrap;
        font-size: 0.8rem;
        font-weight: 600;
        color: #6b7280;
        background: #f3f4f6;
        flex-shrink: 0;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
    }
    .step-item.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        transform: scale(1.05);
    }
    .step-item.done {
        background: #dcfce7;
        color: #166534;
    }
    .step-item:hover:not(.active) {
        background: #e5e7eb;
    }
    .step-item .step-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        font-size: 0.78rem;
        font-weight: 700;
    }
    .step-item:not(.active):not(.done) .step-num {
        background: #d1d5db;
        color: white;
    }
    .step-item.done .step-num {
        background: #16a34a;
        color: white;
    }
    .step-item.done .step-num::before {
        content: '✓';
    }
    .step-item.done .step-num { font-size: 0; }
    .step-item.done .step-num::before { font-size: 0.85rem; }
    .step-arrow {
        color: #9ca3af;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    @media (max-width: 600px) {
        .step-item { font-size: 0.7rem; padding: 4px 7px; }
        .step-item .step-num { width: 18px; height: 18px; font-size: 0.68rem; }
        .step-item span:not(.step-num) { font-size: 0.72rem; }
    }

    /* 各セクションに scroll margin を入れて sticky のヘッダに被らないように */
    .step-target { scroll-margin-top: 80px; transition: border-color 0.3s, box-shadow 0.3s; }

    /* 入力完了したセクションは緑の枠で表示 */
    .step-target.completed { border: 2px solid #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12); }
    .step-target.completed .card-header { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #166534; }
    .step-target.completed .card-header::after { content: ' ✓ 入力済み'; font-weight: 700; color: #16a34a; }
    .step-target.completed h3 { color: #166534; }

    /* 未入力の必須項目があるセクションは薄い黄色 */
    .step-target.pending input[required]:placeholder-shown + label,
    .step-target.pending textarea[required]:placeholder-shown,
    .step-target.pending select[required]:invalid {
        border-color: #fde68a;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>📦 投稿を作成</h1>
    <a href="{{ route('admin.purchase-posts.index') }}" class="btn btn-secondary">← 一覧に戻る</a>
</div>

{{-- ステップインジケータ（スクロール追従・クリックで該当セクションへジャンプ） --}}
<div class="step-indicator" id="stepIndicator">
    <a href="#step-1" class="step-item active" data-step="1">
        <span class="step-num">1</span>
        <span>商品・画像</span>
    </a>
    <span class="step-arrow">→</span>
    <a href="#step-2" class="step-item" data-step="2">
        <span class="step-num">2</span>
        <span>お客様情報</span>
    </a>
    <span class="step-arrow">→</span>
    <a href="#step-3" class="step-item" data-step="3">
        <span class="step-num">3</span>
        <span>AI で本文作成</span>
    </a>
    <span class="step-arrow">→</span>
    <a href="#step-4" class="step-item" data-step="4">
        <span class="step-num">4</span>
        <span>プレビュー</span>
    </a>
    <span class="step-arrow">→</span>
    <a href="#step-5" class="step-item" data-step="5">
        <span class="step-num">5</span>
        <span>投稿</span>
    </a>
</div>

<form method="POST" action="{{ route('admin.purchase-posts.store') }}" enctype="multipart/form-data" id="postForm">
    @csrf

    {{-- 業種モード インジケーター（店舗選択後に動的更新） --}}
    <div id="businessTypeIndicator" style="display:none;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:0.85rem;color:#075985;">
        🏷️ 業種モード: <strong id="bt_indicator_name"></strong>
        <span style="font-size:0.75rem;color:#0369a1;margin-left:6px;">（業種に合わせてフォームの項目とプリセットが自動で切り替わります）</span>
    </div>

    {{-- 管理番号API連携（業種マスタ側の use_pawn_system が true の業種で表示） --}}
    <div class="card dynamic-section hidden" style="margin-bottom:20px;" id="pawnSystemSection">
        <div class="card-header">🔗 管理番号API連携</div>
        <div class="card-body">
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <div class="form-group" style="flex:1;margin-bottom:0;">
                    <label for="manage_number">管理番号</label>
                    <input type="text" id="manage_number" placeholder="例：001_2603K55" style="font-size:1rem;">
                </div>
                <button type="button" class="btn btn-primary" id="fetchStockBtn" onclick="fetchStock()" style="padding:8px 20px;white-space:nowrap;">
                    📥 在庫取得
                </button>
            </div>
            <div id="fetchResult" style="margin-top:8px;font-size:0.85rem;display:none;"></div>
        </div>
    </div>

    {{-- 基本情報 + 画像 --}}
    <div class="card step-target" id="step-1" style="margin-bottom:20px;">
        <div class="card-header">📋 ステップ 1：商品情報と画像</div>
        <div class="card-body">
            <div class="two-col">
                <div class="form-group">
                    <label for="store_id">投稿店舗 <span style="color:#ef4444">*</span></label>
                    <select id="store_id" name="store_id" required>
                        <option value="">店舗を選択</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                    @error('store_id') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="category">カテゴリ <span style="color:#ef4444">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">先に店舗を選択してください</option>
                    </select>
                    @error('category') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col dynamic-section" id="brandProductSection">
                <div class="form-group">
                    <label for="brand_name" id="label_brand_name">ブランド名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name') }}" required placeholder="例：メーカー名・ブランド名・タイトルなど">
                    <p class="form-hint" id="hint_brand_name" style="font-size:0.74rem;color:#9ca3af;margin-top:3px;"></p>
                    @error('brand_name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="product_name" id="label_product_name">商品名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="product_name" name="product_name" value="{{ old('product_name') }}" required placeholder="例：商品名・型番・モデル名など">
                    <p class="form-hint" id="hint_product_name" style="font-size:0.74rem;color:#9ca3af;margin-top:3px;"></p>
                    @error('product_name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="product_status">状態 <span style="color:#ef4444">*</span></label>
                    <select id="product_status" name="product_status" required>
                        {{-- JS で動的に更新 --}}
                        <option value="">先に店舗を選択してください</option>
                    </select>
                </div>

                <div class="form-group dynamic-section" id="rankSection">
                    <label for="rank">ランク</label>
                    <select id="rank" name="rank">
                        <option value="" {{ old('rank') === '' ? 'selected' : '' }}>選択してください</option>
                        <option value="S" {{ old('rank') === 'S' ? 'selected' : '' }}>S（新品・未使用）</option>
                        <option value="A" {{ old('rank') === 'A' ? 'selected' : '' }}>A（美品）</option>
                        <option value="B" {{ old('rank') === 'B' ? 'selected' : '' }}>B（良品）</option>
                        <option value="C" {{ old('rank') === 'C' ? 'selected' : '' }}>C（一般的な使用感）</option>
                        <option value="D" {{ old('rank') === 'D' ? 'selected' : '' }}>D（ジャンク品）</option>
                    </select>
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="wp_tag_name">タグ</label>
                    <input type="text" id="wp_tag_name" name="wp_tag_name" value="{{ old('wp_tag_name') }}" placeholder="例：WordPress用のタグ名">
                    <p class="form-hint">WordPressのタグに設定されます</p>
                </div>
            </div>

            <div class="form-group">
                <label for="custom_hashtags">#️⃣ ハッシュタグ（Instagram・Facebook用）</label>
                <textarea id="custom_hashtags" name="custom_hashtags" rows="3" placeholder="店舗を選択するとデフォルトのハッシュタグがセットされます" style="font-size:0.9rem;line-height:1.6;">{{ old('custom_hashtags') }}</textarea>
                <p class="form-hint">1行に1つ、#なしで入力。投稿時にここの内容が #付きで付与されます。</p>
            </div>

            <div class="form-group">
                <label>画像 <span style="color:#ef4444">*</span></label>
                <div style="display:flex;gap:20px;align-items:flex-start;">
                    <div class="image-preview" onclick="document.getElementById('image').click()">
                        <div class="placeholder" id="imagePlaceholder">
                            📷<br>クリックして<br>画像を選択
                        </div>
                        <img id="imagePreviewImg" style="display:none;" alt="プレビュー">
                    </div>
                    <div>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png" style="display:none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('image').click()">📁 ファイルを選択</button>
                        <p class="form-hint" style="margin-top:8px;">推奨: 1080×1080px（正方形）<br>JPG/PNG、10MB以内</p>
                        @error('image') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- お客様・取引情報（AI生成用） --}}
    <div class="card step-target" id="step-2" style="margin-bottom:20px;">
        <div class="card-header" id="step2_header">👤 ステップ 2：お客様情報（AI 生成の参考・任意）</div>
        <div class="card-body">
            <div class="two-col">
                <div class="form-group">
                    <label for="customer_gender">性別</label>
                    <select id="customer_gender" name="customer_gender">
                        <option value="">不明</option>
                        <option value="男性" {{ old('customer_gender') === '男性' ? 'selected' : '' }}>男性</option>
                        <option value="女性" {{ old('customer_gender') === '女性' ? 'selected' : '' }}>女性</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="customer_age">見た目の年齢</label>
                    <select id="customer_age" name="customer_age">
                        <option value="">不明</option>
                        <option value="20" {{ old('customer_age') === '20' ? 'selected' : '' }}>20代</option>
                        <option value="30" {{ old('customer_age') === '30' ? 'selected' : '' }}>30代</option>
                        <option value="40" {{ old('customer_age') === '40' ? 'selected' : '' }}>40代</option>
                        <option value="50" {{ old('customer_age') === '50' ? 'selected' : '' }}>50代</option>
                        <option value="60" {{ old('customer_age') === '60' ? 'selected' : '' }}>60代以上</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="customer_reason" id="label_customer_reason">ご利用の経緯</label>
                <div id="tags_customer_reason" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                    {{-- JS で動的に更新 --}}
                </div>
                <input type="text" id="customer_reason" name="customer_reason" value="{{ old('customer_reason') }}" placeholder="選択するか直接入力">
            </div>
            <div class="two-col dynamic-section" id="conditionAccessoriesSection">
                <div class="form-group">
                    <label for="product_condition" id="label_product_condition">状態の補足</label>
                    <div id="tags_product_condition" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        {{-- JS で業種ごとに動的更新 --}}
                    </div>
                    <input type="text" id="product_condition" name="product_condition" value="{{ old('product_condition') }}" placeholder="選択するか直接入力">
                </div>
                <div class="form-group">
                    <label for="accessories" id="label_accessories">付属品・備考</label>
                    <div id="tags_accessories" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        {{-- JS で動的に更新 --}}
                    </div>
                    <input type="text" id="accessories" name="accessories" value="{{ old('accessories') }}" placeholder="選択するか直接入力（複数可）">
                </div>
            </div>
        </div>
    </div>

    {{-- タイトル --}}
    <div class="block-section step-target" id="step-3">
        <h3>🔷 ステップ 3：タイトル・本文（AI で文章作成）</h3>
        <p class="form-hint" id="block1Hint" style="margin-bottom:10px;">店舗を選択すると、業種に応じたテンプレートが自動生成されます。</p>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block1_text" name="block1_text" rows="3" required>{{ old('block1_text') }}</textarea>
            <div class="char-count"><span id="block1Count">0</span> / 1,500</div>
        </div>
        @error('block1_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- 本文（エピソード） --}}
    <div class="block-section">
        <h3>🔷 本文（エピソード）（AI自動生成）</h3>
        <div style="display:flex;gap:8px;margin-bottom:12px;">
            <button type="button" class="btn-generate" id="generateBtn" onclick="generateEpisode()">
                ✨ AIでエピソードを生成
            </button>
        </div>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block2_text" name="block2_text" rows="10" required>{{ old('block2_text') }}</textarea>
            <div class="char-count"><span id="block2Count">0</span> / 1,500</div>
        </div>
        @error('block2_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- フッター --}}
    <div class="block-section">
        <h3>🔷 フッター（店舗別エリア情報）</h3>
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <div style="display:flex;gap:6px;align-items:center;">
                <input type="text" id="footer_area" placeholder="エリア名（例：○○市△△・□□エリア）" style="padding:6px 10px;border:2px solid #e5e7eb;border-radius:6px;font-size:0.8rem;width:280px;">
                <button type="button" class="btn-generate btn-sm" onclick="generateFooter()" style="padding:6px 12px;font-size:0.8rem;">✨ AI生成</button>
            </div>
        </div>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block3_text" name="block3_text" rows="3" required>{{ old('block3_text') }}</textarea>
            <div class="char-count"><span id="block3Count">0</span> / 1,500</div>
        </div>
        <p class="form-hint">店舗選択で自動セットされます。「○○」はカテゴリ選択時に自動置換されます。</p>
        @error('block3_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- プレビュー --}}
    <div class="card step-target" id="step-4" style="margin-bottom:20px;">
        <div class="card-header">👁️ ステップ 4：投稿プレビュー</div>
        <div class="card-body">
            <div class="preview-box" id="previewBox">タイトル・本文・フッターを入力すると、ここにプレビューが表示されます</div>
            <div class="char-count" style="margin-top:8px;">合計文字数: <strong id="totalCount">0</strong> / 1,500</div>
        </div>
    </div>

    {{-- 投稿ボタン --}}
    <div class="step-target" id="step-5" style="display:flex;flex-direction:column;gap:12px;align-items:center;margin-bottom:40px;">
        <p style="margin:0;color:#6b7280;font-size:0.88rem;">📮 ステップ 5：内容を確認したら投稿してください</p>
        <button type="submit" class="btn btn-primary" style="padding:14px 40px;font-size:1rem;" id="submitBtn">
            🚀 投稿する
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
    // ========================================
    // ステップインジケータ スクロール連動 + 完了判定
    // ========================================
    (function() {
        const stepItems = document.querySelectorAll('.step-item');
        const targets = Array.from(document.querySelectorAll('.step-target'));
        if (!stepItems.length || !targets.length) return;

        // セクション完了判定（セクション内の必須項目が全て埋まっているか）
        function isStepCompleted(section) {
            if (!section) return false;
            const id = section.id;

            // Step ごとのカスタム判定
            if (id === 'step-1') {
                // 店舗・カテゴリ・ブランド・商品名・状態・画像
                const storeId = document.getElementById('store_id');
                const category = document.getElementById('category');
                const brand = document.getElementById('brand_name');
                const product = document.getElementById('product_name');
                const status = document.getElementById('product_status');
                const image = document.getElementById('image');
                if (!storeId || !storeId.value) return false;
                if (!category || !category.value) return false;
                // ブランド・商品名が非表示化されている業種もあるので、表示されていれば判定
                if (brand && brand.offsetParent !== null && !brand.value.trim()) return false;
                if (product && product.offsetParent !== null && !product.value.trim()) return false;
                if (!status || !status.value) return false;
                if (!image || !image.files || !image.files.length) return false;
                return true;
            }
            if (id === 'step-2') {
                // お客様情報は任意なので、何か 1 つ以上入ったら完了扱い
                const any = section.querySelectorAll('input[type="text"], input[type="checkbox"]:checked, select');
                for (const el of any) {
                    if (el.value && el.value.trim() !== '') return true;
                }
                // 何も入っていなくても「任意」なのでスキップ可能
                // → step-3 に進んでいれば OK（完了ではないけど進捗は見せない）
                return false;
            }
            if (id === 'step-3') {
                // タイトル・本文・フッターが全部埋まっていれば完了
                const b1 = document.getElementById('block1_text');
                const b2 = document.getElementById('block2_text');
                const b3 = document.getElementById('block3_text');
                return (b1 && b1.value.trim()) && (b2 && b2.value.trim()) && (b3 && b3.value.trim());
            }
            if (id === 'step-4') {
                // プレビューは step 1〜3 すべて埋まれば完了
                return isStepCompleted(document.getElementById('step-1')) &&
                       isStepCompleted(document.getElementById('step-3'));
            }
            if (id === 'step-5') {
                // 投稿ボタンは step 1〜3 が終わっていれば実行可能
                return isStepCompleted(document.getElementById('step-1')) &&
                       isStepCompleted(document.getElementById('step-3'));
            }
            return false;
        }

        function updateCompletion() {
            targets.forEach(section => {
                if (isStepCompleted(section)) {
                    section.classList.add('completed');
                } else {
                    section.classList.remove('completed');
                }
            });
            // ステップインジケータの done 表示も完了ベースに
            stepItems.forEach(item => {
                const step = 'step-' + item.dataset.step;
                const target = document.getElementById(step);
                item.classList.remove('done');
                if (target && isStepCompleted(target) && !item.classList.contains('active')) {
                    item.classList.add('done');
                }
            });
        }

        function updateActiveStep() {
            let current = null;
            const threshold = window.innerHeight / 3;
            for (const el of targets) {
                const rect = el.getBoundingClientRect();
                if (rect.top <= threshold) current = el.id;
            }
            stepItems.forEach(item => {
                const step = 'step-' + item.dataset.step;
                item.classList.remove('active');
                if (current === step) {
                    item.classList.add('active');
                }
            });
            updateCompletion();
        }
        window.addEventListener('scroll', updateActiveStep, { passive: true });
        window.addEventListener('resize', updateActiveStep);
        // 入力変化で完了判定を更新
        document.addEventListener('input', updateCompletion);
        document.addEventListener('change', updateCompletion);
        updateActiveStep();
        updateCompletion();

        // ステップクリックで滑らかにスクロール
        stepItems.forEach(item => {
            item.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (!href || !href.startsWith('#')) return;
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    })();

    // ========================================
    // 店舗・業種データ（サーバーから渡す）
    // ========================================
    var storeData = {!! json_encode($stores->mapWithKeys(function($store) {
        $bt = $store->businessType;
        return [$store->id => [
            'name' => $store->name,
            'business_type_name' => $bt->name ?? '',
            'business_type_slug' => $bt->slug ?? '',
            'use_pawn_system' => $bt->use_pawn_system ?? false,
            'use_product_rank' => $bt->use_product_rank ?? false,
            'post_action_word' => $bt->post_action_word ?? '投稿',
            'post_categories' => $bt->post_categories ?? [],
            'post_title_template' => $bt->post_title_template ?? '{brand} {product}のご紹介です。',
            'post_status_options' => $bt->post_status_options ?? ['通常', '新着', 'おすすめ'],
            'post_reason_presets' => $bt->post_reason_presets ?? [],
            'post_accessory_presets' => $bt->post_accessory_presets ?? [],
            'post_hidden_fields' => $bt->post_hidden_fields ?? [],
            'base_context' => $bt->base_context ?? '',
            'default_hashtags' => $bt->post_default_hashtags ?? '',
            'store_hashtags' => $store->custom_hashtags ?? '',
        ]];
    }), JSON_UNESCAPED_UNICODE) !!};

    var storeFooterMap = {!! json_encode($stores->mapWithKeys(function($store) {
        $footer = $store->postTemplate->template_text ?? '';
        if (!$footer) {
            $footer = $store->name . 'へぜひご相談ください。';
        }
        return [(string)$store->id => $footer];
    }), JSON_UNESCAPED_UNICODE) !!};

    var oldCategory = {!! json_encode(old('category', '')) !!};
    var oldStatus = {!! json_encode(old('product_status', '')) !!};

    // ========================================
    // HTMLエスケープ
    // ========================================
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ========================================
    // 店舗選択時にフォームを業種に合わせて更新
    // ========================================
    function onStoreChange() {
        var storeId = document.getElementById('store_id').value;
        var data = storeData[storeId];
        var hiddenFields = (data && data.post_hidden_fields) ? data.post_hidden_fields : [];

        // ランクセクション表示/非表示
        var rankSection = document.getElementById('rankSection');
        if (data && data.use_product_rank) {
            rankSection.classList.remove('hidden');
        } else {
            rankSection.classList.add('hidden');
            document.getElementById('rank').value = '';
        }

        // 業種に応じてフィールドを動的表示/非表示
        toggleFieldSection('brandProductSection', hiddenFields, ['brand_name', 'product_name']);
        toggleFieldSection('conditionAccessoriesSection', hiddenFields, ['product_condition', 'accessories']);

        // pawn-system連携セクション（use_pawn_system でない場合は非表示）
        var pawnSection = document.getElementById('pawnSystemSection');
        if (pawnSection) {
            if (data && data.use_pawn_system) {
                pawnSection.classList.remove('hidden');
            } else {
                pawnSection.classList.add('hidden');
            }
        }

        // カテゴリ更新
        updateCategories(data);

        // 商品状態更新
        updateStatusOptions(data);

        // プリセットタグ更新
        updateReasonPresets(data);
        updateAccessoryPresets(data);
        updateConditionPresets(data);

        // 業種に合わせてラベル・ヒントを切り替え
        updateLabelsForBusinessType(data);

        // ハッシュタグを業種＋店舗のデフォルトでセット
        updateDefaultHashtags(data);

        // ブロック③を自動セット
        updateBlock3();
    }

    // ========================================
    // 業種ごとに状態の補足プリセットを切り替え
    // 質屋・買取店：状態の細かい記述（キズ・使用感など）
    // それ以外：もう少し汎用的な表現（ない場合は非表示）
    // ========================================
    var CONDITION_PRESETS_BY_TYPE = {
        // 物販・買取系（slug or pawn-flag で判定）
        pawn:        ['目立つキズなし', '全体的に良好', '多少の使用感あり', '未使用品', '新品同様', '小キズあり', '汚れあり', '動作確認済み', 'ジャンク品'],
        car_dealer:  ['修復歴なし', '禁煙車', 'ワンオーナー', '記録簿あり', '走行距離少なめ', '内外装良好', '小キズあり', '要メンテナンス'],
        general:     ['新品同様', '良好', '通常', '使用感あり'],
    };

    function updateConditionPresets(data) {
        var container = document.getElementById('tags_product_condition');
        if (!container) return;
        container.innerHTML = '';

        var slug = data && data.business_type_slug ? data.business_type_slug : '';
        var presets = CONDITION_PRESETS_BY_TYPE[slug];
        if (!presets) {
            // 未知の業種は use_pawn_system フラグで推定
            presets = (data && data.use_pawn_system)
                ? CONDITION_PRESETS_BY_TYPE.pawn
                : (data && data.use_product_rank ? CONDITION_PRESETS_BY_TYPE.general : []);
        }

        presets.forEach(function(c) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tag-btn';
            btn.dataset.field = 'product_condition';
            btn.dataset.value = c;
            btn.textContent = c;
            btn.onclick = function() { toggleTag(this); };
            container.appendChild(btn);
        });

        syncTagButtons('product_condition');
    }

    // ========================================
    // 業種ごとにラベル・ヒントを切り替え
    // ========================================
    function updateLabelsForBusinessType(data) {
        if (!data) return;
        var slug = data.business_type_slug || '';

        // 業種インジケーターを更新
        var indicator = document.getElementById('businessTypeIndicator');
        var indicatorName = document.getElementById('bt_indicator_name');
        if (indicator && indicatorName) {
            indicatorName.textContent = (data.business_type_name || '未設定') + (data.post_action_word ? '（' + data.post_action_word + '投稿）' : '');
            indicator.style.display = '';
        }

        // ステップ 2 のヘッダーを業種に合わせて
        var step2Header = document.getElementById('step2_header');
        var step2HeaderText = {
            pawn:       '👤 ステップ 2：お客様情報（AI 生成の参考・任意）',
            car_dealer: '👤 ステップ 2：お客様情報（AI 生成の参考・任意）',
            yakiniku:   '🍽 ステップ 2：来店情報（AI 生成の参考・任意）',
            general:    '👤 ステップ 2：お客様情報（AI 生成の参考・任意）',
        };
        if (step2Header) step2Header.textContent = step2HeaderText[slug] || step2HeaderText.general;

        // 業種別ラベル
        var labels = {
            pawn:       { brand: 'ブランド名',          brandHint: 'メーカー・ブランド・銘柄など',           product: '商品名',           productHint: '型番やモデル名（例：サブマリーナ 116610LN）', condition: '状態の補足',  accessories: '付属品・備考', reason: 'ご利用の経緯' },
            car_dealer: { brand: 'メーカー',            brandHint: 'トヨタ・ホンダ・BMW など',                product: '車種・グレード',     productHint: 'プリウス S グレード など',                       condition: '車両の状態',  accessories: 'オプション・備考', reason: 'ご来店の目的' },
            yakiniku:   { brand: 'メニュー / コース名',  brandHint: '黒毛和牛コース・ホルモンセット など',     product: 'お品書き・特徴',     productHint: 'A5 黒毛和牛 / 飲み放題付き など',             condition: '提供スタイル', accessories: '備考',         reason: 'ご来店の経緯' },
            general:    { brand: 'タイトル',            brandHint: '投稿の主役となる名前',                    product: 'サブタイトル',        productHint: '補足の説明',                                 condition: '備考',         accessories: '備考',         reason: 'ご利用の経緯' },
        };
        var l = labels[slug] || labels.general;

        function setLabelText(elId, txt, required) {
            var el = document.getElementById(elId);
            if (!el) return;
            el.innerHTML = txt + (required ? ' <span style="color:#ef4444">*</span>' : '');
        }
        setLabelText('label_brand_name',          l.brand,       true);
        setLabelText('label_product_name',        l.product,     true);
        setLabelText('label_product_condition',   l.condition,   false);
        setLabelText('label_accessories',         l.accessories, false);
        setLabelText('label_customer_reason',     l.reason,      false);

        var brandHint = document.getElementById('hint_brand_name');
        var productHint = document.getElementById('hint_product_name');
        if (brandHint)   brandHint.textContent = l.brandHint || '';
        if (productHint) productHint.textContent = l.productHint || '';
    }

    // デフォルトハッシュタグをセット（業種＋店舗）
    function updateDefaultHashtags(data) {
        var textarea = document.getElementById('custom_hashtags');
        if (!textarea || !data) return;
        var tags = [];
        // 業種のデフォルトハッシュタグ
        if (data.default_hashtags) {
            data.default_hashtags.split('\n').forEach(function(t) {
                t = t.trim().replace(/^#/, '');
                if (t) tags.push(t);
            });
        }
        // 店舗のカスタムハッシュタグ
        if (data.store_hashtags) {
            data.store_hashtags.split('\n').forEach(function(t) {
                t = t.trim().replace(/^#/, '');
                if (t) tags.push(t);
            });
        }
        // 重複除去
        var unique = [];
        tags.forEach(function(t) { if (unique.indexOf(t) === -1) unique.push(t); });
        textarea.value = unique.join('\n');
    }

    // セクションの表示/非表示を切り替え＋required属性の管理
    function toggleFieldSection(sectionId, hiddenFields, fieldNames) {
        var section = document.getElementById(sectionId);
        if (!section) return;

        var shouldHide = fieldNames.some(function(f) { return hiddenFields.indexOf(f) !== -1; });
        if (shouldHide) {
            section.classList.add('hidden');
            // required属性を外す＋値をクリア
            fieldNames.forEach(function(f) {
                var el = document.getElementById(f);
                if (el) {
                    el.removeAttribute('required');
                    if (!el.value) el.value = '';
                }
            });
        } else {
            section.classList.remove('hidden');
            // brand_name, product_name は required に戻す
            ['brand_name', 'product_name'].forEach(function(f) {
                if (fieldNames.indexOf(f) !== -1) {
                    var el = document.getElementById(f);
                    if (el) el.setAttribute('required', 'required');
                }
            });
        }
    }

    // カテゴリを業種に応じて更新
    function updateCategories(data) {
        var select = document.getElementById('category');
        var currentVal = select.value || oldCategory;
        select.innerHTML = '<option value="">カテゴリを選択</option>';

        if (data && data.post_categories) {
            data.post_categories.forEach(function(cat) {
                var opt = document.createElement('option');
                opt.value = cat.name;
                opt.textContent = cat.name;
                if (cat.name === currentVal) opt.selected = true;
                select.appendChild(opt);
            });
        }
        oldCategory = ''; // 初回のみ old() を使用
    }

    // 商品状態オプション更新
    function updateStatusOptions(data) {
        var select = document.getElementById('product_status');
        var currentVal = select.value || oldStatus;
        select.innerHTML = '';

        var options = (data && data.post_status_options) ? data.post_status_options : ['通常', '新着', 'おすすめ'];
        options.forEach(function(status, idx) {
            var opt = document.createElement('option');
            opt.value = status;
            opt.textContent = status;
            if (status === currentVal || (idx === 0 && !currentVal)) opt.selected = true;
            select.appendChild(opt);
        });
        oldStatus = '';
    }

    // 利用経緯プリセット更新
    function updateReasonPresets(data) {
        var container = document.getElementById('tags_customer_reason');
        container.innerHTML = '';

        var presets = (data && data.post_reason_presets && data.post_reason_presets.length > 0)
            ? data.post_reason_presets
            : ['知人の紹介', 'ネットで見つけた', '以前から気になっていた', 'リピート利用'];

        presets.forEach(function(reason) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tag-btn';
            btn.dataset.field = 'customer_reason';
            btn.dataset.value = reason;
            btn.textContent = reason;
            btn.onclick = function() { toggleTag(this); };
            container.appendChild(btn);
        });

        syncTagButtons('customer_reason');
    }

    // 付属品プリセット更新
    function updateAccessoryPresets(data) {
        var container = document.getElementById('tags_accessories');
        container.innerHTML = '';

        var presets = (data && data.post_accessory_presets && data.post_accessory_presets.length > 0)
            ? data.post_accessory_presets
            : [];

        presets.forEach(function(acc) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tag-btn';
            btn.dataset.field = 'accessories';
            btn.dataset.value = acc;
            btn.textContent = acc;
            btn.onclick = function() { toggleTag(this); };
            container.appendChild(btn);
        });

        syncTagButtons('accessories');
    }

    // ========================================
    // ブロック③を店舗＋カテゴリから自動生成
    // ========================================
    function updateBlock3() {
        var storeId = document.getElementById('store_id').value;
        var categoryName = document.getElementById('category').value;
        var footer = storeFooterMap[storeId];
        if (footer) {
            if (categoryName) {
                footer = footer.replace(/○○/g, categoryName);
            }
            document.getElementById('block3_text').value = footer;
            updateCounts();
        }
    }

    // ========================================
    // イベントリスナー
    // ========================================
    document.getElementById('store_id').addEventListener('change', onStoreChange);

    // 画像プレビュー
    document.getElementById('image').addEventListener('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('imagePreviewImg').src = ev.target.result;
                document.getElementById('imagePreviewImg').style.display = 'block';
                document.getElementById('imagePlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // 文字数カウント
    function updateCounts() {
        var b1 = document.getElementById('block1_text').value;
        var b2 = document.getElementById('block2_text').value;
        var b3 = document.getElementById('block3_text').value;
        document.getElementById('block1Count').textContent = b1.length;
        document.getElementById('block2Count').textContent = b2.length;
        document.getElementById('block3Count').textContent = b3.length;

        var total = b1.length + b2.length + b3.length;
        document.getElementById('totalCount').textContent = total;

        // プレビュー更新（○○をカテゴリ名に置換して表示）
        var categoryName = document.getElementById('category').value || '○○';
        var b3Replaced = b3.replace(/○○/g, categoryName);
        var preview = '';
        if (b1) preview += b1;
        if (b2) preview += '\n\n' + b2;
        if (b3Replaced) preview += '\n\n' + b3Replaced;
        document.getElementById('previewBox').textContent = preview || 'タイトル・本文・フッターを入力すると、ここにプレビューが表示されます';
    }

    document.getElementById('block1_text').addEventListener('input', updateCounts);
    document.getElementById('block2_text').addEventListener('input', updateCounts);
    document.getElementById('block3_text').addEventListener('input', updateCounts);

    // ========================================
    // タグボタンのトグル（選択/解除）
    // ========================================
    function toggleTag(btn) {
        var fieldId = btn.dataset.field;
        var value = btn.dataset.value;
        var field = document.getElementById(fieldId);
        var values = field.value ? field.value.split('、').map(function(v){ return v.trim(); }).filter(Boolean) : [];
        var idx = values.indexOf(value);
        if (idx !== -1) {
            values.splice(idx, 1);
        } else {
            values.push(value);
        }
        field.value = values.join('、');
        syncTagButtons(fieldId);
    }

    function syncTagButtons(fieldId) {
        var field = document.getElementById(fieldId);
        var container = document.getElementById('tags_' + fieldId);
        if (!field || !container) return;
        var values = field.value ? field.value.split('、').map(function(v){ return v.trim(); }).filter(Boolean) : [];
        container.querySelectorAll('.tag-btn').forEach(function(btn) {
            if (values.indexOf(btn.dataset.value) !== -1) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // ========================================
    // ブロック①自動生成（業種テンプレート使用）
    // ========================================
    function autoGenerateBlock1() {
        var brand = document.getElementById('brand_name').value;
        var product = document.getElementById('product_name').value;
        var status = document.getElementById('product_status').value;
        var category = document.getElementById('category').value;
        var storeId = document.getElementById('store_id').value;
        var data = storeData[storeId];
        var hiddenFields = (data && data.post_hidden_fields) ? data.post_hidden_fields : [];

        var template = (data && data.post_title_template)
            ? data.post_title_template
            : '{brand} {product}のご紹介です。';

        // brand/product が非表示の場合はカテゴリ選択だけでタイトル生成
        var brandHidden = hiddenFields.indexOf('brand_name') !== -1;
        if (brandHidden) {
            if (category) {
                var text = template
                    .replace(/{brand}/g, '')
                    .replace(/{product}/g, '')
                    .replace(/{status}/g, status || '')
                    .replace(/{category}/g, category)
                    .replace(/\s+/g, ' ').trim();

                document.getElementById('block1_text').value = text;
                updateCounts();
            }
        } else {
            if (brand && product) {
                var text = template
                    .replace(/{brand}/g, brand)
                    .replace(/{product}/g, product)
                    .replace(/{status}/g, status || '')
                    .replace(/{category}/g, category || '○○');

                document.getElementById('block1_text').value = text;
                updateCounts();
            }
        }

        // ヒントもテンプレートに合わせて更新
        document.getElementById('block1Hint').textContent =
            'テンプレート: ' + template.replace(/{brand}/g, '「ブランド名」').replace(/{product}/g, '「商品名」').replace(/{status}/g, '「状態」').replace(/{category}/g, '「カテゴリ」');
    }

    document.getElementById('brand_name').addEventListener('input', autoGenerateBlock1);
    document.getElementById('product_name').addEventListener('input', autoGenerateBlock1);
    document.getElementById('product_status').addEventListener('change', autoGenerateBlock1);
    document.getElementById('category').addEventListener('change', function() {
        autoGenerateBlock1();
        updateBlock3();
    });

    // ========================================
    // ブロック② AI生成
    // ========================================
    function generateEpisode() {
        var storeId = document.getElementById('store_id').value;
        var data = storeData[storeId];
        var hiddenFields = (data && data.post_hidden_fields) ? data.post_hidden_fields : [];
        var brand = document.getElementById('brand_name').value;
        var product = document.getElementById('product_name').value;
        var category = document.getElementById('category').value;

        // brand/productが非表示ならカテゴリ必須、そうでなければbrand/product必須
        var brandHidden = hiddenFields.indexOf('brand_name') !== -1;
        if (brandHidden) {
            if (!category) {
                alert('カテゴリを選択してください');
                return;
            }
        } else {
            if (!brand || !product) {
                alert('ブランド名と商品名を先に入力してください');
                return;
            }
        }

        var btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> 生成中...';

        var formData = new FormData();
        formData.append('store_id', storeId);
        formData.append('brand_name', brand);
        formData.append('product_name', product);
        formData.append('category', category);
        formData.append('customer_gender', document.getElementById('customer_gender').value);
        formData.append('customer_age', document.getElementById('customer_age').value);
        formData.append('customer_reason', document.getElementById('customer_reason').value);
        formData.append('product_condition', document.getElementById('product_condition').value);
        formData.append('accessories', document.getElementById('accessories').value);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("admin.purchase-posts.generate-episode") }}', {
            method: 'POST',
            body: formData,
        })
        .then(function(res) {
            if (!res.ok) throw new Error('サーバーエラー (' + res.status + ')');
            return res.json();
        })
        .then(function(data) {
            if (data.success) {
                document.getElementById('block2_text').value = data.text;
                updateCounts();
            } else {
                alert('AI生成エラー: ' + (data.error || '不明なエラー'));
            }
        })
        .catch(function(err) {
            alert('通信エラー: ' + err.message);
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '✨ AIでエピソードを生成';
        });
    }

    // ========================================
    // ブロック③ AI生成
    // ========================================
    function generateFooter() {
        var storeId = document.getElementById('store_id').value;
        var area = document.getElementById('footer_area').value;

        if (!storeId) {
            alert('店舗を選択してください');
            return;
        }
        if (!area) {
            alert('エリア名を入力してください');
            return;
        }

        var formData = new FormData();
        formData.append('store_id', storeId);
        formData.append('area', area);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("admin.purchase-posts.generate-footer") }}', {
            method: 'POST',
            body: formData,
        })
        .then(function(res) {
            if (!res.ok) throw new Error('サーバーエラー (' + res.status + ')');
            return res.json();
        })
        .then(function(data) {
            if (data.success) {
                var text = data.text;
                var categoryName = document.getElementById('category').value;
                if (categoryName) {
                    text = text.replace(/○○/g, categoryName);
                }
                document.getElementById('block3_text').value = text;
                updateCounts();
            } else {
                alert('AI生成エラー: ' + (data.error || '不明なエラー'));
            }
        })
        .catch(function(err) {
            alert('通信エラー: ' + err.message);
        });
    }

    // ========================================
    // フォーム送信時の二重送信防止
    // ========================================
    document.getElementById('postForm').addEventListener('submit', function() {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> 投稿中...';
    });

    // ========================================
    // 初期化
    // ========================================
    updateCounts();
    syncTagButtons('product_condition');

    // old() で店舗がセットされている場合はフォームを初期化
    var initialStoreId = document.getElementById('store_id').value;
    if (initialStoreId) {
        onStoreChange();
    }

    // ========================================
    // pawn-system在庫取得
    // ========================================
    function fetchStock() {
        var manageNumber = document.getElementById('manage_number').value.trim();
        if (!manageNumber) {
            alert('管理番号を入力してください');
            return;
        }

        var btn = document.getElementById('fetchStockBtn');
        var resultDiv = document.getElementById('fetchResult');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> 取得中...';
        resultDiv.style.display = 'none';

        var formData = new FormData();
        formData.append('manage_number', manageNumber);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("admin.purchase-posts.fetch-stock") }}', {
            method: 'POST',
            body: formData,
        })
        .then(function(res) {
            if (!res.ok) throw new Error('サーバーエラー (' + res.status + ')');
            return res.json();
        })
        .then(function(data) {
            if (data.success) {
                applyStockData(data.data);
                var msg = '✅ ' + escHtml(manageNumber) + ' の在庫情報を取得しました';
                if (data.data.customer_name) {
                    var nameDisplay = escHtml(data.data.customer_name);
                    if (data.data.customer_kana) {
                        nameDisplay += '（' + escHtml(data.data.customer_kana) + '）';
                    }
                    msg += '<br><span style="font-size:0.85rem;">👤 顧客名: <strong>' + nameDisplay + '</strong></span>';
                }
                if (data.data.feature) {
                    msg += '<br><span style="font-size:0.85rem;">📋 特徴: ' + escHtml(data.data.feature) + '</span>';
                }
                resultDiv.innerHTML = '<span style="color:#059669;">' + msg + '</span>';
            } else {
                resultDiv.innerHTML = '<span style="color:#ef4444;">❌ ' + (data.message || '取得に失敗しました') + '</span>';
            }
            resultDiv.style.display = 'block';
        })
        .catch(function(err) {
            resultDiv.innerHTML = '<span style="color:#ef4444;">❌ 通信エラー: ' + err.message + '</span>';
            resultDiv.style.display = 'block';
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '📥 在庫取得';
        });
    }

    // 取得したデータをフォームに自動入力
    function applyStockData(data) {
        // 1. 店舗を名前で自動選択 → フォームを業種に合わせて再構築
        if (data.shop_name) {
            var storeSelect = document.getElementById('store_id');
            for (var i = 0; i < storeSelect.options.length; i++) {
                if (storeSelect.options[i].text.indexOf(data.shop_name) !== -1) {
                    storeSelect.value = storeSelect.options[i].value;
                    break;
                }
            }
        }
        // 店舗が未選択でも最初の店舗を自動選択
        var storeSelect = document.getElementById('store_id');
        if (!storeSelect.value) {
            for (var i = 0; i < storeSelect.options.length; i++) {
                if (storeSelect.options[i].value) {
                    storeSelect.value = storeSelect.options[i].value;
                    break;
                }
            }
        }
        // フォーム再構築（カテゴリ・ステータスのドロップダウンが作られる）
        onStoreChange();

        // 2. 再構築後にカテゴリ選択
        if (data.category) {
            var catSelect = document.getElementById('category');
            for (var i = 0; i < catSelect.options.length; i++) {
                if (catSelect.options[i].value === data.category) {
                    catSelect.value = data.category;
                    break;
                }
            }
        }

        // 3. 再構築後にステータス選択
        if (data.product_status) {
            var statusSelect = document.getElementById('product_status');
            for (var i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value === data.product_status) {
                    statusSelect.value = data.product_status;
                    break;
                }
            }
        }

        // ブランド名
        if (data.brand_name) {
            document.getElementById('brand_name').value = data.brand_name;
        }

        // 商品名
        if (data.product_name) {
            document.getElementById('product_name').value = data.product_name;
        }

        // ランク
        if (data.rank) {
            document.getElementById('rank').value = data.rank;
        }

        // タグにブランド名を設定
        if (data.brand_name) {
            document.getElementById('wp_tag_name').value = data.brand_name;
        }

        // 性別
        if (data.customer_gender) {
            document.getElementById('customer_gender').value = data.customer_gender;
        }

        // 年齢
        if (data.customer_age) {
            document.getElementById('customer_age').value = data.customer_age;
        }

        // 商品の状態を自動入力（featureから検出）
        if (data.detected_conditions && data.detected_conditions.length > 0) {
            document.getElementById('product_condition').value = data.detected_conditions.join('、');
        }

        // 付属品を自動入力（featureから検出）
        if (data.detected_accessories && data.detected_accessories.length > 0) {
            document.getElementById('accessories').value = data.detected_accessories.join('、');
        }

        // タグボタンの選択状態を同期
        syncTagButtons('customer_reason');
        syncTagButtons('product_condition');
        syncTagButtons('accessories');

        // ブロック①・③を自動生成
        autoGenerateBlock1();
        updateBlock3();
    }

    // Enterキーで在庫取得
    document.getElementById('manage_number').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            fetchStock();
        }
    });
</script>
@endpush
