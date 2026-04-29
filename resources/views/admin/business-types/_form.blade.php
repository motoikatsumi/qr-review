{{-- 業種フォームパーシャル（create・edit共通） --}}
@php
    $isEdit = !is_null($businessType);
    $focusText  = $isEdit ? implode("\n", $businessType->focus_presets ?? []) : '';
    $styleText  = $isEdit ? implode("\n", $businessType->style_presets ?? []) : '';
    $ngText     = $isEdit ? implode("\n", $businessType->ng_words ?? []) : '';
    $reasonText = $isEdit ? implode("\n", $businessType->post_reason_presets ?? []) : '';
    $accessoryText = $isEdit ? implode("\n", $businessType->post_accessory_presets ?? []) : '';
    $statusText = $isEdit ? implode("\n", $businessType->post_status_options ?? []) : '';
    // カテゴリ: 質屋アシスト（slug=pawn）だけ詳細形式（name|wp_slug|wp_path）。
    // それ以外は名前のみ（1行1カテゴリ）でシンプルに。
    $isPawnType = $isEdit && ($businessType->slug ?? '') === 'pawn';
    $categoriesText = '';
    if ($isEdit && $businessType->post_categories) {
        $lines = [];
        foreach ($businessType->post_categories as $cat) {
            if ($isPawnType) {
                $lines[] = ($cat['name'] ?? '') . '|' . ($cat['wp_slug'] ?? '') . '|' . ($cat['wp_path'] ?? '');
            } else {
                $lines[] = ($cat['name'] ?? '');
            }
        }
        $categoriesText = implode("\n", $lines);
    }

    // レビュー質問 5 スロット（order index で固定）
    // 既存データがあれば優先、無ければデフォルト値
    $existingGroups = $isEdit ? ($businessType->review_option_groups ?? []) : [];
    $defaultSlots = [
        ['key' => 'gender',     'label' => '性別',   'options' => ['男性', '女性'],                                      'enabled' => true],
        ['key' => 'visit_type', 'label' => '来店',   'options' => ['新規', 'リピーター'],                                'enabled' => true],
        ['key' => 'age',        'label' => '年代',   'options' => ['20代', '30代', '40代', '50代', '60代~'],             'enabled' => true],
        ['key' => 'item',       'label' => '品目',   'options' => [],                                                   'enabled' => false],
        ['key' => 'custom1',    'label' => '質問5',  'options' => [],                                                   'enabled' => false],
    ];
    $reviewSlots = [];
    for ($i = 0; $i < 5; $i++) {
        $reviewSlots[$i] = $existingGroups[$i] ?? $defaultSlots[$i];
    }
@endphp

