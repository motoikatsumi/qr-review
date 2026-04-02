@extends('layouts.admin')

@section('title', '買取投稿作成')

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
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>📦 買取投稿を作成</h1>
    <a href="{{ route('admin.purchase-posts.index') }}" class="btn btn-secondary">← 一覧に戻る</a>
</div>

<form method="POST" action="{{ route('admin.purchase-posts.store') }}" enctype="multipart/form-data" id="postForm">
    @csrf

    {{-- 基本情報 --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">📋 基本情報</div>
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
                        <option value="">カテゴリを選択</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="brand_name">ブランド名（メーカー） <span style="color:#ef4444">*</span></label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name') }}" required placeholder="例：ROLEX ロレックス">
                    @error('brand_name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="product_name">商品名 <span style="color:#ef4444">*</span></label>
                    <input type="text" id="product_name" name="product_name" value="{{ old('product_name') }}" required placeholder="例：オイスターパーペチュアル 69160">
                    @error('product_name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="two-col">
                <div class="form-group">
                    <label for="product_status">状態 <span style="color:#ef4444">*</span></label>
                    <select id="product_status" name="product_status" required>
                        <option value="中古品" {{ old('product_status', '中古品') === '中古品' ? 'selected' : '' }}>中古品</option>
                        <option value="新品" {{ old('product_status') === '新品' ? 'selected' : '' }}>新品</option>
                        <option value="未使用品" {{ old('product_status') === '未使用品' ? 'selected' : '' }}>未使用品</option>
                    </select>
                </div>

                <div class="form-group">
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
                    <label for="wp_tag_name">タグ（ブランド名）</label>
                    <input type="text" id="wp_tag_name" name="wp_tag_name" value="{{ old('wp_tag_name') }}" placeholder="例：ロレックス">
                    <p class="form-hint">WordPressのタグに設定されます</p>
                </div>
            </div>

            <div class="form-group">
                <label>商品画像 <span style="color:#ef4444">*</span></label>
                <div style="display:flex;gap:20px;align-items:flex-start;">
                    <div class="image-preview" onclick="document.getElementById('image').click()">
                        <div class="placeholder" id="imagePlaceholder">
                            📷<br>クリックして<br>画像を選択
                        </div>
                        <img id="imagePreviewImg" style="display:none;" alt="プレビュー">
                    </div>
                    <div>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png" required style="display:none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('image').click()">📁 ファイルを選択</button>
                        <p class="form-hint" style="margin-top:8px;">推奨: 1080×1080px（正方形）<br>JPG/PNG、10MB以内</p>
                        @error('image') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- お客様情報（AI生成用） --}}
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">👤 お客様情報（AI生成の参考情報・任意）</div>
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
                <label for="customer_reason">売却理由</label>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                    @foreach(['使わなくなったため', '引っ越しのため', '買い替えのため', '資金が必要なため', '断捨離・整理のため', '遺品整理のため', 'プレゼントだが不要になった'] as $reason)
                        <button type="button" class="btn btn-sm" style="background:#f3f4f6;border:1px solid #d1d5db;border-radius:20px;padding:4px 12px;font-size:0.8rem;cursor:pointer;" onclick="appendToField('customer_reason', '{{ $reason }}')">{{ $reason }}</button>
                    @endforeach
                </div>
                <input type="text" id="customer_reason" name="customer_reason" value="{{ old('customer_reason') }}" placeholder="選択するか直接入力">
            </div>
            <div class="two-col">
                <div class="form-group">
                    <label for="product_condition">商品の状態</label>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        @foreach(['目立つキズなし', '全体的に良好', '多少の使用感あり', '未使用品', '新品同様', '小キズあり', '汚れあり', '動作確認済み'] as $cond)
                            <button type="button" class="btn btn-sm" style="background:#f3f4f6;border:1px solid #d1d5db;border-radius:20px;padding:4px 12px;font-size:0.8rem;cursor:pointer;" onclick="appendToField('product_condition', '{{ $cond }}')">{{ $cond }}</button>
                        @endforeach
                    </div>
                    <input type="text" id="product_condition" name="product_condition" value="{{ old('product_condition') }}" placeholder="選択するか直接入力">
                </div>
                <div class="form-group">
                    <label for="accessories">付属品</label>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                        @foreach(['箱', '保証書', '説明書', '替えベルト', '充電器', 'ケース', '袋', 'ギャランティカード', '鑑定書', 'コマ'] as $acc)
                            <button type="button" class="btn btn-sm" style="background:#f3f4f6;border:1px solid #d1d5db;border-radius:20px;padding:4px 12px;font-size:0.8rem;cursor:pointer;" onclick="appendToField('accessories', '{{ $acc }}')">{{ $acc }}</button>
                        @endforeach
                    </div>
                    <input type="text" id="accessories" name="accessories" value="{{ old('accessories') }}" placeholder="選択するか直接入力（複数可）">
                </div>
            </div>
        </div>
    </div>

    {{-- ブロック① --}}
    <div class="block-section">
        <h3>🔷 ブロック①：タイトル行</h3>
        <p class="form-hint" style="margin-bottom:10px;">「ブランド名 + 商品名 + 状態」をお買取りいたしました。人気の「ブランド名」の「カテゴリ」は状態を問わず高価買取中です。</p>
        <div class="form-group" style="margin-bottom:8px;">
            <textarea id="block1_text" name="block1_text" rows="3" required>{{ old('block1_text') }}</textarea>
            <div class="char-count"><span id="block1Count">0</span> / 1,500</div>
        </div>
        @error('block1_text') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
    </div>

    {{-- ブロック② --}}
    <div class="block-section">
        <h3>🔷 ブロック②：お客様エピソード（AI自動生成）</h3>
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

    {{-- ブロック③ --}}
    <div class="block-section">
        <h3>🔷 ブロック③：店舗別エリアフッター</h3>
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <div style="display:flex;gap:6px;align-items:center;">
                <input type="text" id="footer_area" placeholder="エリア名（例：鹿児島市西千石・天文館）" style="padding:6px 10px;border:2px solid #e5e7eb;border-radius:6px;font-size:0.8rem;width:280px;">
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
    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">👁️ 投稿プレビュー</div>
        <div class="card-body">
            <div class="preview-box" id="previewBox">ブロック①②③を入力すると、ここにプレビューが表示されます</div>
            <div class="char-count" style="margin-top:8px;">合計文字数: <strong id="totalCount">0</strong> / 1,500</div>
        </div>
    </div>

    {{-- 投稿ボタン --}}
    <div style="display:flex;gap:12px;justify-content:center;margin-bottom:40px;">
        <button type="submit" class="btn btn-primary" style="padding:14px 40px;font-size:1rem;" id="submitBtn">
            🚀 WordPress + Google に投稿する
        </button>
    </div>
</form>

@endsection

@push('scripts')
<script>
    // 店舗別フッターテンプレート（○○はカテゴリ名に自動置換）
    var storeFooterMap = {
        @foreach($stores as $store)
        '{{ $store->id }}': @if(str_contains($store->name, '西千石'))"鹿児島市西千石・天文館・中央駅周辺エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト西千石本店へぜひご相談ください。LINE査定も受付中です。"@elseif(str_contains($store->name, '宇宿'))"鹿児島市宇宿・谷山エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト宇宿店へぜひご相談ください。LINE査定も受付中です。"@elseif(str_contains($store->name, '伊敷'))"鹿児島市伊敷・草牟田・下伊敷・吉野エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト伊敷店へぜひご相談ください。LINE査定も受付中です。"@elseif(str_contains($store->name, '鹿屋'))"鹿屋市寿・札元・川西エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト鹿屋店へぜひご相談ください。LINE査定も受付中です。"@elseif(str_contains($store->name, '国分'))"霧島市国分・隼人エリアで○○の売却や質預かりをご検討の方は、高価買取の質屋アシスト国分店へぜひご相談ください。LINE査定も受付中です。"@else""@endif,
        @endforeach
    };

    // ブロック③を店舗＋カテゴリから自動生成
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

    // 店舗選択時にブロック③を自動セット
    document.getElementById('store_id').addEventListener('change', function() {
        updateBlock3();
    });

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
        document.getElementById('previewBox').textContent = preview || 'ブロック①②③を入力すると、ここにプレビューが表示されます';
    }

    document.getElementById('block1_text').addEventListener('input', updateCounts);
    document.getElementById('block2_text').addEventListener('input', updateCounts);
    document.getElementById('block3_text').addEventListener('input', updateCounts);

    // 選択肢をテキスト入力に追加
    function appendToField(fieldId, value) {
        var field = document.getElementById(fieldId);
        var current = field.value.trim();
        if (current && !current.endsWith('、')) {
            field.value = current + '、' + value;
        } else {
            field.value = current + value;
        }
    }

    // ブロック①自動生成（ブランド名・商品名・状態・カテゴリ入力時に自動更新）
    function autoGenerateBlock1() {
        var brand = document.getElementById('brand_name').value;
        var product = document.getElementById('product_name').value;
        var status = document.getElementById('product_status').value;
        var category = document.getElementById('category').value;

        if (brand && product) {
            var text = brand + ' ' + product + ' ' + status + 'をお買取りいたしました。人気の' + brand + 'の' + (category || '○○') + 'は状態を問わず高価買取中です。';
            document.getElementById('block1_text').value = text;
            updateCounts();
        }
    }

    document.getElementById('brand_name').addEventListener('input', autoGenerateBlock1);
    document.getElementById('product_name').addEventListener('input', autoGenerateBlock1);
    document.getElementById('product_status').addEventListener('change', autoGenerateBlock1);
    document.getElementById('category').addEventListener('change', function() {
        autoGenerateBlock1();
        // ブロック③を店舗＋カテゴリで再生成
        updateBlock3();
    });

    // ブロック② AI生成
    function generateEpisode() {
        var brand = document.getElementById('brand_name').value;
        var product = document.getElementById('product_name').value;

        if (!brand || !product) {
            alert('ブランド名と商品名を先に入力してください');
            return;
        }

        var btn = document.getElementById('generateBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> 生成中...';

        var formData = new FormData();
        formData.append('brand_name', brand);
        formData.append('product_name', product);
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

    // ブロック③ AI生成
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

    // フォーム送信時の二重送信防止
    document.getElementById('postForm').addEventListener('submit', function() {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> 投稿中...';
    });

    // 初期化
    updateCounts();
</script>
@endpush
