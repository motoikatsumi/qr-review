@extends('layouts.admin')

@section('title', '店舗を編集：' . $store->name)

@push('styles')
<style>
    /* ===== タブナビゲーション ===== */
    .store-tabs {
        display: flex;
        gap: 0;
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        overflow: hidden;
        border-bottom: 2px solid #e5e7eb;
    }
    .store-tab {
        flex: 1;
        text-align: center;
        padding: 16px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .store-tab:hover {
        color: #374151;
        background: #f9fafb;
    }
    .store-tab.active {
        color: #4338ca;
        border-bottom-color: #667eea;
        background: #faf9ff;
    }
    .store-tab .tab-icon { font-size: 1.1rem; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ===== AI設定セクション ===== */
    .ai-section {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        margin-bottom: 24px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    .ai-section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 24px;
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border-bottom: 1px solid #e5e7eb;
    }
    .ai-section-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; flex-shrink: 0;
    }
    .ai-section-icon.blue { background: #dbeafe; }
    .ai-section-icon.purple { background: #ede9fe; }
    .ai-section-icon.green { background: #d1fae5; }
    .ai-section-icon.orange { background: #ffedd5; }
    .ai-section-icon.pink { background: #fce7f3; }
    .ai-section-icon.teal { background: #ccfbf1; }
    .ai-section-title { font-size: 1rem; font-weight: 700; color: #1e1b4b; }
    .ai-section-desc { font-size: 0.78rem; color: #6b7280; margin-top: 2px; }
    .ai-section-body { padding: 24px; }
    .help-box {
        background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;
        padding: 14px 16px; margin-bottom: 16px; font-size: 0.82rem;
        color: #92400e; line-height: 1.6;
    }
    .help-box strong { color: #78350f; }
    .example-box {
        background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;
        padding: 12px 14px; margin-top: 8px; font-size: 0.78rem;
        color: #64748b; line-height: 1.7;
    }
    .example-box .example-label { font-weight: 700; color: #475569; margin-bottom: 4px; }
    .ai-section .form-group label { font-size: 0.88rem; font-weight: 600; color: #374151; margin-bottom: 8px; }
    .ai-section textarea { min-height: 80px; line-height: 1.6; }
    .radio-cards { display: flex; gap: 12px; flex-wrap: wrap; }
    .radio-card { flex: 1; min-width: 140px; position: relative; }
    .radio-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .radio-card-label {
        display: block; padding: 14px 16px; border: 2px solid #e5e7eb;
        border-radius: 10px; cursor: pointer; text-align: center;
        transition: all 0.2s; background: white;
    }
    .radio-card-label .rc-icon { font-size: 1.3rem; display: block; margin-bottom: 4px; }
    .radio-card-label .rc-title { font-weight: 600; font-size: 0.85rem; color: #374151; }
    .radio-card-label .rc-desc { font-size: 0.72rem; color: #9ca3af; margin-top: 2px; }
    .radio-card input:checked + .radio-card-label {
        border-color: #667eea; background: #f5f3ff; box-shadow: 0 0 0 1px #667eea;
    }
    .radio-card input:checked + .radio-card-label .rc-title { color: #4338ca; }
    .setting-status {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 0.72rem; padding: 2px 8px; border-radius: 10px; margin-left: auto;
    }
    .setting-status.configured { background: #d1fae5; color: #065f46; }
    .setting-status.default { background: #f3f4f6; color: #6b7280; }
    .save-bar {
        background: white; border-radius: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb; padding: 20px 24px;
        display: flex; align-items: center; justify-content: space-between;
    }
    .save-bar .save-hint { font-size: 0.82rem; color: #6b7280; }

    /* ===== 基本情報グリッド ===== */
    .basic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
    .basic-grid .ai-section { margin-bottom: 0; }

    /* ===== AI設定グリッド ===== */
    .ai-choices-row {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;
    }
    .ai-choices-row .ai-section-body { padding: 16px; }
    .radio-cards-vertical { display: flex; flex-direction: column; gap: 8px; }
    .radio-card-compact { position: relative; cursor: pointer; display: block; }
    .radio-card-compact input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .radio-card-compact .radio-card-label {
        display: flex; align-items: center; gap: 8px; padding: 8px 12px;
        border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s; background: white;
    }
    .radio-card-compact .rc-icon { font-size: 1rem; flex-shrink: 0; }
    .radio-card-compact .rc-title { font-weight: 600; font-size: 0.82rem; color: #374151; }
    .radio-card-compact .rc-desc { font-size: 0.72rem; color: #9ca3af; margin-left: auto; }
    .radio-card-compact input:checked + .radio-card-label {
        border-color: #667eea; background: #f5f3ff; box-shadow: 0 0 0 1px #667eea;
    }
    .radio-card-compact input:checked + .radio-card-label .rc-title { color: #4338ca; }
    .ai-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .ai-grid .ai-section { margin-bottom: 0; }

    /* === 詳細設定（折りたたみ）=== */
    .ai-advanced-details {
        background: #f9fafb;
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .ai-advanced-details[open] {
        background: #fafbff;
        border-style: solid;
        border-color: #c7d2fe;
    }
    .ai-advanced-summary {
        cursor: pointer;
        user-select: none;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        padding: 4px 0;
        outline: none;
    }
    .ai-advanced-summary::-webkit-details-marker {
        display: none;
    }
    .ai-advanced-summary::before {
        content: '▶';
        font-size: 0.75rem;
        color: #667eea;
        margin-right: 10px;
        transition: transform 0.2s;
    }
    .ai-advanced-details[open] > .ai-advanced-summary::before {
        transform: rotate(90deg);
    }

    /* AI 生成中フィールドのアニメーション */
    .ai-generating {
        outline: 3px solid #06b6d4 !important;
        outline-offset: 2px;
        background: linear-gradient(120deg, #cffafe 0%, #a5f3fc 50%, #cffafe 100%) !important;
        background-size: 200% 100% !important;
        animation: aiShimmer 1.2s ease-in-out infinite, aiPulse 1.5s ease-in-out infinite;
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
        100% { background: white; }
    }
    .ai-error {
        outline: 3px solid #ef4444 !important;
        outline-offset: 2px;
        background: #fee2e2 !important;
    }
    @media (max-width: 860px) {
        .ai-choices-row { grid-template-columns: 1fr; }
        .basic-grid, .ai-grid { grid-template-columns: 1fr; }
    }

    /* ===== 外部連携 ===== */
    .integration-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 20px; }
    .integration-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
    .integration-header { padding: 16px 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #e5e7eb; }
    .integration-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    .icon-instagram { background: linear-gradient(135deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
    .icon-facebook  { background: #1877f2; }
    .icon-wordpress { background: #21759b; }
    .icon-google    { background: #fff; border: 1px solid #e5e7eb; }
    .integration-title { font-weight: 700; font-size: 1rem; }
    .integration-body { padding: 20px; }
    .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; margin-bottom: 16px; }
    .status-connected { background: #d1fae5; color: #065f46; }
    .status-disconnected { background: #f3f4f6; color: #6b7280; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; }
    .dot-green { background: #10b981; }
    .dot-gray  { background: #9ca3af; }
    .field-row { margin-bottom: 14px; }
    .field-row label { display: block; font-size: 0.82rem; font-weight: 600; color: #555; margin-bottom: 5px; }
    .field-row input { width: 100%; padding: 9px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.88rem; font-family: monospace; }
    .field-row input:focus { border-color: #667eea; outline: none; }
    .action-row { display: flex; gap: 8px; margin-top: 16px; }
    .note { font-size: 0.78rem; color: #888; margin-top: 12px; line-height: 1.6; }
    a.hint-link { color: #667eea; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1>🏪 店舗を編集：{{ $store->name }}</h1>
    <a href="/admin/stores" class="btn btn-secondary">← 一覧に戻る</a>
</div>

{{-- ===== タブ ===== --}}
<div class="store-tabs">
    <a class="store-tab {{ $activeTab === 'basic' ? 'active' : '' }}" data-tab="basic" href="#">
        <span class="tab-icon">🏪</span> 基本情報
    </a>
    <a class="store-tab {{ $activeTab === 'ai' ? 'active' : '' }}" data-tab="ai" href="#">
        <span class="tab-icon">🤖</span> AI設定
    </a>
    <a class="store-tab {{ $activeTab === 'integrations' ? 'active' : '' }}" data-tab="integrations" href="#">
        <span class="tab-icon">🔗</span> 外部連携
    </a>
</div>

{{-- ============================== --}}
{{-- タブ1: 基本情報 --}}
{{-- ============================== --}}
<div id="tab-basic" class="tab-panel {{ $activeTab === 'basic' ? 'active' : '' }}">
    <div style="padding-top:24px;">
        <form method="POST" action="/admin/stores/{{ $store->id }}">
            @csrf
            @method('PUT')

            <div class="basic-grid">
                {{-- 店舗名・業種 --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon blue">🏪</div>
                        <div>
                            <div class="ai-section-title">店舗情報</div>
                            <div class="ai-section-desc">店舗名と業種を設定します</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="name">店舗名 <span style="color:#ef4444">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $store->name) }}" required>
                            @error('name') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label for="business_type_id">業種</label>
                            <select id="business_type_id" name="business_type_id">
                                <option value="">（未選択）</option>
                                @foreach($businessTypes as $bt)
                                <option value="{{ $bt->id }}" {{ old('business_type_id', $store->business_type_id) == $bt->id ? 'selected' : '' }}>
                                    {{ $bt->name }}
                                </option>
                                @endforeach
                            </select>
                            <p class="form-hint">AI文章生成の切り口・スタイル・NGワードが業種ごとに切り替わります。</p>
                            @error('business_type_id') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Google口コミURL --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon green">🔗</div>
                        <div>
                            <div class="ai-section-title">Google口コミURL</div>
                            <div class="ai-section-desc">お客様が口コミを書くURLを設定</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="google_review_url">Google口コミ投稿URL <span style="color:#ef4444">*</span></label>
                            <input type="url" id="google_review_url" name="google_review_url" value="{{ old('google_review_url', $store->google_review_url) }}" required>
                            @error('google_review_url') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- 通知設定 --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon orange">🔔</div>
                        <div>
                            <div class="ai-section-title">通知設定</div>
                            <div class="ai-section-desc">低評価時の通知先とルールを設定</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="notify_email">低評価通知先メール <span style="color:#ef4444">*</span></label>
                            <input type="email" id="notify_email" name="notify_email" value="{{ old('notify_email', $store->notify_email) }}" required>
                            @error('notify_email') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label for="notify_threshold">低評価通知の閾値</label>
                            <select id="notify_threshold" name="notify_threshold">
                                @for($i = 1; $i <= 4; $i++)
                                <option value="{{ $i }}" {{ old('notify_threshold', $store->notify_threshold ?? 3) == $i ? 'selected' : '' }}>
                                    ★{{ $i }}以下で通知
                                </option>
                                @endfor
                            </select>
                            <p class="form-hint">QR口コミ：閾値を超える評価はGoogle誘導、以下はメール通知。</p>
                            @error('notify_threshold') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- URL・有効/無効 --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon purple">⚙️</div>
                        <div>
                            <div class="ai-section-title">システム設定</div>
                            <div class="ai-section-desc">URL識別名と有効/無効を管理</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="slug">URL識別名 <span style="color:#ef4444">*</span></label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $store->slug) }}" required>
                            <p class="form-hint">口コミURLの一部になります。例: https://ドメイン/review/<strong>{{ $store->slug }}</strong> ←この部分です</p>
                            @error('slug') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group" style="margin-top:16px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $store->is_active) ? 'checked' : '' }}>
                                有効（レビュー受付中）
                            </label>
                        </div>
                        <div class="form-group" style="margin-top:16px;background:#f0f4ff;padding:12px 14px;border-radius:8px;">
                            <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="use_wordpress" value="1" {{ old('use_wordpress', $store->use_wordpress ?? true) ? 'checked' : '' }} style="margin-top:3px;">
                                <span>
                                    <strong>WordPress 連携を使う</strong>
                                    <p style="font-size:0.78rem;color:#6b7280;margin:4px 0 0;line-height:1.5;">
                                        ON: 買取投稿が WordPress（HP）と Instagram/Facebook の両方に投稿される<br>
                                        OFF: Instagram/Facebook のみに投稿（WordPress 不要、ブログ機能なしの店舗向け）
                                    </p>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="save-bar" style="margin-top:24px;">
                <span class="save-hint">💡 変更後は保存ボタンを押してください</span>
                <button type="submit" class="btn btn-primary" style="padding:12px 32px;font-size:0.95rem;">
                    ✅ 基本情報を保存する
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================== --}}
{{-- タブ2: AI設定 --}}
{{-- ============================== --}}
<div id="tab-ai" class="tab-panel {{ $activeTab === 'ai' ? 'active' : '' }}">
    <div style="padding-top:24px;">

        {{-- AI 入力サポート（店舗向け） --}}
        <div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:1px solid #bae6fd;border-radius:12px;padding:16px 18px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">
                <span style="font-size:1.2rem;">🤖</span>
                <strong style="font-size:0.95rem;color:#075985;">AI 入力サポート</strong>
                <span style="font-size:0.78rem;color:#0369a1;">店舗名と業種から、AI 設定の各項目を自動で埋められます</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <button type="button" id="aiFillStoreBtn" class="btn btn-secondary btn-sm" style="background:#0ea5e9;color:white;border:none;font-weight:600;">
                    ✨ AI で詳細設定を自動入力
                </button>
                <span id="aiFillStoreStatus" style="font-size:0.82rem;color:#0369a1;"></span>
            </div>
            <p style="font-size:0.78rem;color:#075985;margin:6px 0 0;">
                ※ お店の特徴・追加 NG ワード・エリア/サービスキーワード・返信指示・ハッシュタグなどを一括生成。下書きとして使い、最後に微調整するのがおすすめです。<br>
                ※ 「⚙️ AI 詳細設定」を開いた状態で実行すると、生成内容を即座に確認できます。
            </p>
        </div>

        <form method="POST" action="/admin/stores/{{ $store->id }}/ai-settings">
            @csrf
            @method('PUT')

            {{-- 選択系：文体・返信文字数・提案文字数 を横並び --}}
            <div class="ai-choices-row">
                {{-- 文体の方向性 --}}
                <div class="ai-section" style="margin-bottom:0;">
                    <div class="ai-section-header">
                        <div class="ai-section-icon purple">🎨</div>
                        <div>
                            <div class="ai-section-title">文体の方向性</div>
                            <div class="ai-section-desc">トーンを選択</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="radio-cards-vertical">
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_tone_preference" value="auto"
                                    {{ old('ai_tone_preference', $store->ai_tone_preference) === 'auto' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">🎲</span>
                                    <span class="rc-title">自動</span>
                                </div>
                            </label>
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_tone_preference" value="formal"
                                    {{ old('ai_tone_preference', $store->ai_tone_preference) === 'formal' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">👔</span>
                                    <span class="rc-title">フォーマル</span>
                                </div>
                            </label>
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_tone_preference" value="casual"
                                    {{ old('ai_tone_preference', $store->ai_tone_preference) === 'casual' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">😊</span>
                                    <span class="rc-title">カジュアル</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- 口コミ返信の文字数 --}}
                <div class="ai-section" style="margin-bottom:0;">
                    <div class="ai-section-header">
                        <div class="ai-section-icon purple">📏</div>
                        <div>
                            <div class="ai-section-title">返信の文字数</div>
                            <div class="ai-section-desc">Google口コミ返信のボリューム</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="radio-cards-vertical">
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_reply_length" value="short"
                                    {{ old('ai_reply_length', $store->ai_reply_length ?? 'medium') === 'short' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">📝</span>
                                    <span class="rc-title">コンパクト</span>
                                    <span class="rc-desc">150〜250字</span>
                                </div>
                            </label>
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_reply_length" value="medium"
                                    {{ old('ai_reply_length', $store->ai_reply_length ?? 'medium') === 'medium' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">📄</span>
                                    <span class="rc-title">標準</span>
                                    <span class="rc-desc">300〜500字</span>
                                </div>
                            </label>
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_reply_length" value="long"
                                    {{ old('ai_reply_length', $store->ai_reply_length ?? 'medium') === 'long' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">📋</span>
                                    <span class="rc-title">しっかり</span>
                                    <span class="rc-desc">500〜700字</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- 口コミ提案文の長さ --}}
                <div class="ai-section" style="margin-bottom:0;">
                    <div class="ai-section-header">
                        <div class="ai-section-icon green">✍️</div>
                        <div>
                            <div class="ai-section-title">提案文の長さ</div>
                            <div class="ai-section-desc">QR口コミ提案のボリューム</div>
                        </div>
                    </div>
                    <div class="ai-section-body">
                        <div class="radio-cards-vertical">
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_suggestion_length" value="short"
                                    {{ old('ai_suggestion_length', $store->ai_suggestion_length ?? 'medium') === 'short' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">💬</span>
                                    <span class="rc-title">短め</span>
                                    <span class="rc-desc">15〜50字</span>
                                </div>
                            </label>
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_suggestion_length" value="medium"
                                    {{ old('ai_suggestion_length', $store->ai_suggestion_length ?? 'medium') === 'medium' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">📝</span>
                                    <span class="rc-title">標準</span>
                                    <span class="rc-desc">20〜90字</span>
                                </div>
                            </label>
                            <label class="radio-card-compact">
                                <input type="radio" name="ai_suggestion_length" value="long"
                                    {{ old('ai_suggestion_length', $store->ai_suggestion_length ?? 'medium') === 'long' ? 'checked' : '' }}>
                                <div class="radio-card-label">
                                    <span class="rc-icon">📄</span>
                                    <span class="rc-title">長め</span>
                                    <span class="rc-desc">50〜150字</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 詳細設定（折りたたみ式・デフォルトで閉じる）--}}
            @php
                $hasAdvancedData = $store->ai_custom_instruction || $store->ai_extra_ng_words
                    || $store->ai_area_keywords || $store->ai_service_keywords || $store->ai_reply_instruction;
            @endphp
            <details class="ai-advanced-details">
                <summary class="ai-advanced-summary">
                    <span style="font-size:1rem;font-weight:700;color:#1e1b4b;">⚙️ AI 詳細設定（AI を細かくチューニングしたい方向け）</span>
                    @if ($hasAdvancedData)
                        <span style="background:#dcfce7;color:#166534;font-size:0.75rem;padding:2px 10px;border-radius:999px;margin-left:10px;font-weight:600;">● 設定済み</span>
                    @else
                        <span style="font-size:0.82rem;color:#6b7280;margin-left:8px;">（未設定・必要に応じて開いてください）</span>
                    @endif
                </summary>
                <div style="margin-top:16px;">

            {{-- テキスト入力系を2カラムグリッド --}}
            <div class="ai-grid">
                {{-- 1. お店の特徴 --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon blue">💡</div>
                        <div>
                            <div class="ai-section-title">お店の特徴・基本方針</div>
                            <div class="ai-section-desc">AIにこの店舗のことを教えてあげましょう</div>
                        </div>
                        @if($store->ai_custom_instruction)
                            <span class="setting-status configured">● 設定済み</span>
                        @else
                            <span class="setting-status default">○ 未設定</span>
                        @endif
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="ai_custom_instruction">AIへの追加情報</label>
                            <textarea id="ai_custom_instruction" name="ai_custom_instruction" rows="4"
                                placeholder="空欄の場合、業種の標準設定で動作します">{{ old('ai_custom_instruction', $store->ai_custom_instruction) }}</textarea>
                            @error('ai_custom_instruction') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="example-box">
                            <div class="example-label">✏️ 記入例</div>
                            • 家族連れが多くキッズスペースあり<br>
                            • 駅から徒歩3分の好立地
                        </div>
                    </div>
                </div>

                {{-- 3. NGワード --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon pink">🚫</div>
                        <div>
                            <div class="ai-section-title">追加NGワード</div>
                            <div class="ai-section-desc">この店舗だけで禁止したいワードを設定</div>
                        </div>
                        @if($store->ai_extra_ng_words)
                            <span class="setting-status configured">● 設定済み</span>
                        @else
                            <span class="setting-status default">○ 未設定</span>
                        @endif
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="ai_extra_ng_words">追加NGワード（1行に1ワード）</label>
                            <textarea id="ai_extra_ng_words" name="ai_extra_ng_words" rows="4"
                                placeholder="空欄の場合、業種のNGワードのみが適用されます">{{ old('ai_extra_ng_words', $store->ai_extra_ng_words) }}</textarea>
                            @error('ai_extra_ng_words') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="example-box">
                            <div class="example-label">✏️ 記入例</div>
                            競合店A<br>特定のメニュー名
                        </div>
                    </div>
                </div>

                {{-- 4. 地名キーワード --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon green">📍</div>
                        <div>
                            <div class="ai-section-title">地名キーワード（MEO対策）</div>
                            <div class="ai-section-desc">返信に含める地域名パターン</div>
                        </div>
                        @if($store->ai_area_keywords)
                            <span class="setting-status configured">● 設定済み</span>
                        @else
                            <span class="setting-status default">○ 未設定</span>
                        @endif
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="ai_area_keywords">地名パターン（1行に1パターン）</label>
                            <textarea id="ai_area_keywords" name="ai_area_keywords" rows="4"
                                placeholder="空欄の場合、返信に地名は含まれません">{{ old('ai_area_keywords', $store->ai_area_keywords) }}</textarea>
                            @error('ai_area_keywords') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="example-box">
                            <div class="example-label">✏️ 記入例</div>
                            「○○市」「△△町」という地名を自然に1回ずつ混ぜる
                        </div>
                    </div>
                </div>

                {{-- 5. サービスキーワード --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon orange">🔑</div>
                        <div>
                            <div class="ai-section-title">サービスキーワード（MEO対策）</div>
                            <div class="ai-section-desc">返信に含めるサービス名</div>
                        </div>
                        @if($store->ai_service_keywords)
                            <span class="setting-status configured">● 設定済み</span>
                        @else
                            <span class="setting-status default">○ 未設定</span>
                        @endif
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="ai_service_keywords">サービスキーワード（1行に1ワード）</label>
                            <textarea id="ai_service_keywords" name="ai_service_keywords" rows="4"
                                placeholder="空欄の場合、サービスキーワードは含まれません">{{ old('ai_service_keywords', $store->ai_service_keywords) }}</textarea>
                            @error('ai_service_keywords') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="example-box">
                            <div class="example-label">✏️ 記入例</div>
                            高価買取 / ランチ / カット / 無料査定
                        </div>
                    </div>
                </div>

                {{-- 6. 返信の方針 --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon teal">💬</div>
                        <div>
                            <div class="ai-section-title">口コミ返信の方針</div>
                            <div class="ai-section-desc">返信スタイルをカスタマイズ</div>
                        </div>
                        @if($store->ai_reply_instruction)
                            <span class="setting-status configured">● 設定済み</span>
                        @else
                            <span class="setting-status default">○ 未設定</span>
                        @endif
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="ai_reply_instruction">返信の方針・スタイル</label>
                            <textarea id="ai_reply_instruction" name="ai_reply_instruction" rows="4"
                                placeholder="空欄の場合、標準の返信テンプレートで生成されます">{{ old('ai_reply_instruction', $store->ai_reply_instruction) }}</textarea>
                            @error('ai_reply_instruction') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="example-box">
                            <div class="example-label">✏️ 記入例</div>
                            • 感謝→口コミへの言及→サービス紹介→締め
                        </div>
                    </div>
                </div>

                {{-- 7. 店舗紹介文 --}}
                <div class="ai-section">
                    <div class="ai-section-header">
                        <div class="ai-section-icon blue">🏢</div>
                        <div>
                            <div class="ai-section-title">店舗紹介文（AI生成用）</div>
                            <div class="ai-section-desc">エピソード・フッター生成時の自己紹介</div>
                        </div>
                        @if($store->ai_store_description)
                            <span class="setting-status configured">● 設定済み</span>
                        @else
                            <span class="setting-status default">○ 未設定</span>
                        @endif
                    </div>
                    <div class="ai-section-body">
                        <div class="form-group">
                            <label for="ai_store_description">店舗の紹介文</label>
                            <textarea id="ai_store_description" name="ai_store_description" rows="3"
                                placeholder="空欄の場合、「（店舗名）のスタッフ」として生成されます">{{ old('ai_store_description', $store->ai_store_description) }}</textarea>
                            @error('ai_store_description') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                        </div>
                        <div class="example-box">
                            <div class="example-label">✏️ 記入例</div>
                            地域密着型リサイクルショップ ○○ のスタッフ
                        </div>
                    </div>
                </div>
            </div>

            {{-- フッターテンプレート（全幅） --}}
            <div class="ai-section">
                <div class="ai-section-header">
                    <div class="ai-section-icon green">📝</div>
                    <div>
                        <div class="ai-section-title">投稿フッターテンプレート</div>
                        <div class="ai-section-desc">投稿のブロック③に自動セットされるテンプレート</div>
                    </div>
                    @if($store->postTemplate && $store->postTemplate->template_text)
                        <span class="setting-status configured">● 設定済み</span>
                    @else
                        <span class="setting-status default">○ 未設定</span>
                    @endif
                </div>
                <div class="ai-section-body">
                    <div class="form-group">
                        <label for="post_footer_template">テンプレート本文</label>
                        <textarea id="post_footer_template" name="post_footer_template" rows="4" style="font-size:0.9rem;line-height:1.6;">{{ old('post_footer_template', $store->postTemplate->template_text ?? '') }}</textarea>
                        <p class="form-hint"><code>○○</code> はカテゴリ名に自動置換されます。</p>
                        @error('post_footer_template') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

                </div>{{-- end ai-advanced-details body --}}
            </details>

            {{-- 店舗ハッシュタグ（全幅） --}}
            <div class="ai-section">
                <div class="ai-section-header">
                    <div class="ai-section-icon green">#️⃣</div>
                    <div>
                        <div class="ai-section-title">店舗ハッシュタグ</div>
                        <div class="ai-section-desc">Instagram・Facebook投稿に付与されるハッシュタグ</div>
                    </div>
                    @if($store->custom_hashtags)
                        <span class="setting-status configured">● 設定済み</span>
                    @else
                        <span class="setting-status default">○ 未設定</span>
                    @endif
                </div>
                <div class="ai-section-body">
                    <div class="form-group">
                        <label for="custom_hashtags">ハッシュタグ（1行に1つ、#なしで入力）</label>
                        <textarea id="custom_hashtags" name="custom_hashtags" rows="4" style="font-size:0.9rem;line-height:1.6;" placeholder="店舗名&#10;地域名&#10;サービス名">{{ old('custom_hashtags', $store->custom_hashtags) }}</textarea>
                        <p class="form-hint">業種のデフォルトハッシュタグに加えて、この店舗固有のタグを追加できます。投稿時に編集も可能です。</p>
                        @error('custom_hashtags') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="save-bar">
                <span class="save-hint">💡 保存後、次回のAI生成から反映されます</span>
                <button type="submit" class="btn btn-primary" style="padding:12px 32px;font-size:0.95rem;">
                    ✅ AI設定を保存する
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============================== --}}
{{-- タブ3: 外部連携 --}}
{{-- ============================== --}}
<div id="tab-integrations" class="tab-panel {{ $activeTab === 'integrations' ? 'active' : '' }}">
    <div style="padding-top:24px;">
        {{-- Meta App 審査中の注意バナー --}}
        <div style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-left:5px solid #f59e0b;border-radius:10px;padding:16px 20px;margin-bottom:20px;">
            <div style="display:flex;align-items:flex-start;gap:14px;">
                <div style="font-size:1.6rem;flex-shrink:0;">⏳</div>
                <div style="flex:1;font-size:0.88rem;line-height:1.7;color:#78350f;">
                    <strong style="font-size:0.95rem;">Meta（Facebook / Instagram）連携について</strong><br>
                    現在、当サービスは Meta 社の審査プロセス（Business Verification + App Review）申請中です。<br>
                    審査通過までは、お客様の Facebook アカウントを <strong>「テスター」として個別招待</strong> する必要があります。
                    連携をご希望の場合、以下のメールアドレスに「テスター招待希望」とご連絡ください。
                    <div style="margin-top:8px;">
                        📧 <a href="mailto:info@assist-grp.jp?subject=Meta連携テスター招待希望&body=店舗名:%20%0AFacebookに登録されているメールアドレス:%20" style="color:#92400e;font-weight:600;">info@assist-grp.jp</a>
                    </div>
                    <details style="margin-top:10px;">
                        <summary style="cursor:pointer;font-size:0.82rem;color:#92400e;">ℹ️ なぜ招待が必要？（クリックで開く）</summary>
                        <p style="margin:8px 0 0;font-size:0.82rem;color:#78350f;">
                            Meta は、新しいアプリが Facebook ページや Instagram への投稿機能を一般公開する前に、
                            Business Verification と App Review を通過することを義務付けています。
                            この審査は通常 2〜4 週間かかるため、その間は招待されたテスターのみが連携可能となります。
                            審査通過後はこの手順なしで自由に連携できるようになります。
                        </p>
                    </details>
                </div>
            </div>
        </div>

        {{-- 自動 WordPress セットアップ（FB/IG 連携用ブリッジ） --}}
        <div style="background:white;border:2px solid #c7d2fe;border-radius:12px;padding:20px 24px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="font-size:1.6rem;">🚀</div>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:1rem;color:#1e1b4b;">FB/IG 連携用 WordPress を自動セットアップ</div>
                    <div style="font-size:0.82rem;color:#6b7280;margin-top:2px;">Meta 審査を待たずに Facebook / Instagram 連携を使うための非公開ブリッジ WordPress を、ボタン 1 つで構築します。</div>
                </div>
            </div>

            <div id="autoWpBlock" data-store-id="{{ $store->id }}" data-status="{{ $autoWp?->status ?? 'not_installed' }}">
                @if(!$autoWp)
                    <div style="background:#f9fafb;padding:14px 18px;border-radius:8px;margin-bottom:12px;font-size:0.85rem;color:#555;line-height:1.7;">
                        💡 セットアップを開始すると、以下が自動で行われます：
                        <ul style="margin:6px 0 0 20px;padding:0;">
                            <li>WordPress 本体のインストール（noindex 設定済み・検索エンジンに非表示）</li>
                            <li>Jetpack プラグインの自動展開（Meta 審査済み）</li>
                            <li>専用データベースの作成</li>
                            <li>管理者アカウントの自動生成</li>
                        </ul>
                        <div style="margin-top:8px;color:#888;">所要時間: 約 30 秒〜2 分（初回はダウンロードあり）</div>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="installAutoWp()">🚀 自動セットアップを開始</button>
                @else
                    <div id="autoWpStatusArea">
                        @if($autoWp->status === 'ready')
                            <div style="background:#d1fae5;border-left:4px solid #10b981;padding:14px 18px;border-radius:8px;margin-bottom:14px;">
                                <strong style="color:#047857;">✅ セットアップ完了</strong>
                                <div style="font-size:0.85rem;color:#065f46;margin-top:6px;">
                                    {{ $autoWp->installed_at?->format('Y-m-d H:i') }} に構築済み
                                </div>
                            </div>

                            <div style="background:#f9fafb;padding:14px 18px;border-radius:8px;font-size:0.85rem;line-height:1.8;">
                                <div><strong>サイト URL:</strong> <code style="font-size:0.78rem;">{{ $autoWp->site_url }}</code></div>
                                <div><strong>管理画面:</strong> <a href="{{ $autoWp->admin_url }}" target="_blank" style="color:#667eea;">{{ $autoWp->admin_url }}</a></div>
                                <div><strong>ユーザー名:</strong> <code style="font-size:0.78rem;">{{ $autoWp->admin_username }}</code></div>
                                <div><strong>パスワード:</strong>
                                    <code id="adminPass" style="font-size:0.78rem;background:#fef3c7;padding:2px 8px;">●●●●●●●●●●●●</code>
                                    <button type="button" onclick="togglePass()" style="font-size:0.75rem;border:none;background:none;color:#667eea;cursor:pointer;">表示</button>
                                </div>
                            </div>

                            {{-- Jetpack / FB / IG 接続状況パネル --}}
                            <div id="jetpackStatusPanel" style="background:#f0f4ff;border-left:4px solid #6366f1;padding:14px 18px;border-radius:8px;margin-top:14px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                    <strong style="font-size:0.92rem;color:#1e1b4b;">📡 連携状況</strong>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="refreshJetpackStatus()" id="refreshStatusBtn" style="font-size:0.78rem;">🔄 状態を更新</button>
                                </div>
                                <div id="jetpackStatusContent" style="font-size:0.85rem;color:#555;">
                                    （「状態を更新」ボタンを押してください）
                                </div>
                            </div>

                            <div style="background:#fff7ed;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:8px;margin-top:14px;font-size:0.85rem;color:#78350f;line-height:1.7;">
                                <strong>📋 連携手順</strong>
                                <ol style="margin:6px 0 0 20px;padding:0;">
                                    <li>下の「Jetpack 設定を開く」ボタンをクリック（自動ログイン）</li>
                                    <li>初回のみ：Jetpack を WordPress.com に接続（無料アカウント作成 or 既存ログイン）</li>
                                    <li>Jetpack 内「共有 → Connect Facebook / Instagram」ボタンをクリック</li>
                                    <li>このページに戻って「🔄 状態を更新」ボタンを押すと、上のパネルに反映されます</li>
                                </ol>
                            </div>

                            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="button" class="btn btn-primary btn-sm" onclick="openJetpackAutoLogin()" style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);">
                                    🚀 Jetpack 設定を開く（自動ログイン）
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openWpAdminAutoLogin()">
                                    🔧 WP 管理画面を開く
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="uninstallAutoWp()" style="margin-left:auto;">🗑️ セットアップを削除</button>
                            </div>
                        @elseif($autoWp->status === 'installing')
                            <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:8px;font-size:0.88rem;color:#78350f;">
                                ⏳ セットアップ中…画面を更新してください
                            </div>
                        @else
                            <div style="background:#fee2e2;border-left:4px solid #ef4444;padding:14px 18px;border-radius:8px;font-size:0.85rem;color:#7f1d1d;">
                                ❌ セットアップに失敗しました<br>
                                <code style="font-size:0.78rem;">{{ $autoWp->last_error }}</code>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" style="margin-top:10px;" onclick="installAutoWp()">🔄 リトライ</button>
                        @endif
                    </div>
                @endif

                <div id="autoWpProgress" style="display:none;margin-top:14px;padding:14px 18px;background:#f0f4ff;border-radius:8px;font-size:0.88rem;color:#1e1b4b;">
                    <div id="autoWpProgressMsg">⏳ セットアップ中…（30 秒〜2 分かかります）</div>
                </div>
            </div>
        </div>

        <div class="integration-grid">

            @php
                $fb = $integrations->get('facebook');
                $ig = $integrations->get('instagram');
                $metaConnectUrl = url("/meta/connect/{$store->id}") . '?' . http_build_query(['return_url' => url("/admin/stores/{$store->id}/edit?tab=integrations")]);
            @endphp

            {{-- Facebook（OAuth連携） --}}
            <div class="integration-card">
                <div class="integration-header">
                    <div class="integration-icon icon-facebook" style="color:white;">f</div>
                    <div>
                        <div class="integration-title">Facebook</div>
                        <div style="font-size:0.78rem;color:#888;">Graph API v25.0</div>
                    </div>
                </div>
                <div class="integration-body">
                    @if($fb && $fb->is_active)
                        <div class="status-badge status-connected"><span class="status-dot dot-green"></span>連携済み</div>
                        <p style="font-size:0.85rem;color:#555;margin-bottom:12px;line-height:1.6;">
                            ページ名: <code>{{ $fb->extra_data['page_name'] ?? '—' }}</code><br>
                            ページID: <code>{{ $fb->extra_data['page_id'] ?? '—' }}</code>
                        </p>
                        <div class="action-row">
                            <a href="{{ $metaConnectUrl }}" class="btn btn-secondary btn-sm">再連携</a>
                            <form method="POST" action="{{ url("/admin/stores/{$store->id}/integrations/facebook/disconnect") }}"
                                  onsubmit="return confirm('Facebook連携を解除しますか？')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">連携解除</button>
                            </form>
                        </div>
                    @else
                        <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未連携</div>
                        <a href="{{ $metaConnectUrl }}" style="display:inline-flex;align-items:center;gap:8px;background:#1877f2;color:white;border:none;padding:10px 20px;border-radius:8px;font-size:0.9rem;font-weight:600;text-decoration:none;">
                            📘 Facebookと連携する
                        </a>
                        <p class="note">
                            ※ Facebookアカウントでログインし、連携するページを選択します。<br>
                            ※ Instagramビジネスアカウントが紐付いていれば自動連携されます。
                        </p>
                    @endif
                </div>
            </div>

            {{-- Instagram（Facebook OAuth経由で自動連携） --}}
            <div class="integration-card">
                <div class="integration-header">
                    <div class="integration-icon icon-instagram">📸</div>
                    <div>
                        <div class="integration-title">Instagram</div>
                        <div style="font-size:0.78rem;color:#888;">Graph API v25.0</div>
                    </div>
                </div>
                <div class="integration-body">
                    @if($ig && $ig->is_active)
                        <div class="status-badge status-connected"><span class="status-dot dot-green"></span>連携済み</div>
                        <p style="font-size:0.85rem;color:#555;margin-bottom:12px;line-height:1.6;">
                            @if(!empty($ig->extra_data['ig_username']))
                                アカウント: <code>{{ '@' . $ig->extra_data['ig_username'] }}</code><br>
                            @endif
                            IG User ID: <code>{{ $ig->extra_data['ig_user_id'] ?? '—' }}</code>
                        </p>
                        <div class="action-row">
                            <form method="POST" action="{{ url("/admin/stores/{$store->id}/integrations/instagram/disconnect") }}"
                                  onsubmit="return confirm('Instagram連携を解除しますか？')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">連携解除</button>
                            </form>
                        </div>
                    @else
                        <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未連携</div>
                        @if(!$fb || !$fb->is_active)
                            <a href="{{ $metaConnectUrl }}" style="display:inline-flex;align-items:center;gap:8px;background:#1877f2;color:white;border:none;padding:10px 20px;border-radius:8px;font-size:0.9rem;font-weight:600;text-decoration:none;">
                                📘 Facebookと連携する
                            </a>
                            <p class="note">
                                ※ InstagramはFacebookページ経由で連携されます。<br>
                                ※ Facebookと連携すると、紐付いたInstagramも自動連携されます。
                            </p>
                        @else
                            <p class="note">
                                Facebookは連携済みですが、Instagramビジネスアカウントが紐付いていませんでした。<br>
                                FacebookページにInstagramビジネスアカウントをリンクしてから、
                                <a href="{{ $metaConnectUrl }}" class="hint-link">再連携</a>してください。
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- WordPress --}}
            @php
                $wp = $integrations->get('wordpress');
                $useWp = $store->use_wordpress ?? true;
            @endphp
            <div class="integration-card" style="{{ !$useWp ? 'opacity:0.6;' : '' }}">
                <div class="integration-header">
                    <div class="integration-icon icon-wordpress" style="color:white;font-size:1rem;font-weight:bold;">W</div>
                    <div>
                        <div class="integration-title">WordPress</div>
                        <div style="font-size:0.78rem;color:#888;">REST API（Application Password）</div>
                    </div>
                </div>
                <div class="integration-body">
                    @if(!$useWp)
                        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:10px 14px;border-radius:6px;font-size:0.82rem;color:#78350f;line-height:1.7;">
                            <strong>⚠️ WordPress 連携は OFF になっています</strong><br>
                            「基本情報」タブの「WordPress 連携を使う」を ON にすると、ここで連携設定できます。<br>
                            <span style="color:#92400e;">現在は WordPress を経由せず、Instagram/Facebook へ直接投稿する設定です（HP 不要な店舗向け）。</span>
                        </div>
                    @elseif($wp && $wp->is_active)
                        <div class="status-badge status-connected"><span class="status-dot dot-green"></span>連携済み</div>
                        <p style="font-size:0.85rem;color:#555;margin-bottom:12px;">
                            URL: <code>{{ $wp->extra_data['wp_url'] ?? '—' }}</code><br>
                            ユーザー: <code>{{ $wp->extra_data['wp_username'] ?? '—' }}</code>
                        </p>
                        <form method="POST" action="/admin/stores/{{ $store->id }}/integrations/wordpress/disconnect"
                              onsubmit="return confirm('WordPress連携を解除しますか？\nこの操作は元に戻せません。')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm">連携解除</button>
                        </form>
                    @else
                        <div class="status-badge status-disconnected"><span class="status-dot dot-gray"></span>未連携</div>
                        <form method="POST" action="/admin/stores/{{ $store->id }}/integrations/wordpress">
                            @csrf
                            <div class="field-row">
                                <label>WordPress サイトURL</label>
                                <input type="url" name="wp_url" value="{{ old('wp_url') }}" placeholder="https://example.com" required>
                            </div>
                            <div class="field-row">
                                <label>ユーザー名</label>
                                <input type="text" name="wp_username" value="{{ old('wp_username') }}" placeholder="admin" required>
                            </div>
                            <div class="field-row">
                                <label>アプリケーションパスワード</label>
                                <input type="text" name="wp_password" value="{{ old('wp_password') }}" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" required>
                            </div>
                            <div class="action-row">
                                <button class="btn btn-primary btn-sm">接続テスト＆保存</button>
                            </div>
                            <p class="note">
                                ※ WordPress管理画面 → ユーザー → プロフィール →「アプリケーションパスワード」で発行してください。<br>
                                スペース区切りの形式のままコピーしてOKです。
                            </p>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// タブ切り替え
document.querySelectorAll('.store-tab').forEach(function(tab) {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        var targetTab = this.dataset.tab;

        // タブのactive切り替え
        document.querySelectorAll('.store-tab').forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');

        // パネルの表示切り替え
        document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
        document.getElementById('tab-' + targetTab).classList.add('active');

        // URLを更新（リロード時にタブを維持）
        history.replaceState(null, null, '?tab=' + targetTab);
    });
});

// バリデーションエラー時：エラーのあるタブにインジケーター表示 & 自動切り替え
(function() {
    var tabs = ['basic', 'ai', 'integrations'];
    var firstErrorTab = null;
    tabs.forEach(function(tabName) {
        var panel = document.getElementById('tab-' + tabName);
        if (panel) {
            var errors = panel.querySelectorAll('p[style*="color:#ef4444"]');
            if (errors.length > 0) {
                // タブに赤い●を追加
                var tabEl = document.querySelector('.store-tab[data-tab="' + tabName + '"]');
                if (tabEl) {
                    tabEl.style.color = '#dc2626';
                    tabEl.insertAdjacentHTML('beforeend', ' <span style="color:#ef4444;font-size:0.7rem;">●</span>');
                }
                if (!firstErrorTab) firstErrorTab = tabName;
            }
        }
    });
    // エラーのあるタブを自動で開く
    if (firstErrorTab) {
        document.querySelectorAll('.store-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('active'); });
        var tabEl = document.querySelector('.store-tab[data-tab="' + firstErrorTab + '"]');
        var panelEl = document.getElementById('tab-' + firstErrorTab);
        if (tabEl) tabEl.classList.add('active');
        if (panelEl) panelEl.classList.add('active');
    }
})();

// AI 入力サポート（店舗 AI 設定）
(function() {
    const btn = document.getElementById('aiFillStoreBtn');
    if (!btn) return;
    const status = document.getElementById('aiFillStoreStatus');
    const csrfToken = '{{ csrf_token() }}';
    const url = '/admin/stores/{{ $store->id }}/ai-settings/ai-suggest';

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
    function fieldFor(target) {
        switch (target) {
            case 'store_description':  return document.getElementById('ai_store_description');
            case 'custom_instruction': return document.getElementById('ai_custom_instruction');
            case 'area_keywords':      return document.getElementById('ai_area_keywords');
            case 'service_keywords':   return document.getElementById('ai_service_keywords');
            case 'ng_words':           return document.getElementById('ai_extra_ng_words');
            case 'reply_instruction':  return document.getElementById('ai_reply_instruction');
            case 'custom_hashtags':    return document.getElementById('custom_hashtags');
        }
    }

    async function callTarget(target) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ target }),
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

    btn.addEventListener('click', async function() {
        if (!confirm('AI が店舗向け詳細設定を自動入力します。既存の内容は上書きされますがよろしいですか？')) return;

        // 詳細設定を自動展開
        const details = document.querySelector('.ai-advanced-details');
        if (details) details.open = true;

        btn.disabled = true;
        btn.textContent = '⏳ 生成中…';
        status.innerHTML = '<span style="color:#0369a1;">並列生成中…</span>';

        const tasks = [
            { target: 'store_description', apply: d => { if(d.ai_store_description) document.getElementById('ai_store_description').value = d.ai_store_description; return '店舗説明'; } },
            { target: 'custom_instruction', apply: d => { if(d.ai_custom_instruction) document.getElementById('ai_custom_instruction').value = d.ai_custom_instruction; return 'お店の特徴'; } },
            { target: 'area_keywords',     apply: d => { if(d.ai_area_keywords) document.getElementById('ai_area_keywords').value = d.ai_area_keywords; return 'エリアキーワード'; } },
            { target: 'service_keywords',  apply: d => { if(d.ai_service_keywords) document.getElementById('ai_service_keywords').value = d.ai_service_keywords; return 'サービスキーワード'; } },
            { target: 'ng_words',          apply: d => { if(d.ai_extra_ng_words) document.getElementById('ai_extra_ng_words').value = d.ai_extra_ng_words; return '追加NGワード'; } },
            { target: 'reply_instruction', apply: d => { if(d.ai_reply_instruction) document.getElementById('ai_reply_instruction').value = d.ai_reply_instruction; return '返信指示'; } },
            { target: 'custom_hashtags',   apply: d => { if(d.custom_hashtags) document.getElementById('custom_hashtags').value = d.custom_hashtags; return 'ハッシュタグ'; } },
        ];

        // 開始時に全フィールドに「生成中」マーカー
        tasks.forEach(t => focusField(fieldFor(t.target), 'generating'));

        const done = [];
        const failed = [];
        await Promise.all(tasks.map(async t => {
            try {
                const data = await callTarget(t.target);
                const label = t.apply(data);
                focusField(fieldFor(t.target), 'success');
                if (label) {
                    done.push(label);
                    status.innerHTML = '<span style="color:#059669;">✓ ' + done.join(' / ') + '</span>' +
                                       (failed.length ? '<br><span style="color:#dc2626;">✗ ' + failed.join(' / ') + '</span>' : '');
                }
            } catch (e) {
                focusField(fieldFor(t.target), 'error');
                failed.push(t.target);
            }
        }));

        btn.disabled = false;
        btn.textContent = '✨ AI で詳細設定を自動入力';
        if (failed.length === 0) {
            status.innerHTML = '<span style="color:#059669;font-weight:600;">✓ 自動入力しました！内容を確認して保存してください</span>';
        }
    });
})();

// ============================================
// 自動 WordPress セットアップ
// ============================================
async function installAutoWp() {
    if (!confirm('FB/IG 連携用の WordPress を自動セットアップします。\n所要時間 30 秒〜2 分。続行しますか？')) return;
    const block = document.getElementById('autoWpBlock');
    const storeId = block.dataset.storeId;
    const progress = document.getElementById('autoWpProgress');
    const progressMsg = document.getElementById('autoWpProgressMsg');
    progress.style.display = 'block';
    progressMsg.textContent = '⏳ セットアップ中…（30 秒〜2 分。WP コアダウンロード→DB 作成→インストーラ実行→Jetpack 展開）';

    try {
        const res = await fetch(`/admin/stores/${storeId}/auto-wp/install`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || 'セットアップ失敗');
        progressMsg.innerHTML = '✅ セットアップ完了！画面を更新します…';
        setTimeout(() => location.reload(), 1500);
    } catch (e) {
        progressMsg.innerHTML = '<span style="color:#ef4444;">❌ ' + e.message + '</span>';
    }
}

async function uninstallAutoWp() {
    if (!confirm('WordPress セットアップを削除します。\n（FB/IG 連携も解除されます）\n本当に削除しますか？')) return;
    const block = document.getElementById('autoWpBlock');
    const storeId = block.dataset.storeId;
    try {
        const res = await fetch(`/admin/stores/${storeId}/auto-wp`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error);
        alert('削除しました');
        location.reload();
    } catch (e) {
        alert('削除失敗: ' + e.message);
    }
}

function togglePass() {
    const el = document.getElementById('adminPass');
    const btn = el.nextElementSibling;
    if (el.dataset.shown === '1') {
        el.textContent = '●●●●●●●●●●●●';
        el.dataset.shown = '0';
        btn.textContent = '表示';
    } else {
        const block = document.getElementById('autoWpBlock');
        fetch(`/admin/stores/${block.dataset.storeId}/auto-wp/status`, {
            headers: { 'Accept': 'application/json' }
        }).then(r => r.json()).then(j => {
            el.textContent = j.admin_password || '?';
            el.dataset.shown = '1';
            btn.textContent = '隠す';
        });
    }
}

// ============================================
// Jetpack 自動ログイン + 状態取得
// ============================================
function openJetpackAutoLogin() {
    const block = document.getElementById('autoWpBlock');
    const url = `/admin/stores/${block.dataset.storeId}/auto-wp/login-redirect?dest=jetpack`;
    window.open(url, '_blank');
}

function openWpAdminAutoLogin() {
    const block = document.getElementById('autoWpBlock');
    const url = `/admin/stores/${block.dataset.storeId}/auto-wp/login-redirect?dest=admin`;
    window.open(url, '_blank');
}

async function refreshJetpackStatus() {
    const btn = document.getElementById('refreshStatusBtn');
    const content = document.getElementById('jetpackStatusContent');
    const block = document.getElementById('autoWpBlock');
    btn.disabled = true;
    btn.textContent = '⏳ 取得中…';
    content.innerHTML = '<span style="color:#6b7280;">⏳ WordPress に問い合わせ中…</span>';

    try {
        const res = await fetch(`/admin/stores/${block.dataset.storeId}/auto-wp/jetpack-status`, {
            headers: { 'Accept': 'application/json' }
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.error || '取得失敗');

        let html = '';
        if (!json.jetpack_active) {
            html += '<div style="color:#dc2626;">⚠️ Jetpack が有効化されていません。WP 管理画面で有効化してください。</div>';
        } else if (!json.jetpack_connected) {
            html += '<div style="color:#d97706;">⚠️ Jetpack が WordPress.com に未接続です。「Jetpack 設定を開く」ボタンから接続してください。</div>';
        } else {
            html += '<div style="margin-bottom:8px;"><span style="color:#10b981;">✅ Jetpack：接続済み</span></div>';
        }

        // FB / IG の接続状況
        const services = ['facebook', 'instagram'];
        const labelMap = { facebook: '📘 Facebook', instagram: '📷 Instagram' };
        services.forEach(function(s) {
            const conn = (json.connections || []).find(c => c.service === s);
            if (conn) {
                html += `<div style="margin-top:4px;color:#10b981;">✅ ${labelMap[s]}：${escapeHtml(conn.display_name || '接続済み')}</div>`;
            } else {
                html += `<div style="margin-top:4px;color:#9ca3af;">○ ${labelMap[s]}：未接続</div>`;
            }
        });

        if (json.error) {
            html += `<div style="margin-top:8px;color:#dc2626;font-size:0.78rem;">${escapeHtml(json.error)}</div>`;
        }
        content.innerHTML = html;
    } catch (e) {
        content.innerHTML = `<span style="color:#ef4444;">❌ ${e.message}</span>`;
    } finally {
        btn.disabled = false;
        btn.textContent = '🔄 状態を更新';
    }
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}

// ページ読み込み時に WP がセットアップ済みなら自動で状態取得
document.addEventListener('DOMContentLoaded', function() {
    const block = document.getElementById('autoWpBlock');
    if (block && block.dataset.status === 'ready' && document.getElementById('jetpackStatusPanel')) {
        refreshJetpackStatus();
    }
});
</script>
@endpush