@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:16px;">
        <ul style="margin:0;padding-left:20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-group">
    <label for="name">業種名 <span style="color:#ef4444">*</span></label>
    <input type="text" id="name" name="name" value="{{ old('name', $isEdit ? $businessType->name : '') }}" required placeholder="例：焼肉店">
    @error('name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
</div>

{{-- AI 入力サポート（設定に慣れていない方向け） --}}
<div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:1px solid #bae6fd;border-radius:12px;padding:16px 18px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">
        <span style="font-size:1.2rem;">🤖</span>
        <strong style="font-size:0.95rem;color:#075985;">AI 入力サポート</strong>
        <span style="font-size:0.78rem;color:#0369a1;">業種名を入れたら、以下のボタンで各設定を一括で埋められます</span>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <button type="button" id="aiFillAllBtn" class="btn btn-secondary btn-sm" style="background:#0ea5e9;color:white;border:none;font-weight:600;">
            ✨ 全項目を AI で自動入力
        </button>
        <span id="aiFillStatus" style="font-size:0.82rem;color:#0369a1;"></span>
    </div>

    {{-- 進捗バー（生成中だけ表示） --}}
    <div id="aiProgress" style="display:none;margin-top:12px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="font-size:0.78rem;color:#0369a1;">生成中…</span>
            <span style="font-size:0.78rem;color:#0369a1;font-weight:600;"><span id="aiProgressDone">0</span> / <span id="aiProgressTotal">5</span> 完了</span>
        </div>
        <div style="height:6px;background:#bae6fd;border-radius:3px;overflow:hidden;">
            <div id="aiProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#0ea5e9,#0284c7);transition:width 0.3s ease;"></div>
        </div>
        <div id="aiProgressTaskList" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;font-size:0.74rem;"></div>
    </div>

    <p style="font-size:0.78rem;color:#075985;margin:6px 0 0;">※ すでに入力した内容は上書きされます。下書きとして使って、最後に微調整するのがおすすめです。</p>
    <p style="font-size:0.78rem;color:#075985;margin:4px 0 0;">💡 各項目の右にある「🔄 AI で再生成」ボタンで <strong>1 項目だけ</strong>作り直すこともできます。</p>
</div>

@push('styles')
<style>
    /* AI 生成中フィールドのアニメーション */
    .ai-generating {
        outline: 3px solid #06b6d4 !important;
        outline-offset: 2px;
        background: linear-gradient(120deg, #cffafe 0%, #a5f3fc 50%, #cffafe 100%) !important;
        background-size: 200% 100% !important;
        animation: aiShimmer 1.2s ease-in-out infinite, aiPulse 1.5s ease-in-out infinite;
        position: relative;
    }
    @keyframes aiShimmer {
        0%   { background-position: 0% 50%; }
        100% { background-position: -200% 50%; }
    }
    @keyframes aiPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(6,182,212,0.4); }
        50%      { box-shadow: 0 0 0 12px rgba(6,182,212,0); }
    }
    .ai-success {
        outline: 3px solid #22c55e !important;
        outline-offset: 2px;
        background: #dcfce7 !important;
        animation: aiSuccessFlash 2s ease-out;
    }
    @keyframes aiSuccessFlash {
        0%   { background: #86efac; }
        50%  { background: #dcfce7; }
        100% { background: white; }
    }
    .ai-error {
        outline: 3px solid #ef4444 !important;
        outline-offset: 2px;
        background: #fee2e2 !important;
    }
    /* ツールチップ用 */
    .help-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        background: #6366f1;
        color: white;
        border-radius: 50%;
        font-size: 0.7rem;
        font-weight: 700;
        cursor: help;
        margin-left: 4px;
        position: relative;
        vertical-align: middle;
    }
    .help-icon:hover { background: #4f46e5; }
    .help-icon::after {
        content: attr(data-help);
        position: absolute;
        bottom: calc(100% + 8px);
        /* 左端で見切れないよう、アイコンの右側に展開 */
        left: -10px;
        transform: none;
        background: #1e1b4b;
        color: white;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 400;
        line-height: 1.6;
        white-space: normal;
        width: 320px;
        max-width: min(320px, 90vw);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s, visibility 0.15s;
        z-index: 10;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        pointer-events: none;
    }
    .help-icon::before {
        content: '';
        position: absolute;
        bottom: calc(100% + 2px);
        left: 4px;
        transform: none;
        border: 6px solid transparent;
        border-top-color: #1e1b4b;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s, visibility 0.15s;
        z-index: 10;
    }
    .help-icon:hover::after,
    .help-icon:hover::before {
        opacity: 1;
        visibility: visible;
    }

    /* 個別 AI 再生成ボタン */
    .ai-regen-btn {
        background: white;
        color: #0ea5e9;
        border: 1px solid #bae6fd;
        padding: 5px 12px;
        font-size: 0.74rem;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s;
    }
    .ai-regen-btn:hover {
        background: #0ea5e9;
        color: white;
        border-color: #0ea5e9;
    }
    .ai-regen-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('aiFillAllBtn');
    if (!btn) return;
    const status = document.getElementById('aiFillStatus');
    const csrfToken = '{{ csrf_token() }}';

    /** 対象フィールドにアニメーションを付ける + スクロール */
    function focusField(el, state) {
        if (!el) return;
        el.classList.remove('ai-generating', 'ai-success', 'ai-error');
        if (state) el.classList.add('ai-' + state);
        if (state === 'generating' || state === 'success') {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        if (state === 'success') {
            setTimeout(() => el.classList.remove('ai-success'), 2000);
        }
    }

    async function callSuggest(name, target) {
        const res = await fetch('/admin/business-types/ai-suggest', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, target }),
        });
        if (!res.ok) {
            let err = 'エラー (' + res.status + ')';
            try { const j = await res.json(); err = j.error || err; } catch(e) {}
            throw new Error(err);
        }
        const json = await res.json();
        if (!json.success) throw new Error(json.error || '失敗');
        return json.data || {};
    }

    /** target → フォーカス対象 DOM 要素 */
    function fieldFor(target) {
        switch (target) {
            case 'base_context':   return document.getElementById('base_context');
            case 'focus_presets':  return document.getElementById('focus_presets_raw');
            case 'style_presets':  return document.getElementById('style_presets_raw');
            case 'ng_words':       return document.getElementById('ng_words_raw');
            case 'review_options': return document.querySelector('[name="review_groups[3][options_raw]"]');
            case 'post_settings':  return document.getElementById('post_action_word');
        }
    }

    function applyBaseContext(data) {
        if (data.base_context) {
            document.getElementById('base_context').value = data.base_context;
            return '業種説明';
        }
    }
    function applyFocus(data) {
        if (Array.isArray(data.focus_presets) && data.focus_presets.length) {
            document.getElementById('focus_presets_raw').value = data.focus_presets.join('\n');
            return '口コミの切り口 (' + data.focus_presets.length + ')';
        }
    }
    function applyStyle(data) {
        if (Array.isArray(data.style_presets) && data.style_presets.length) {
            document.getElementById('style_presets_raw').value = data.style_presets.join('\n');
            return '書き手スタイル (' + data.style_presets.length + ')';
        }
    }
    function applyNgWords(data) {
        if (Array.isArray(data.ng_words) && data.ng_words.length) {
            document.getElementById('ng_words_raw').value = data.ng_words.join('\n');
            return 'NGワード (' + data.ng_words.length + ')';
        }
    }
    function applyPostSettings(data) {
        if (!data.post_settings) return;
        const ps = data.post_settings;

        // 各フィールドの埋まり状況を記録
        const filled = [];

        // テキスト入力系（空文字含めて反映、ただし空なら skip）
        function setVal(id, v, label) {
            const el = document.getElementById(id);
            if (!el) return;
            if (v && String(v).trim() !== '') {
                el.value = v;
                filled.push(label);
                flashField(el);
            }
        }
        setVal('post_action_word',    ps.post_action_word,    'アクションワード');
        setVal('post_title_template', ps.post_title_template, 'タイトルテンプレート');

        // 配列 → 改行区切り
        function setLines(id, arr, label, allowEmpty) {
            const el = document.getElementById(id);
            if (!el) return;
            if (Array.isArray(arr) && arr.length > 0) {
                el.value = arr.join('\n');
                filled.push(label + '(' + arr.length + ')');
                flashField(el);
            } else if (allowEmpty) {
                // 業種にとって不要な項目（付属品など）はクリアして「該当なし」を明示
                el.value = '';
                filled.push(label + '(該当なし)');
                flashField(el);
            }
        }
        setLines('post_status_options_raw',    ps.post_status_options,    '状態オプション',     false);
        setLines('post_reason_presets_raw',    ps.post_reason_presets,    '利用理由プリセット', false);
        setLines('post_accessory_presets_raw', ps.post_accessory_presets, '付属品プリセット',   true);
        setLines('post_default_hashtags',      ps.post_default_hashtags,  'ハッシュタグ',       false);

        // カテゴリ
        const isPawnSlug = '{{ $isPawnType ?? false ? "1" : "" }}' === '1';
        if (Array.isArray(ps.post_categories) && ps.post_categories.length > 0) {
            const lines = ps.post_categories.map(c => {
                if (typeof c === 'string') return c;
                if (c.name && (c.wp_slug || c.wp_path)) {
                    return isPawnSlug ? `${c.name}|${c.wp_slug || c.name}|${c.wp_path || ''}` : c.name;
                }
                return c.name || '';
            }).filter(Boolean);
            const el = document.getElementById('post_categories_raw');
            if (el && lines.length > 0) {
                el.value = lines.join('\n');
                filled.push('カテゴリ(' + lines.length + ')');
                flashField(el);
            }
        }

        // サマリを画面に通知
        showPostSettingsSummary(filled);
        return '投稿機能設定';
    }

    /** フィールドに緑のフラッシュを一瞬当てて「埋まりました」を可視化 */
    function flashField(el) {
        if (!el) return;
        el.classList.remove('ai-success');
        // 強制 reflow して再アニメーション
        void el.offsetWidth;
        el.classList.add('ai-success');
        setTimeout(() => el.classList.remove('ai-success'), 2400);
    }

    /** どのフィールドが埋まったか一覧を表示 */
    function showPostSettingsSummary(filledList) {
        let summary = document.getElementById('postSettingsSummary');
        if (!summary) {
            const anchor = document.querySelector('#post_action_word');
            if (!anchor) return;
            summary = document.createElement('div');
            summary.id = 'postSettingsSummary';
            summary.style.cssText = 'background:#dcfce7;border-left:4px solid #10b981;padding:10px 14px;border-radius:6px;margin:10px 0;font-size:0.82rem;color:#065f46;line-height:1.6;';
            // 投稿機能の設定ヘッダの直下に挿入
            const header = anchor.closest('.form-group');
            if (header && header.parentNode) header.parentNode.insertBefore(summary, header);
        }
        if (!filledList || filledList.length === 0) {
            summary.innerHTML = '⚠️ AI が返したデータが空でした。もう一度試してください。';
            summary.style.background = '#fee2e2';
            summary.style.borderLeftColor = '#dc2626';
            summary.style.color = '#991b1b';
        } else {
            summary.innerHTML = '✅ AI で生成した <strong>' + filledList.length + '</strong> 項目を反映しました: <span style="color:#047857;">' + filledList.join(' / ') + '</span>';
            summary.style.background = '#dcfce7';
            summary.style.borderLeftColor = '#10b981';
            summary.style.color = '#065f46';
        }
        // 6 秒で薄くフェードアウト
        setTimeout(() => { if (summary) summary.style.opacity = '0.5'; }, 6000);
    }

    function applyReviewOptions(data) {
        if (!data.review_options) return;
        const ro = data.review_options;
        const visits = ro.visit_type;
        const items  = ro.item;
        const visitLabel = ro.visit_type_label || '来店';
        const itemLabel  = ro.item_label || '品目';
        const labelInput2 = document.querySelector('[name="review_groups[1][label]"]');
        const optsInput2  = document.querySelector('[name="review_groups[1][options_raw]"]');
        if (labelInput2) labelInput2.value = visitLabel;
        if (optsInput2 && Array.isArray(visits)) {
            optsInput2.value = visits.join('\n');
            focusField(optsInput2, 'success');
        }
        const labelInput4 = document.querySelector('[name="review_groups[3][label]"]');
        const optsInput4  = document.querySelector('[name="review_groups[3][options_raw]"]');
        const enabledInput4 = document.querySelector('[name="review_groups[3][enabled]"]');
        if (labelInput4) labelInput4.value = itemLabel;
        if (optsInput4 && Array.isArray(items)) optsInput4.value = items.join('\n');
        if (enabledInput4) enabledInput4.checked = true;
        return '質問項目';
    }

    /** 進捗バー操作 */
    const progressEl = document.getElementById('aiProgress');
    const progressBar = document.getElementById('aiProgressBar');
    const progressDoneEl = document.getElementById('aiProgressDone');
    const progressTotalEl = document.getElementById('aiProgressTotal');
    const progressTaskList = document.getElementById('aiProgressTaskList');

    function setupProgress(taskLabels) {
        progressEl.style.display = 'block';
        progressBar.style.width = '0%';
        progressDoneEl.textContent = '0';
        progressTotalEl.textContent = String(taskLabels.length);
        progressTaskList.innerHTML = taskLabels.map((l, i) =>
            `<span data-task-idx="${i}" style="background:white;color:#0369a1;padding:3px 10px;border-radius:14px;border:1px solid #bae6fd;">⏳ ${l}</span>`
        ).join('');
    }
    function updateProgress(done, total, idx, ok, label) {
        const pct = total === 0 ? 100 : (done / total) * 100;
        progressBar.style.width = pct + '%';
        progressDoneEl.textContent = String(done);
        const chip = progressTaskList.querySelector(`[data-task-idx="${idx}"]`);
        if (chip) {
            chip.textContent = (ok ? '✓ ' : '✗ ') + label;
            chip.style.background = ok ? '#dcfce7' : '#fee2e2';
            chip.style.color = ok ? '#166534' : '#991b1b';
            chip.style.borderColor = ok ? '#86efac' : '#fca5a5';
        }
    }
    function hideProgress() {
        setTimeout(() => { progressEl.style.display = 'none'; }, 3500);
    }

    /** 単一項目の AI 再生成（個別ボタン用） */
    async function regenerateField(target, applyFn, label) {
        const name = document.getElementById('name').value.trim();
        if (!name) { alert('先に業種名を入力してください。'); return; }
        const el = fieldFor(target);
        focusField(el, 'generating');
        try {
            const data = await callSuggest(name, target);
            applyFn(data);
            focusField(el, 'success');
        } catch (e) {
            focusField(el, 'error');
            alert('生成に失敗しました: ' + (e.message || 'エラー'));
        }
    }
    // グローバルに公開（onclick 用）
    window._regenerateBaseContext  = () => regenerateField('base_context',   applyBaseContext,  'AI 業種説明');
    window._regenerateFocus        = () => regenerateField('focus_presets',  applyFocus,        '口コミの切り口');
    window._regenerateStyle        = () => regenerateField('style_presets',  applyStyle,        '書き手スタイル');
    window._regenerateNgWords      = () => regenerateField('ng_words',       applyNgWords,      'NG ワード');
    window._regenerateReviewOpts   = () => regenerateField('review_options', applyReviewOptions,'質問項目');
    window._regeneratePostSettings = () => regenerateField('post_settings',  applyPostSettings, '投稿機能設定');

    btn.addEventListener('click', async function() {
        const name = document.getElementById('name').value.trim();
        if (!name) {
            alert('先に業種名を入力してください。');
            return;
        }
        if (!confirm(`業種名「${name}」を基に AI が各項目を自動入力します。既存の内容は上書きされますが よろしいですか？`)) {
            return;
        }
        btn.disabled = true;
        btn.textContent = '⏳ 生成中…';
        status.innerHTML = '';

        const tasks = [
            { target: 'base_context',   apply: applyBaseContext,   label: '業種説明' },
            { target: 'focus_presets',  apply: applyFocus,          label: '口コミの切り口' },
            { target: 'style_presets',  apply: applyStyle,          label: '書き手スタイル' },
            { target: 'ng_words',       apply: applyNgWords,        label: 'NG ワード' },
            { target: 'review_options', apply: applyReviewOptions,  label: '質問項目' },
            { target: 'post_settings',  apply: applyPostSettings,   label: '投稿機能設定' },
        ];

        setupProgress(tasks.map(t => t.label));

        // 全ターゲットのフィールドに「生成中」マーカーを付ける
        tasks.forEach(t => focusField(fieldFor(t.target), 'generating'));

        const done = [];
        const failed = [];
        let doneCount = 0;

        await Promise.all(tasks.map(async (t, idx) => {
            try {
                const data = await callSuggest(name, t.target);
                t.apply(data);
                focusField(fieldFor(t.target), 'success');
                done.push(t.label);
                doneCount++;
                updateProgress(doneCount, tasks.length, idx, true, t.label);
            } catch (e) {
                focusField(fieldFor(t.target), 'error');
                failed.push(t.label + ' (' + (e.message || 'エラー') + ')');
                doneCount++;
                updateProgress(doneCount, tasks.length, idx, false, t.label);
            }
        }));

        btn.disabled = false;
        btn.textContent = '✨ 全項目を AI で自動入力';
        if (failed.length === 0) {
            status.innerHTML = '<span style="color:#059669;font-weight:600;">✓ すべての項目を自動入力しました！内容を確認してください</span>';
        } else {
            status.innerHTML = '<span style="color:#dc2626;">⚠️ ' + failed.length + ' 件失敗しました: ' + failed.join(' / ') + '</span>';
        }
        hideProgress();
    });
});
</script>
@endpush

