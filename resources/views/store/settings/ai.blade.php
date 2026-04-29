@extends('layouts.store')

@section('title', 'AI 設定')

@section('content')
<div class="page-header">
    <h1>🤖 AI 設定</h1>
</div>

@push('styles')
<style>
    .ai-help { background: linear-gradient(135deg,#f0f9ff,#e0f2fe); border:1px solid #bae6fd; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 0.85rem; color:#075985; }
    .ai-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 16px; }
    .ai-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 16px 18px; }
    .ai-card h3 { font-size: 0.92rem; color:#065f46; margin-bottom: 6px; display:flex; align-items:center; gap:8px; }
    .ai-card .desc { font-size: 0.78rem; color:#888; margin-bottom: 10px; }
    .ai-card textarea { width:100%; padding:10px 12px; border:2px solid #e5e7eb; border-radius:8px; font-size:0.88rem; font-family:inherit; resize:vertical; }
    .ai-card textarea:focus { border-color:#059669; outline:none; }
    .ai-card .example { background:#f9fafb; border-left:3px solid #d1d5db; padding:8px 12px; font-size:0.78rem; color:#666; border-radius:6px; margin-top:8px; }
    .ai-row { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .ai-row .ai-card { flex:1; min-width:200px; }
    .radio-row { display:flex; gap:8px; flex-wrap:wrap; }
    .radio-row label { flex:1; min-width:80px; padding: 8px 10px; border: 2px solid #e5e7eb; border-radius:8px; cursor:pointer; text-align:center; font-size:0.82rem; }
    .radio-row label:has(input:checked) { border-color:#059669; background:#ecfdf5; color:#065f46; font-weight:600; }
    .radio-row input { display:none; }
    .save-bar { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 16px 20px; margin-top: 20px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .save-bar .save-hint { color:#666; font-size:0.85rem; }
    .ai-suggest-banner { background: linear-gradient(135deg,#fef3c7,#fde68a); border:1px solid #fcd34d; border-radius:10px; padding:14px 18px; margin-bottom:20px; }
    .ai-suggest-banner button { background:#f59e0b; color:white; border:none; padding:8px 16px; border-radius:8px; font-size:0.85rem; font-weight:600; cursor:pointer; }
    .ai-suggest-banner button:hover { background:#d97706; }
    .ai-suggest-banner button:disabled { opacity:0.5; cursor:wait; }
</style>
@endpush

<div class="ai-help">
    AI が口コミ提案文や Google 返信文を生成するときの、お店ごとの設定です。<br>
    空欄でも業種「<strong>{{ $store->businessType->name ?? '未設定' }}</strong>」のデフォルト設定で動作します。
</div>

<div class="ai-suggest-banner">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span style="font-size:1.2rem;">✨</span>
        <strong>AI 入力サポート</strong>
        <span style="font-size:0.82rem;color:#92400e;">店舗名・業種から各項目を一括生成します（下書きとして使ってください）</span>
        <button type="button" id="aiFillBtn">✨ AI で一括生成</button>
        <span id="aiFillStatus" style="font-size:0.82rem;color:#92400e;"></span>
    </div>
</div>

<form method="POST" action="/store/settings/ai">
    @csrf
    @method('PUT')

    {{-- トーン・文字数（横並び） --}}
    <div class="ai-row">
        <div class="ai-card">
            <h3>🎨 文体の方向性</h3>
            <div class="radio-row">
                @foreach(['auto'=>'自動','formal'=>'フォーマル','casual'=>'カジュアル'] as $val=>$label)
                    <label>
                        <input type="radio" name="ai_tone_preference" value="{{ $val }}" {{ old('ai_tone_preference', $store->ai_tone_preference ?? 'auto') === $val ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="ai-card">
            <h3>📏 Google 返信の文字数</h3>
            <div class="radio-row">
                @foreach(['short'=>'短め','medium'=>'標準','long'=>'長め'] as $val=>$label)
                    <label>
                        <input type="radio" name="ai_reply_length" value="{{ $val }}" {{ old('ai_reply_length', $store->ai_reply_length ?? 'medium') === $val ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="ai-card">
            <h3>✍️ QR 提案文の長さ</h3>
            <div class="radio-row">
                @foreach(['short'=>'短め','medium'=>'標準','long'=>'長め'] as $val=>$label)
                    <label>
                        <input type="radio" name="ai_suggestion_length" value="{{ $val }}" {{ old('ai_suggestion_length', $store->ai_suggestion_length ?? 'medium') === $val ? 'checked' : '' }}>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="ai-grid">
        <div class="ai-card">
            <h3>💡 お店の特徴・基本方針</h3>
            <div class="desc">AI にこの店舗のことを教えてあげましょう（100 文字以内）</div>
            <textarea name="ai_custom_instruction" rows="4" placeholder="空欄の場合、業種の標準設定で動作します">{{ old('ai_custom_instruction', $store->ai_custom_instruction) }}</textarea>
            <div class="example">例: 家族連れ歓迎、駅から徒歩 3 分、個室あり</div>
        </div>

        <div class="ai-card">
            <h3>🚫 追加 NG ワード</h3>
            <div class="desc">この店舗だけで禁止したい単語（1 行 1 ワード）</div>
            <textarea name="ai_extra_ng_words" rows="4" placeholder="空欄の場合、業種の NG ワードのみが適用されます">{{ old('ai_extra_ng_words', $store->ai_extra_ng_words) }}</textarea>
            <div class="example">例: 競合店名 / 個人情報 / 廃止メニュー名</div>
        </div>

        <div class="ai-card">
            <h3>📍 地名キーワード（MEO 対策）</h3>
            <div class="desc">返信に自然に織り込む地名（1 行 1 パターン）</div>
            <textarea name="ai_area_keywords" rows="4" placeholder="空欄の場合、返信に地名は含まれません">{{ old('ai_area_keywords', $store->ai_area_keywords) }}</textarea>
            <div class="example">例: 「○○市」「△△駅前」を 1 回ずつ自然に入れる</div>
        </div>

        <div class="ai-card">
            <h3>🔑 サービスキーワード（MEO 対策）</h3>
            <div class="desc">返信に含めたいサービス名（1 行 1 ワード）</div>
            <textarea name="ai_service_keywords" rows="4" placeholder="空欄の場合、サービスキーワードは含まれません">{{ old('ai_service_keywords', $store->ai_service_keywords) }}</textarea>
            <div class="example">例: 高価買取 / 無料査定 / ランチ</div>
        </div>

        <div class="ai-card">
            <h3>💬 口コミ返信の方針</h3>
            <div class="desc">返信スタイルの指示（1〜2 文）</div>
            <textarea name="ai_reply_instruction" rows="4" placeholder="空欄の場合、標準テンプレートで生成します">{{ old('ai_reply_instruction', $store->ai_reply_instruction) }}</textarea>
            <div class="example">例: 感謝 → 口コミへの言及 → サービス紹介 → 締めの順</div>
        </div>

        <div class="ai-card">
            <h3>🏢 店舗紹介文（AI 生成用）</h3>
            <div class="desc">「…のスタッフ」で終わる 1 文</div>
            <textarea name="ai_store_description" rows="3" placeholder="空欄の場合、「（店舗名）のスタッフ」で生成します">{{ old('ai_store_description', $store->ai_store_description) }}</textarea>
            <div class="example">例: 地域密着型リサイクルショップ ○○ のスタッフ</div>
        </div>

        <div class="ai-card">
            <h3>#️⃣ 店舗ハッシュタグ</h3>
            <div class="desc">Instagram/Facebook 投稿に付与（1 行 1 タグ、# 不要）</div>
            <textarea name="custom_hashtags" rows="4" placeholder="店舗名&#10;地域名&#10;サービス名">{{ old('custom_hashtags', $store->custom_hashtags) }}</textarea>
            <div class="example">業種のデフォルトタグに加えて付与されます</div>
        </div>

        <div class="ai-card">
            <h3>📝 投稿フッターテンプレート</h3>
            <div class="desc">投稿のブロック③に自動セット。<code>○○</code> はカテゴリ名に置換されます</div>
            <textarea name="post_footer_template" rows="4">{{ old('post_footer_template', $store->postTemplate->template_text ?? '') }}</textarea>
            <div class="example">例: ○○の買取なら○○店へ！ ご来店お待ちしています</div>
        </div>
    </div>

    <div class="save-bar">
        <span class="save-hint">💡 保存後、次回の AI 生成から反映されます</span>
        <button type="submit" class="btn btn-primary">✅ AI 設定を保存する</button>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('aiFillBtn').addEventListener('click', async function() {
    const btn    = this;
    const status = document.getElementById('aiFillStatus');
    if (!confirm('AI で各項目の下書きを一括生成します。既存の入力は上書きされませんが、空欄の項目に値が入ります。続けますか？')) return;

    btn.disabled = true;
    status.textContent = '生成中... 30 秒ほどかかります';

    try {
        const resp = await fetch('/store/settings/ai/ai-suggest', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ target: 'all' }),
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || '生成に失敗しました');

        const map = {
            ai_store_description: 'ai_store_description',
            ai_custom_instruction: 'ai_custom_instruction',
            ai_area_keywords:      'ai_area_keywords',
            ai_service_keywords:   'ai_service_keywords',
            ai_extra_ng_words:     'ai_extra_ng_words',
            ai_reply_instruction:  'ai_reply_instruction',
            custom_hashtags:       'custom_hashtags',
        };
        let filled = 0;
        for (const [key, name] of Object.entries(map)) {
            const el = document.querySelector(`textarea[name="${name}"]`);
            if (el && !el.value.trim() && json.data[key]) {
                el.value = json.data[key];
                filled++;
            }
        }
        status.textContent = `✅ ${filled} 項目に下書きをセットしました（保存ボタンで確定）`;
    } catch (e) {
        status.textContent = '❌ ' + e.message;
    } finally {
        btn.disabled = false;
    }
});
</script>
@endpush
@endsection
