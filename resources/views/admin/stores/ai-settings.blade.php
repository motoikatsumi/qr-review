@extends('layouts.admin')

@section('title', 'AI設定：' . $store->name)

@push('styles')
<style>
    .ai-settings-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
    }
    .ai-settings-header h1 {
        font-size: 1.4rem;
        color: #1e1b4b;
    }
    .ai-settings-header .header-actions {
        display: flex;
        gap: 8px;
    }
    .store-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ede9fe;
        color: #5b21b6;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 12px;
    }

    /* セクションカード */
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
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .ai-section-icon.blue { background: #dbeafe; }
    .ai-section-icon.purple { background: #ede9fe; }
    .ai-section-icon.green { background: #d1fae5; }
    .ai-section-icon.orange { background: #ffedd5; }
    .ai-section-icon.pink { background: #fce7f3; }
    .ai-section-icon.teal { background: #ccfbf1; }
    .ai-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e1b4b;
    }
    .ai-section-desc {
        font-size: 0.78rem;
        color: #6b7280;
        margin-top: 2px;
    }
    .ai-section-body {
        padding: 24px;
    }

    /* ヘルプテキスト */
    .help-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 16px;
        font-size: 0.82rem;
        color: #92400e;
        line-height: 1.6;
    }
    .help-box strong {
        color: #78350f;
    }

    /* プレースホルダーの例 */
    .example-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        padding: 12px 14px;
        margin-top: 8px;
        font-size: 0.78rem;
        color: #64748b;
        line-height: 1.7;
    }
    .example-box .example-label {
        font-weight: 700;
        color: #475569;
        margin-bottom: 4px;
    }

    /* フォーム要素の上書き */
    .ai-section .form-group label {
        font-size: 0.88rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .ai-section textarea {
        min-height: 80px;
        line-height: 1.6;
    }

    /* ラジオカード */
    .radio-cards {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .radio-card {
        flex: 1;
        min-width: 140px;
        position: relative;
    }
    .radio-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .radio-card-label {
        display: block;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
        background: white;
    }
    .radio-card-label .rc-icon {
        font-size: 1.3rem;
        display: block;
        margin-bottom: 4px;
    }
    .radio-card-label .rc-title {
        font-weight: 600;
        font-size: 0.85rem;
        color: #374151;
    }
    .radio-card-label .rc-desc {
        font-size: 0.72rem;
        color: #9ca3af;
        margin-top: 2px;
    }
    .radio-card input:checked + .radio-card-label {
        border-color: #667eea;
        background: #f5f3ff;
        box-shadow: 0 0 0 1px #667eea;
    }
    .radio-card input:checked + .radio-card-label .rc-title {
        color: #4338ca;
    }

    /* ステータスインジケータ */
    .setting-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.72rem;
        padding: 2px 8px;
        border-radius: 10px;
        margin-left: auto;
    }
    .setting-status.configured {
        background: #d1fae5;
        color: #065f46;
    }
    .setting-status.default {
        background: #f3f4f6;
        color: #6b7280;
    }

    /* 保存ボタン */
    .save-bar {
        background: white;
        border-radius: 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .save-bar .save-hint {
        font-size: 0.82rem;
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<div class="ai-settings-header">
    <div>
        <h1>
            🤖 AI設定
            <span class="store-badge">🏪 {{ $store->name }}</span>
        </h1>
        <p style="font-size:0.82rem;color:#6b7280;margin-top:6px;">
            この店舗のAI口コミ生成・返信の動作をカスタマイズできます
        </p>
    </div>
    <div class="header-actions">
        <a href="/admin/stores/{{ $store->id }}/edit" class="btn btn-secondary">← 店舗編集に戻る</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="/admin/stores/{{ $store->id }}/ai-settings">
    @csrf
    @method('PUT')

    {{-- ============================== --}}
    {{--  1. 基本方針  --}}
    {{-- ============================== --}}
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
            <div class="help-box">
                💬 <strong>何を書けばいい？</strong><br>
                お店の雰囲気、強み、お客様の特徴など、AIに知ってもらいたい情報を自由に書いてください。<br>
                これにより、AI生成される口コミや返信がよりお店らしくなります。
            </div>
            <div class="form-group">
                <label for="ai_custom_instruction">AIへの追加情報</label>
                <textarea id="ai_custom_instruction" name="ai_custom_instruction" rows="4"
                    placeholder="空欄の場合、業種の標準設定で動作します">{{ old('ai_custom_instruction', $store->ai_custom_instruction) }}</textarea>
                @error('ai_custom_instruction') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div class="example-box">
                <div class="example-label">✏️ 記入例</div>
                • うちは家族連れのお客様が多いです。キッズスペースがあります。<br>
                • 駅から徒歩3分の好立地です。<br>
                • スタッフは全員資格保持者で丁寧な接客を心がけています。<br>
                • 予約なしでも気軽に来店できるのが特徴です。
            </div>
        </div>
    </div>

    {{-- ============================== --}}
    {{--  2. 文体の方向性  --}}
    {{-- ============================== --}}
    <div class="ai-section">
        <div class="ai-section-header">
            <div class="ai-section-icon purple">🎨</div>
            <div>
                <div class="ai-section-title">文体の方向性</div>
                <div class="ai-section-desc">AIが生成する口コミ文のトーンを選びます</div>
            </div>
        </div>
        <div class="ai-section-body">
            <div class="radio-cards">
                <label class="radio-card">
                    <input type="radio" name="ai_tone_preference" value="auto"
                        {{ old('ai_tone_preference', $store->ai_tone_preference) === 'auto' ? 'checked' : '' }}>
                    <div class="radio-card-label">
                        <span class="rc-icon">🎲</span>
                        <span class="rc-title">自動（おすすめ）</span>
                        <span class="rc-desc">毎回ランダムで自然に</span>
                    </div>
                </label>
                <label class="radio-card">
                    <input type="radio" name="ai_tone_preference" value="formal"
                        {{ old('ai_tone_preference', $store->ai_tone_preference) === 'formal' ? 'checked' : '' }}>
                    <div class="radio-card-label">
                        <span class="rc-icon">👔</span>
                        <span class="rc-title">フォーマル</span>
                        <span class="rc-desc">落ち着いた丁寧な文体</span>
                    </div>
                </label>
                <label class="radio-card">
                    <input type="radio" name="ai_tone_preference" value="casual"
                        {{ old('ai_tone_preference', $store->ai_tone_preference) === 'casual' ? 'checked' : '' }}>
                    <div class="radio-card-label">
                        <span class="rc-icon">😊</span>
                        <span class="rc-title">カジュアル</span>
                        <span class="rc-desc">親しみやすい明るい文体</span>
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- ============================== --}}
    {{--  3. NGワード  --}}
    {{-- ============================== --}}
    <div class="ai-section">
        <div class="ai-section-header">
            <div class="ai-section-icon pink">🚫</div>
            <div>
                <div class="ai-section-title">追加NGワード</div>
                <div class="ai-section-desc">AIが生成する文章に含めてほしくない言葉を指定</div>
            </div>
            @if($store->ai_extra_ng_words)
                <span class="setting-status configured">● 設定済み</span>
            @else
                <span class="setting-status default">○ 未設定</span>
            @endif
        </div>
        <div class="ai-section-body">
            <div class="help-box">
                🚫 業種ごとに基本的なNGワード（{{ $store->businessType ? $store->businessType->name . 'の標準NGワード' : 'デフォルトのNGワード' }}）は自動で設定されています。<br>
                ここでは<strong>この店舗だけ</strong>で追加で禁止したいワードを設定できます。
            </div>
            <div class="form-group">
                <label for="ai_extra_ng_words">追加NGワード（1行に1ワード）</label>
                <textarea id="ai_extra_ng_words" name="ai_extra_ng_words" rows="4"
                    placeholder="空欄の場合、業種のNGワードのみが適用されます">{{ old('ai_extra_ng_words', $store->ai_extra_ng_words) }}</textarea>
                @error('ai_extra_ng_words') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div class="example-box">
                <div class="example-label">✏️ 記入例</div>
                競合店A<br>
                特定のメニュー名<br>
                ○○キャンペーン
            </div>
        </div>
    </div>

    {{-- ============================== --}}
    {{--  4. MEO地名キーワード  --}}
    {{-- ============================== --}}
    <div class="ai-section">
        <div class="ai-section-header">
            <div class="ai-section-icon green">📍</div>
            <div>
                <div class="ai-section-title">地名キーワード（MEO対策）</div>
                <div class="ai-section-desc">Google口コミ返信に含める地域名のパターンを設定</div>
            </div>
            @if($store->ai_area_keywords)
                <span class="setting-status configured">● 設定済み</span>
            @else
                <span class="setting-status default">○ 未設定</span>
            @endif
        </div>
        <div class="ai-section-body">
            <div class="help-box">
                📍 <strong>MEO対策とは？</strong><br>
                Googleマップ上で店舗を上位表示させるための施策です。<br>
                口コミ返信に地名を含めると、地域検索で見つかりやすくなります。<br>
                複数パターンを登録しておくと、毎回ランダムに1つが選ばれ自然な返信になります。
            </div>
            <div class="form-group">
                <label for="ai_area_keywords">地名パターン（1行に1パターン）</label>
                <textarea id="ai_area_keywords" name="ai_area_keywords" rows="5"
                    placeholder="空欄の場合、返信に地名は含まれません">{{ old('ai_area_keywords', $store->ai_area_keywords) }}</textarea>
                @error('ai_area_keywords') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div class="example-box">
                <div class="example-label">✏️ 記入例（お店の場所に合わせて書き換えてください）</div>
                「○○市」「△△町」という地名を自然に1回ずつ混ぜる<br>
                「○○市」「□□エリア」という地名を自然に1回ずつ混ぜる<br>
                「○○駅前」「△△通り」という地名を自然に1回ずつ混ぜる
            </div>
        </div>
    </div>

    {{-- ============================== --}}
    {{--  5. サービスキーワード  --}}
    {{-- ============================== --}}
    <div class="ai-section">
        <div class="ai-section-header">
            <div class="ai-section-icon orange">🔑</div>
            <div>
                <div class="ai-section-title">サービスキーワード（MEO対策）</div>
                <div class="ai-section-desc">口コミ返信に含めるお店のサービスに関するキーワード</div>
            </div>
            @if($store->ai_service_keywords)
                <span class="setting-status configured">● 設定済み</span>
            @else
                <span class="setting-status default">○ 未設定</span>
            @endif
        </div>
        <div class="ai-section-body">
            <div class="help-box">
                🔑 返信生成時にランダムで1つが選ばれ、文中に自然に織り込まれます。<br>
                お店の主なサービスや強みに関連するワードを登録してください。
            </div>
            <div class="form-group">
                <label for="ai_service_keywords">サービスキーワード（1行に1ワード）</label>
                <textarea id="ai_service_keywords" name="ai_service_keywords" rows="4"
                    placeholder="空欄の場合、業種のデフォルトキーワードが使われます">{{ old('ai_service_keywords', $store->ai_service_keywords) }}</textarea>
                @error('ai_service_keywords') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div class="example-box">
                <div class="example-label">✏️ 業種別の記入例</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:6px;">
                    <div>
                        <strong>質屋・買取店の場合</strong><br>
                        高価買取<br>
                        買取・査定<br>
                        無料査定<br>
                        質預かり・買取
                    </div>
                    <div>
                        <strong>飲食店の場合</strong><br>
                        ランチ<br>
                        テイクアウト<br>
                        個室完備<br>
                        宴会・貸切
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================== --}}
    {{--  6. 返信の方針  --}}
    {{-- ============================== --}}
    <div class="ai-section">
        <div class="ai-section-header">
            <div class="ai-section-icon teal">💬</div>
            <div>
                <div class="ai-section-title">口コミ返信の方針</div>
                <div class="ai-section-desc">Google口コミへの返信スタイルをカスタマイズ</div>
            </div>
            @if($store->ai_reply_instruction)
                <span class="setting-status configured">● 設定済み</span>
            @else
                <span class="setting-status default">○ 未設定</span>
            @endif
        </div>
        <div class="ai-section-body">
            <div class="help-box">
                💬 <strong>返信の全体的な方針を指示できます。</strong><br>
                「どんな内容を含めてほしいか」「どんな雰囲気にしてほしいか」を自由に書いてください。<br>
                空欄の場合はシステム標準の返信構成（感謝→口コミへの言及→サービス紹介→締め）で生成されます。
            </div>
            <div class="form-group">
                <label for="ai_reply_instruction">返信の方針・スタイル</label>
                <textarea id="ai_reply_instruction" name="ai_reply_instruction" rows="5"
                    placeholder="空欄の場合、標準の返信テンプレートで生成されます">{{ old('ai_reply_instruction', $store->ai_reply_instruction) }}</textarea>
                @error('ai_reply_instruction') <p style="color:#ef4444;font-size:0.8rem;margin-top:4px;">{{ $message }}</p> @enderror
            </div>
            <div class="example-box">
                <div class="example-label">✏️ 記入例</div>
                • 最初に感謝を伝え、口コミの内容に具体的に触れてください<br>
                • 当店の取扱サービスや商品を自然にPRしてください<br>
                • 最後に店舗名を含めて、またのご来店をお待ちしている旨で締めてください<br>
                • 地域密着の姿勢をさりげなくアピールしてください
            </div>
        </div>
    </div>

    {{-- ============================== --}}
    {{--  保存バー  --}}
    {{-- ============================== --}}
    <div class="save-bar">
        <span class="save-hint">💡 保存後、次回のAI生成から反映されます</span>
        <button type="submit" class="btn btn-primary" style="padding:12px 32px;font-size:0.95rem;">
            ✅ AI設定を保存する
        </button>
    </div>
</form>
@endsection