@if ($isEdit)
    {{-- 編集時は slug を変更不可の hidden で送信 --}}
    <input type="hidden" name="slug" value="{{ $businessType->slug }}">
@endif
{{-- 新規作成時は slug をサーバ側で自動生成するため入力欄なし --}}

<div class="form-group">
    <label for="base_context">
        AI への業種説明 <span style="color:#ef4444">*</span>
        <span class="help-icon" data-help="この業種でのお客様の体験を一言で表すと？AI がこの説明を参考に「お店の雰囲気に合った口コミ文」を生成します。例: 焼肉店なら『焼肉店での食事・接客・雰囲気に関する体験』、美容室なら『美容室でのカット・カラー・接客の体験』">?</span>
    </label>
    <div style="display:flex;gap:6px;align-items:center;">
        <input type="text" id="base_context" name="base_context" value="{{ old('base_context', $isEdit ? $businessType->base_context : '') }}" required placeholder="例：焼肉店での食事・接客・雰囲気に関する体験" style="flex:1;">
        <button type="button" class="ai-regen-btn" onclick="_regenerateBaseContext()" title="この項目だけ AI で再生成">🔄 再生成</button>
    </div>
    <p class="form-hint">📝 「○○店での△△体験」のように1文で。AI が口コミの土台を作るときに参照します。</p>
    @error('base_context') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label for="focus_presets_raw">
        口コミの切り口（1行1項目） <span style="color:#ef4444">*</span>
        <span class="help-icon" data-help="毎回同じような口コミ文にならないように、AI が口コミを書くときの『どういう角度で書くか』のパターンを複数登録します。AI は毎回ランダムに1つ選びます。10〜15個あると文章のバリエーションが豊富になります。">?</span>
    </label>
    <textarea id="focus_presets_raw" name="focus_presets_raw" rows="10" required placeholder="お肉の美味しさや鮮度を中心に&#10;スタッフの気配りや接客を中心に&#10;また来たいという気持ちを中心に">{{ old('focus_presets_raw', $focusText) }}</textarea>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
        <p class="form-hint" style="margin:0;">🎨 AI が毎回ランダムに 1 つ選びます。多いほど文章のバリエーションが豊富に。</p>
        <button type="button" class="ai-regen-btn" onclick="_regenerateFocus()" title="この項目だけ AI で再生成">🔄 再生成</button>
    </div>
    @error('focus_presets_raw') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label for="style_presets_raw">
        書き手スタイルリスト（1行1項目） <span style="color:#ef4444">*</span>
        <span class="help-icon" data-help="AI が口コミを書くときに『誰が書いたか』を演じます。例: 『初めて利用した新規客』だと初々しい文章に、『常連客』だと馴染みのある文章になります。複数登録すると AI が毎回違う書き手を演じます。">?</span>
    </label>
    <textarea id="style_presets_raw" name="style_presets_raw" rows="8" required placeholder="初めてこの店を利用した新規のお客様&#10;何度もリピートしているヘビーユーザー">{{ old('style_presets_raw', $styleText) }}</textarea>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
        <p class="form-hint" style="margin:0;">👤 AI が「どんなお客様を演じるか」を切り替えます。</p>
        <button type="button" class="ai-regen-btn" onclick="_regenerateStyle()" title="この項目だけ AI で再生成">🔄 再生成</button>
    </div>
    @error('style_presets_raw') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
