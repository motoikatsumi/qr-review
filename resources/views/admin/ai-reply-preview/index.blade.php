@extends('layouts.admin')

@section('title', 'AI返信プレビュー')

@push('styles')
<style>
    .preview-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .preview-grid { grid-template-columns: 1fr; }
    }
    .preview-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        padding: 20px;
    }
    .preview-card h3 {
        font-size: 0.95rem;
        margin: 0 0 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
        color: #1e1b4b;
    }
    .form-row { margin-bottom: 14px; }
    .form-row label {
        display: block;
        font-size: 0.83rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }
    .form-row select,
    .form-row input[type="text"],
    .form-row input[type="number"],
    .form-row textarea {
        width: 100%;
        padding: 9px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.88rem;
        font-family: inherit;
        outline: none;
        transition: border-color 0.2s;
    }
    .form-row select:focus,
    .form-row input:focus,
    .form-row textarea:focus { border-color: #667eea; }

    .star-rating { display: flex; gap: 6px; }
    .star-rating button {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.15s;
    }
    .star-rating button.active {
        background: #fef3c7;
        border-color: #f59e0b;
    }

    .keyword-chips { display: flex; flex-wrap: wrap; gap: 6px; max-height: 200px; overflow-y: auto; padding: 6px; border: 1px solid #e5e7eb; border-radius: 8px; }
    .keyword-chips label {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 5px 10px; background: #f0f4ff; border: 1px solid #e0e5f0;
        border-radius: 16px; font-size: 0.78rem; cursor: pointer;
        margin: 0;
    }
    .keyword-chips label.checked {
        background: #c7d2fe; border-color: #6366f1; color: #1e1b4b;
    }

    .reply-output {
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        border: 2px solid #10b981;
        border-radius: 10px;
        padding: 16px;
        font-size: 0.92rem;
        line-height: 1.7;
        color: #1f2937;
        white-space: pre-wrap;
        min-height: 200px;
    }
    .reply-empty {
        background: #f9fafb;
        border: 2px dashed #d1d5db;
        color: #9ca3af;
        text-align: center;
        padding: 40px 20px;
        border-radius: 10px;
        font-size: 0.9rem;
    }
    .feedback-bar {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #e5e7eb;
    }
    .feedback-btn {
        padding: 10px 18px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .feedback-btn-good { color: #10b981; }
    .feedback-btn-good:hover { background: #d1fae5; border-color: #10b981; }
    .feedback-btn-bad { color: #ef4444; }
    .feedback-btn-bad:hover { background: #fee2e2; border-color: #ef4444; }

    .feedback-stats {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 0.85rem;
        color: #075985;
    }
    .stat-pill {
        display: inline-block;
        padding: 2px 8px;
        background: white;
        border-radius: 12px;
        font-weight: 600;
        margin: 0 4px;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>🔍 AI返信プレビュー＆学習</h1>
    <a href="/admin/google-reviews" class="btn btn-secondary">← Google口コミ一覧へ戻る</a>
</div>

<div class="card" style="margin-bottom:20px;padding:14px 18px;">
    <p style="font-size:0.88rem;color:#555;margin:0;">
        💡 <strong>使い方：</strong>店舗を選び、サンプルの口コミ評価とカテゴリを設定して「プレビュー生成」ボタンを押すと、現在の AI 設定でどのような返信が生成されるか確認できます。
        生成された返信に👍／👎をつけると、今後の AI 返信生成時に学習データとして反映されます。
    </p>
</div>

@if($selectedStore && (($feedbackCount['good'] ?? 0) > 0 || ($feedbackCount['bad'] ?? 0) > 0))
<div class="feedback-stats">
    📊 <strong>{{ $selectedStore->name }}</strong> の累計フィードバック：
    <span class="stat-pill" style="color:#10b981;">👍 良い例 {{ $feedbackCount['good'] ?? 0 }} 件</span>
    <span class="stat-pill" style="color:#ef4444;">👎 悪い例 {{ $feedbackCount['bad'] ?? 0 }} 件</span>
    <span style="font-size:0.78rem;color:#075985;">（最新2件ずつが返信生成プロンプトに反映されます）</span>
</div>
@endif

<div class="preview-grid">
    {{-- 入力 --}}
    <div class="preview-card">
        <h3>📝 サンプル入力</h3>

        <div class="form-row">
            <label for="store_id">対象店舗</label>
            <select id="store_id">
                @foreach($stores as $s)
                <option value="{{ $s->id }}" {{ $selectedStore && $selectedStore->id == $s->id ? 'selected' : '' }}>
                    {{ $s->name }}{{ $s->businessType ? ' / ' . $s->businessType->name : '' }}
                </option>
                @endforeach
            </select>
            <p style="font-size:0.72rem;color:#999;margin-top:3px;">店舗ごとに AI 設定（トーン・キーワード・返信指示など）が異なります</p>
        </div>

        <div class="form-row">
            <label>口コミ評価（星）</label>
            <div class="star-rating" id="ratingButtons">
                @for($i = 1; $i <= 5; $i++)
                <button type="button" data-rating="{{ $i }}" class="{{ $i == 5 ? 'active' : '' }}">{{ $i }}★</button>
                @endfor
            </div>
            <input type="hidden" id="rating" value="5">
        </div>

        <div class="form-row">
            <label for="sample_review_comment">サンプル口コミ本文（任意）</label>
            <textarea id="sample_review_comment" rows="3" placeholder="例: スタッフの対応が丁寧で、商品の説明もわかりやすかったです。また利用したいと思います。"></textarea>
            <p style="font-size:0.72rem;color:#999;margin-top:3px;">空欄の場合は「星評価のみの口コミ」として扱います</p>
        </div>

        <div class="form-row">
            <label for="customer_type">顧客タイプ</label>
            <select id="customer_type">
                <option value="new">新規</option>
                <option value="repeater">リピーター</option>
                <option value="unknown">不明</option>
            </select>
        </div>

        <div class="form-row">
            <label for="reply_category">返信カテゴリ</label>
            <select id="reply_category" onchange="onCategoryChange()">
                <option value="">なし（一般的な返信）</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" data-name="{{ $cat->name }}">{{ $cat->name }}（キーワード {{ $cat->keywords->count() }}件）</option>
                @endforeach
            </select>
        </div>

        <div class="form-row" id="keywordsBlock" style="display:none;">
            <label>含めるキーワード（複数選択可）</label>
            <div class="keyword-chips" id="keywordChips"></div>
        </div>

        <button type="button" class="btn btn-primary" id="generateBtn" onclick="generatePreview()" style="width:100%;padding:12px;font-size:1rem;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
            ✨ プレビュー生成
        </button>
        <p id="generateStatus" style="margin-top:10px;font-size:0.85rem;color:#667eea;text-align:center;"></p>
    </div>

    {{-- 出力 --}}
    <div class="preview-card">
        <h3>📤 AI 生成結果</h3>
        <div id="replyOutput" class="reply-empty">
            ← 左側で条件を設定して<br>「プレビュー生成」ボタンを押してください
        </div>

        <div class="feedback-bar" id="feedbackBar" style="display:none;">
            <button class="feedback-btn feedback-btn-good" onclick="sendFeedback('good')">👍 良い</button>
            <button class="feedback-btn feedback-btn-bad" onclick="sendFeedback('bad')">👎 悪い</button>
            <span id="feedbackStatus" style="font-size:0.85rem;color:#666;margin-left:auto;"></span>
        </div>
        <p style="font-size:0.72rem;color:#999;margin-top:8px;">
            ※ 評価はプロンプト改善に使われます。良い／悪いの両方を貯めると AI が学習します。
        </p>
    </div>
</div>

{{-- カテゴリ→キーワードのデータをJSに渡す --}}
@php
    $replyCategoriesPayload = $categories->map(function ($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'keywords' => $c->keywords->map(fn ($k) => [
                'id' => $k->id,
                'label' => $k->label,
                'keyword' => $k->keyword,
            ])->values(),
        ];
    });
@endphp
<script>
    const replyCategoriesData = {!! $replyCategoriesPayload->toJson(JSON_UNESCAPED_UNICODE) !!};
</script>
@endsection

@push('scripts')
<script>
    let currentReply = null;

    // 星評価ボタン
    document.querySelectorAll('#ratingButtons button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#ratingButtons button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('rating').value = this.dataset.rating;
        });
    });

    function onCategoryChange() {
        const sel = document.getElementById('reply_category');
        const block = document.getElementById('keywordsBlock');
        const chips = document.getElementById('keywordChips');
        const catId = parseInt(sel.value);
        if (!catId) {
            block.style.display = 'none';
            chips.innerHTML = '';
            return;
        }
        const cat = replyCategoriesData.find(c => c.id == catId);
        if (!cat || !cat.keywords.length) {
            block.style.display = 'none';
            return;
        }
        let html = '';
        cat.keywords.forEach(function(k) {
            html += '<label><input type="checkbox" value="' + escapeHtml(k.keyword) + '" onchange="this.parentElement.classList.toggle(\'checked\', this.checked)"> ' + escapeHtml(k.label) + '</label>';
        });
        chips.innerHTML = html;
        block.style.display = '';
    }

    async function generatePreview() {
        const btn = document.getElementById('generateBtn');
        const status = document.getElementById('generateStatus');
        const output = document.getElementById('replyOutput');
        btn.disabled = true;
        btn.textContent = '🤖 AI が考えています…（5〜15 秒）';
        status.textContent = '';
        output.className = 'reply-empty';
        output.textContent = '生成中…';
        document.getElementById('feedbackBar').style.display = 'none';

        const keywords = Array.from(document.querySelectorAll('#keywordChips input:checked')).map(cb => cb.value);
        const catSel = document.getElementById('reply_category');
        const catName = catSel.value ? catSel.options[catSel.selectedIndex].dataset.name : '';

        const payload = {
            store_id: parseInt(document.getElementById('store_id').value),
            rating: parseInt(document.getElementById('rating').value),
            sample_review_comment: document.getElementById('sample_review_comment').value,
            category: catName,
            keywords: keywords,
            customer_type: document.getElementById('customer_type').value,
        };

        try {
            const res = await fetch('/admin/ai-reply-preview/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.error || '生成に失敗しました');

            currentReply = json.reply;
            output.className = 'reply-output';
            output.textContent = json.reply;
            document.getElementById('feedbackBar').style.display = 'flex';
            document.getElementById('feedbackStatus').textContent = '';
            status.innerHTML = '<span style="color:#10b981;">✅ 生成完了</span>';
        } catch (e) {
            output.className = 'reply-empty';
            output.textContent = '❌ ' + e.message;
        } finally {
            btn.disabled = false;
            btn.textContent = '✨ プレビュー生成';
        }
    }

    async function sendFeedback(type) {
        if (!currentReply) return;
        let comment = '';
        if (type === 'bad') {
            comment = prompt('👎 悪いと感じた理由を教えてください（任意。AI が学習に使います）', '') || '';
        }
        const fbStatus = document.getElementById('feedbackStatus');
        fbStatus.textContent = '送信中…';

        const keywords = Array.from(document.querySelectorAll('#keywordChips input:checked')).map(cb => cb.value);
        const catSel = document.getElementById('reply_category');
        const catName = catSel.value ? catSel.options[catSel.selectedIndex].dataset.name : '';

        try {
            const res = await fetch('/admin/ai-reply-preview/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    store_id: parseInt(document.getElementById('store_id').value),
                    feedback_type: type,
                    rating: parseInt(document.getElementById('rating').value),
                    sample_review_comment: document.getElementById('sample_review_comment').value,
                    generated_reply: currentReply,
                    category: catName,
                    keywords: keywords,
                    customer_type: document.getElementById('customer_type').value,
                    comment: comment,
                }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.error || '保存失敗');
            fbStatus.innerHTML = '<span style="color:' + (type === 'good' ? '#10b981' : '#ef4444') + ';">' + json.message + '</span>';
            // 1.5 秒後にページをリロードしてカウンターを更新
            setTimeout(() => location.reload(), 2000);
        } catch (e) {
            fbStatus.innerHTML = '<span style="color:#ef4444;">エラー: ' + e.message + '</span>';
        }
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    // 店舗を変えたらリロードしてフィードバックカウンターを更新
    document.getElementById('store_id').addEventListener('change', function() {
        location.href = '/admin/ai-reply-preview?store_id=' + this.value;
    });
</script>
@endpush
