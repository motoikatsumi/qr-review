<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSettingController extends Controller
{
    private function getStore()
    {
        $user = Auth::user();
        return $user->isAdmin() ? null : $user->store;
    }

    public function edit()
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $store->load(['businessType', 'postTemplate']);
        return view('store.settings.ai', compact('store'));
    }

    public function update(Request $request)
    {
        $store = $this->getStore();
        if (!$store) return redirect('/admin/stores');

        $validated = $request->validate([
            'ai_custom_instruction' => 'nullable|string|max:1000',
            'ai_extra_ng_words'     => 'nullable|string|max:1000',
            'ai_tone_preference'    => 'nullable|in:auto,formal,casual',
            'ai_area_keywords'      => 'nullable|string|max:2000',
            'ai_service_keywords'   => 'nullable|string|max:1000',
            'ai_reply_instruction'  => 'nullable|string|max:2000',
            'ai_store_description'  => 'nullable|string|max:500',
            'custom_hashtags'       => 'nullable|string|max:2000',
            'ai_reply_length'       => 'nullable|in:short,medium,long',
            'ai_suggestion_length'  => 'nullable|in:short,medium,long',
            'post_footer_template'  => 'nullable|string|max:2000',
        ]);

        $validated['ai_tone_preference']  = $validated['ai_tone_preference']  ?? 'auto';
        $validated['ai_reply_length']     = $validated['ai_reply_length']     ?? 'medium';
        $validated['ai_suggestion_length']= $validated['ai_suggestion_length']?? 'medium';

        $footerText = $validated['post_footer_template'] ?? '';
        unset($validated['post_footer_template']);
        if ($footerText) {
            $store->postTemplate()->updateOrCreate(
                ['store_id' => $store->id],
                ['template_text' => $footerText]
            );
        } elseif ($store->postTemplate) {
            $store->postTemplate->delete();
        }

        $store->update($validated);

        return redirect('/store/settings/ai')->with('success', 'AI 設定を保存しました。');
    }

    public function aiSuggest(Request $request)
    {
        $store = $this->getStore();
        if (!$store) return response()->json(['success' => false, 'error' => '権限がありません'], 403);

        $validated = $request->validate([
            'target' => 'required|string|in:store_description,custom_instruction,area_keywords,service_keywords,ng_words,reply_instruction,custom_hashtags,all',
        ]);

        $industry  = $store->businessType?->name ?? 'その他のお店';
        $context   = "店舗名『{$store->name}』、業種『{$industry}』";
        $apiKey    = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'GEMINI_API_KEY が未設定です'], 500);
        }

        try {
            $result = [];
            $target = $validated['target'];

            if ($target === 'store_description' || $target === 'all') {
                $result['ai_store_description'] = $this->genText(
                    "{$context} の AI 文章生成用の『店舗自己紹介』を生成してください。文末は『…のスタッフ』で終わる 1 文。30 文字以内。"
                );
            }
            if ($target === 'custom_instruction' || $target === 'all') {
                $result['ai_custom_instruction'] = $this->genText(
                    "{$context} のお店の特徴・基本方針を AI に伝える 1〜2 文。100 文字以内、店舗名は含めず特徴のみ。"
                );
            }
            if ($target === 'area_keywords' || $target === 'all') {
                $result['ai_area_keywords'] = $this->genLines(
                    "{$context} で使うエリア（地域）キーワードを 5〜10 個生成。1 行 1 ワード。"
                );
            }
            if ($target === 'service_keywords' || $target === 'all') {
                $result['ai_service_keywords'] = $this->genLines(
                    "{$context} で扱う『サービスキーワード』を 5〜10 個生成。1 行 1 ワード。"
                );
            }
            if ($target === 'ng_words' || $target === 'all') {
                $result['ai_extra_ng_words'] = $this->genLines(
                    "{$context} で AI が口コミを書く時に避けたい『追加 NG ワード』を 5〜10 個。1 行 1 ワード。"
                );
            }
            if ($target === 'reply_instruction' || $target === 'all') {
                $result['ai_reply_instruction'] = $this->genText(
                    "{$context} で Google レビューに返信する際の AI への特別指示文を 1〜2 文。100 文字以内。"
                );
            }
            if ($target === 'custom_hashtags' || $target === 'all') {
                $result['custom_hashtags'] = $this->genLines(
                    "{$context} の Instagram/Facebook 投稿に付ける『店舗固有ハッシュタグ』を 5〜8 個。# は付けず単語のみ。"
                );
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('Store(owner) aiSuggest failed', ['store_id' => $store->id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'AI 生成に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    private function genText(string $prompt): string
    {
        $text = $this->callGemini($prompt, false);
        $text = trim($text);
        $text = preg_replace('/^[\-\*・●\d+\.\s]+/u', '', $text);
        return mb_substr(trim($text), 0, 200);
    }

    private function genLines(string $prompt): string
    {
        $prompt .= "\n\n【出力】JSON 配列形式で出力。例: [\"項目1\", \"項目2\"]。日本語のみ、説明・前置きは不要。";
        $text = $this->callGemini($prompt, true);
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $data = json_decode(trim($text), true);
        if (!is_array($data)) {
            $lines = preg_split('/\r?\n/', $text);
            $data = [];
            foreach ($lines as $line) {
                $line = trim($line);
                $line = preg_replace('/^["\-\*・●\d+\.\s)\(]+|["\s,]+$/u', '', $line ?? '');
                if ($line && preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $line)) {
                    $data[] = $line;
                }
            }
        }
        $clean = array_map(fn($v) => is_string($v) ? trim($v) : '', $data);
        $clean = array_filter($clean, fn($v) => $v !== '' && preg_match('/\p{Han}|\p{Hiragana}|\p{Katakana}/u', $v));
        return implode("\n", array_unique($clean));
    }

    private function callGemini(string $prompt, bool $jsonMode = false): string
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $url    = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
        $config = [
            'temperature' => 0.7,
            'maxOutputTokens' => 2000,
            'thinkingConfig' => ['thinkingBudget' => 0],
        ];
        if ($jsonMode) {
            $config['response_mime_type'] = 'application/json';
        }
        $resp = Http::timeout(45)->post($url, [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => $config,
        ]);
        if (!$resp->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $resp->status());
        }
        return trim((string) data_get($resp->json(), 'candidates.0.content.parts.0.text', ''));
    }
}