</div>

<div class="form-group">
    <label>
        口コミフォームの質問項目（最大 5 個）
        <span class="help-icon" data-help="お客様が QR コードから口コミを書くページに表示される質問項目です。たとえば『性別』『年代』『来店』『品目』など。AI はここでお客様が選んだ回答を参考にして、その人に合った口コミ文を作ります。最大 5 個まで自由に追加・並び替え・選択肢編集できます。">?</span>
    </label>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px;">
        <p class="form-hint" style="margin:0;flex:1;">
            🙋 顧客の QR レビューページに表示される質問。AI 文章生成のヒントにもなります。<br>
            「表示する」をオフにするとフォームに出ません。「その他」入力を許可すると、選択肢にない場合にお客様が自由入力できるようになります（空欄送信も可）。
        </p>
        <button type="button" class="ai-regen-btn" onclick="_regenerateReviewOpts()" title="来店・品目の選択肢を AI で再生成" style="flex-shrink:0;">🔄 来店/品目を AI 再生成</button>
    </div>

    @for ($i = 0; $i < 5; $i++)
        @php
            $slot = $reviewSlots[$i];
            $slotEnabled = old("review_groups.{$i}.enabled", !empty($slot['enabled'])) ? true : false;
            $slotLabel   = old("review_groups.{$i}.label", $slot['label'] ?? '');
            $slotOpts    = old("review_groups.{$i}.options_raw",
                isset($slot['options']) && is_array($slot['options']) ? implode("\n", $slot['options']) : ''
            );
            $slotAllowOther = old("review_groups.{$i}.allow_other_input", !empty($slot['allow_other_input'])) ? true : false;
            $placeholderLabels = ['性別', '来店', '年代', '品目', 'お好みの項目'];
            $placeholderLabel  = $placeholderLabels[$i] ?? '';
            $placeholderOpts   = [
                0 => "男性\n女性",
                1 => "初めての来店\nリピーター",
                2 => "20代\n30代\n40代\n50代\n60代~",
                3 => "ブランド品\n貴金属\n時計\n…",
                4 => "（自由に追加できる質問欄）",
            ];
        @endphp
        <div style="background:#f8f9fa;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;">
            <div style="display:flex;gap:16px;align-items:center;margin-bottom:10px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:6px;font-weight:600;color:#1e1b4b;font-size:0.9rem;">
                    <input type="checkbox" name="review_groups[{{ $i }}][enabled]" value="1" {{ $slotEnabled ? 'checked' : '' }}>
                    項目{{ $i + 1 }} を表示する
                </label>
                <div style="flex:1;min-width:200px;">
                    <input type="text" name="review_groups[{{ $i }}][label]" value="{{ $slotLabel }}"
                           placeholder="項目名（例：{{ $placeholderLabel }}）"
                           style="width:100%;padding:8px 12px;font-size:0.9rem;border:1px solid #d1d5db;border-radius:6px;">
                </div>
            </div>
            <div>
                <label style="font-size:0.78rem;color:#666;display:block;margin-bottom:4px;">選択肢（1行1項目）</label>
                <textarea name="review_groups[{{ $i }}][options_raw]" rows="4"
                          style="width:100%;padding:8px;font-size:0.85rem;border:1px solid #d1d5db;border-radius:6px;"
                          placeholder="{{ $placeholderOpts[$i] }}">{{ $slotOpts }}</textarea>
            </div>
            <div style="margin-top:10px;">
                <label style="display:flex;align-items:center;gap:6px;font-size:0.82rem;color:#475569;">
                    <input type="checkbox" name="review_groups[{{ $i }}][allow_other_input]" value="1" {{ $slotAllowOther ? 'checked' : '' }}>
                    「その他」入力を許可（選択肢にない場合に自由入力欄を表示・空欄での投稿も可能）
                </label>
            </div>
        </div>
    @endfor
</div>

<div class="form-group">
    <label for="ng_words_raw">
        業種固有NGワード（1行1ワード）
        <span class="help-icon" data-help="この業種で AI に絶対に使わせたくないワードを登録します。例: 焼肉店なら『食中毒・腹痛』、質屋なら『偽物・盗品』など、誤解を招く・差別的・業界的にタブーなワード。AI 生成時にこれらを含む文章は自動的に書き直されます。">?</span>
    </label>
    <textarea id="ng_words_raw" name="ng_words_raw" rows="6" placeholder="競合店名&#10;食中毒&#10;異物">{{ old('ng_words_raw', $ngText) }}</textarea>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
        <p class="form-hint" style="margin:0;">🚫 AI が口コミ文を作るときに避ける単語。業界的にタブー・誤解を招くワードを登録。</p>
        <button type="button" class="ai-regen-btn" onclick="_regenerateNgWords()" title="この項目だけ AI で再生成">🔄 再生成</button>
    </div>
</div>

<hr style="margin:24px 0;border-color:#e5e7eb;">

<div style="display:flex;gap:32px;flex-wrap:wrap;">
    <div class="form-group" style="flex:0 0 auto;">
        <label>
            <input type="checkbox" name="use_pawn_system" value="1" {{ old('use_pawn_system', $isEdit ? $businessType->use_pawn_system : false) ? 'checked' : '' }}>
            質屋在庫システム連携を使用する
        </label>
        <p class="form-hint" style="margin-top:4px;">pawn-system APIからの在庫情報取得を有効にします（質屋業種のみ）。</p>
    </div>

    <div class="form-group" style="flex:0 0 auto;">
        <label>
            <input type="checkbox" name="use_purchase_posts" value="1" {{ old('use_purchase_posts', $isEdit ? $businessType->use_purchase_posts : false) ? 'checked' : '' }}>
            投稿機能を使用する
        </label>
        <p class="form-hint" style="margin-top:4px;">WordPress/Instagram/Facebook への投稿機能を有効にします。</p>
    </div>

    <div class="form-group" style="flex:0 0 auto;">
        <label>
            <input type="checkbox" name="use_product_rank" value="1" {{ old('use_product_rank', $isEdit ? $businessType->use_product_rank : false) ? 'checked' : '' }}>
            商品ランク（S/A/B/C/D）を使用する
        </label>
        <p class="form-hint" style="margin-top:4px;">投稿フォームに商品ランク選択を表示します。</p>
    </div>
</div>

<hr style="margin:24px 0;border-color:#e5e7eb;">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:10px;">
    <h3 style="font-size:1rem;color:#4338ca;margin:0;">📦 投稿機能の設定</h3>
    <button type="button" class="ai-regen-btn" onclick="_regeneratePostSettings()" title="この投稿機能ぜんぶ AI で再生成">🔄 投稿設定を AI 再生成</button>
</div>
<p class="form-hint" style="margin-bottom:16px;">投稿機能（use_purchase_posts）が有効な場合に使われる設定です。「✨ 全項目を AI で自動入力」または右上の再生成ボタンから AI に下書きを作らせることができます。</p>

<div class="form-group">
    <label for="post_action_word">
        アクションワード
        <span class="help-icon" data-help="WordPress や SNS への投稿タイトルで使う動詞です。質屋なら『お買取り』、不動産なら『ご紹介』など。例: 商品名 + アクションワード = 『ロレックス サブマリーナをお買取りいたしました』">?</span>
    </label>
    <input type="text" id="post_action_word" name="post_action_word" value="{{ old('post_action_word', $isEdit ? $businessType->post_action_word : '') }}" placeholder="例：お買取り、ご提供、ご紹介" style="width:250px;">
    <p class="form-hint">📌 投稿タイトルで使う動詞（例：「○○をお買取りいたしました」）。</p>
</div>

<div class="form-group">
    <label for="post_title_template">
        ブロック①テンプレート
        <span class="help-icon" data-help="WordPress 投稿の最初のブロック（タイトル直下）に自動挿入される文章です。プレースホルダーを使うと商品ごとに自動で置き換わります: {brand}=ブランド名、{product}=商品名、{status}=新品/中古などの状態、{category}=カテゴリ">?</span>
    </label>
    <input type="text" id="post_title_template" name="post_title_template" value="{{ old('post_title_template', $isEdit ? $businessType->post_title_template : '') }}" placeholder="例：{brand} {product} {status}をお買取りいたしました。">
    <p class="form-hint">📝 使えるプレースホルダー: <code>{brand}</code> <code>{product}</code> <code>{status}</code> <code>{category}</code></p>
</div>

<div class="form-group">
    <label for="post_status_options_raw">
        商品状態の選択肢（1行1項目）
        <span class="help-icon" data-help="投稿フォームで『この商品の状態』を選ぶプルダウンに出てくる選択肢です。例: 質屋なら『中古品/新品/未使用品』、不動産なら『新築/築浅/中古』など。">?</span>
    </label>
    <textarea id="post_status_options_raw" name="post_status_options_raw" rows="3" placeholder="中古品&#10;新品&#10;未使用品">{{ old('post_status_options_raw', $statusText) }}</textarea>
    <p class="form-hint">📂 投稿フォームの「状態」プルダウンの選択肢。</p>
</div>

<div class="form-group">
    <label for="post_categories_raw">
        カテゴリ一覧
        @if($isPawnType)
        <span class="help-icon" data-help="投稿フォームの「カテゴリ選択」に出る項目と、WordPress のカテゴリ・リンク先 URL の対応表です。各行は『表示名|WPカテゴリスラッグ|リンク先URL』の3つを縦棒（|）で区切ります。例: 『時計|時計|/items/clock』">?</span>
        @else
        <span class="help-icon" data-help="投稿フォームの「カテゴリ選択」に出る項目です。1行に1カテゴリの名前を入力してください。例: 『軽自動車』『SUV』『セダン』など。WordPress に投稿する場合は、ここに入力した名前と同じカテゴリが WordPress 側に作成・割当されます。">?</span>
        @endif
    </label>
    @if($isPawnType)
        <textarea id="post_categories_raw" name="post_categories_raw" rows="6" placeholder="時計|時計|/items/clock&#10;ブランド品|ブランド品|/items/brand">{{ old('post_categories_raw', $categoriesText) }}</textarea>
        <p class="form-hint">📁 1行 = 1カテゴリ。形式: <code>表示名|WPスラッグ|WPパス</code>（質屋アシスト専用の詳細形式）</p>
    @else
        <textarea id="post_categories_raw" name="post_categories_raw" rows="6" placeholder="軽自動車&#10;SUV&#10;セダン">{{ old('post_categories_raw', $categoriesText) }}</textarea>
        <p class="form-hint">📁 1行 = 1カテゴリ名。WordPress 連携時はこの名前のカテゴリが自動で割り当てられます。</p>
    @endif
</div>

<div class="form-group">
    <label for="post_reason_presets_raw">
        ご利用の経緯プリセット（1行1項目）
        <span class="help-icon" data-help="投稿フォームで『お客様がなぜ利用したか』を選ぶタグボタンの候補です。AI が文章生成時に使うこともあります。例: 質屋なら『使わなくなった/引っ越し/資金が必要』など。">?</span>
    </label>
    <textarea id="post_reason_presets_raw" name="post_reason_presets_raw" rows="4" placeholder="使わなくなったため&#10;引っ越しのため&#10;買い替えのため">{{ old('post_reason_presets_raw', $reasonText) }}</textarea>
    <p class="form-hint">💭 投稿時に「お客様の利用理由」をワンクリックで挿入できる候補。</p>
</div>

<div class="form-group">
    <label for="post_accessory_presets_raw">
        付属品プリセット（1行1項目）
        <span class="help-icon" data-help="投稿フォームで『付属品・備考』に挿入できるタグボタンの候補です。例: 質屋なら『箱/保証書/説明書/替えベルト』など、業界特有のものを登録します。">?</span>
    </label>
    <textarea id="post_accessory_presets_raw" name="post_accessory_presets_raw" rows="4" placeholder="箱&#10;保証書&#10;説明書">{{ old('post_accessory_presets_raw', $accessoryText) }}</textarea>
    <p class="form-hint">📦 投稿時に「付属品」をワンクリックで挿入できる候補。</p>
</div>

<div class="form-group">
    <label for="post_default_hashtags">
        デフォルトハッシュタグ
        <span class="help-icon" data-help="この業種の Instagram / Facebook 投稿に必ず付与されるハッシュタグです。# は不要、1行1ワードで入力。店舗ごとに追加のタグを店舗 AI 設定で足せます。">?</span>
    </label>
    <textarea id="post_default_hashtags" name="post_default_hashtags" rows="3" placeholder="買取&#10;高価買取&#10;査定">{{ old('post_default_hashtags', $isEdit ? $businessType->post_default_hashtags : '') }}</textarea>
    <p class="form-hint">#️⃣ 業種共通の SNS ハッシュタグ。# は付けず単語のみ。</p>
</div>

<hr style="margin:24px 0;border-color:#e5e7eb;">

<div class="form-group">
    <label for="sort_order">並び順</label>
    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $isEdit ? $businessType->sort_order : 0) }}" min="0" style="width:100px;">
</div>

<div class="form-group">
    <label>
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $isEdit ? $businessType->is_active : true) ? 'checked' : '' }}>
        有効（店舗作成時に選択可能）
    </label>
</div>
