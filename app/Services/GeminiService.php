<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    }

    /**
     * AI月間利用上限をチェック（超過していたらエラーメッセージを返す）
     */
    protected function checkMonthlyLimit(): ?string
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (!$tenant) {
            return null; // ローカル開発時は無制限
        }

        $limit = $tenant->ai_monthly_limit ?? 200;
        $used = AiUsageLog::monthlyCount();

        if ($used >= $limit) {
            return '今月のAI利用上限（' . $limit . '回）に達しました。プランのアップグレードをご検討ください。';
        }

        return null;
    }

    /**
     * AI利用ログを記録
     */
    protected function logUsage(string $action, ?int $storeId = null, int $tokensUsed = 0): void
    {
        try {
            AiUsageLog::create([
                'action' => $action,
                'store_id' => $storeId,
                'user_id' => auth()->id(),
                'tokens_used' => $tokensUsed,
            ]);
        } catch (\Exception $e) {
            Log::warning('AI usage log failed', ['error' => $e->getMessage()]);
        }
    }

    // =========================================================
    // 業種ごとのデフォルトプリセット（汎用フォールバック）
    // =========================================================

    private function defaultFocuses(): array
    {
        return [
            'お店の雰囲気や居心地も交えて',
            '結果（対応やサービス内容）を中心に',
            '相談しやすい雰囲気だったことを中心に',
            'また来たいという気持ちを中心に',
            'スタッフへの感謝を中心に',
            '予想以上に満足できたことを中心に',
            '対応のスピード感を中心に',
            '説明の丁寧さ・わかりやすさを中心に',
            '店内の清潔感や印象を交えて',
            '思っていたより良かった驚きを中心に',
            '気軽に相談できる雰囲気を中心に',
            '接客の距離感がちょうど良かったことを中心に',
            '知人にもすすめたいという気持ちを中心に',
            '緊張していたけど安心できたことを中心に',
            '立地やアクセスの良さにも触れて',
            '待ち時間が短くスムーズだったことを中心に',
            '無理な営業がなく安心だったことを中心に',
            '納得のいく説明をしてもらえたことを中心に',
            '想像よりカジュアルで入りやすかったことを中心に',
            '帰り際の気持ちの良さを中心に',
        ];
    }

    private function defaultStyles(): array
    {
        return [
            '初めてこの店を利用した新規のお客様',
            '何度も利用しているリピーターのお客様',
            '家族や友人に紹介したいと思っているタイプ',
            'ネットで他店と比較してから来店した慎重なタイプ',
            '普段あまり口コミを書かない素朴なタイプ',
            '短文でサクッと書くタイプ',
            '感謝の気持ちをしっかり伝えたいタイプ',
            '仕事の合間にサッと立ち寄った忙しいタイプ',
            '引っ越しや大掃除をきっかけに来店したタイプ',
            '久しぶりに利用して改めて良さを実感したタイプ',
            '家族に勧められて来店したタイプ',
            '口コミを見て来店を決めたタイプ',
            '通りがかりにふらっと入ってみたタイプ',
            '買い替え資金のために持ち込んだタイプ',
            'たまたま近くに用事があって立ち寄ったタイプ',
            '他の人の参考になればと思って書いているタイプ',
            'イメージよりずっと良かったと感じたタイプ',
        ];
    }

    private function defaultNgWords(): array
    {
        return [
            '借金', '返済', '生活費', 'お金に困って', '金欠',
            '離婚', '元妻', '元夫', '別れた', '元カノ', '元カレ',
            '病気', '入院', '手術費', '治療費', '闘病',
            'ロレックス', 'ルイヴィトン', 'シャネル', 'エルメス',
            'ギャンブル', 'パチンコ', '競馬', 'スロット',
            '亡くなった', '遺品', '形見', '故人', '供養',
        ];
    }

    /** 業種・店舗設定から使用するfocuses/styles/ngWordsを解決する */
    private function resolvePresets(Store $store): array
    {
        $bt = $store->relationLoaded('businessType') ? $store->businessType : $store->businessType;

        $focuses     = ($bt && !empty($bt->focus_presets))  ? $bt->focus_presets  : $this->defaultFocuses();
        $styles      = ($bt && !empty($bt->style_presets))  ? $bt->style_presets  : $this->defaultStyles();
        $ngWords     = ($bt && !empty($bt->ng_words))       ? $bt->ng_words       : $this->defaultNgWords();
        $baseContext = $bt ? $bt->base_context : 'お店のサービスや体験';

        // 店舗独自追加NGワード
        if (!empty($store->ai_extra_ng_words)) {
            $extra = array_filter(array_map('trim', explode("\n", $store->ai_extra_ng_words)));
            $ngWords = array_merge($ngWords, array_values($extra));
        }

        // トーン設定（店舗設定で上書き）
        $tonePreference = $store->ai_tone_preference ?? 'auto';

        return compact('focuses', 'styles', 'ngWords', 'baseContext', 'tonePreference');
    }

    /**
     * 送信された口コミ文がGoogleマップのポリシーに違反していないかチェックする
     * 違反がある場合はエラーメッセージを返し、問題なければnullを返す
     *
     * @param string $comment
     * @param Store|null $store  店舗オブジェクト（業種コンテキスト取得用）
     */
    public function validateGooglePolicy(string $comment, ?Store $store = null): ?string
    {
        // ポリシーチェックは軽量なのでログのみ（制限カウントには含めない）
        $this->logUsage('policy_check', $store?->id ?? null);

        // 業種コンテキストを解決（店舗なければデフォルト）
        $baseContext = 'お店のサービスや体験';
        if ($store) {
            $bt = $store->businessType;
            $baseContext = $bt ? $bt->base_context : $baseContext;
        }

        $prompt = <<<EOT
あなたはGoogleマップの口コミポリシー審査員です。
以下の口コミ文が、Googleマップの「禁止および制限されているコンテンツ」ポリシーに違反しているかどうかを審査してください。

【対象の口コミ文】
{$comment}

【審査基準となる主なポリシー違反】
1. スパムと虚偽のエンゲージメント（意味のない文字列、URLのみなど）
2. 関連性のないコンテンツ（{$baseContext}と無関係な政治的・個人的な主張など）
3. 不適切なコンテンツ（ヘイトスピーチ、嫌がらせ、個人攻撃、卑猥な言葉、差別的な発言など）
4. 露骨な性的表現
5. 冒涜的な言葉

判定ルール：
- もし上記ポリシーのいずれかに【明確に違反している】場合は、その理由を日本語で簡潔に指摘してください（例：「不適切な言葉が含まれているため投稿できません」など）。
- 違反しておらず、通常の口コミとして【問題ない】場合は、必ず「OK」という2文字だけを出力してください。
- 多少の誤字脱字やネガティブな意見はポリシー違反ではないため「OK」としてください。
EOT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // 判定なので低く設定
                    'maxOutputTokens' => 150,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $result = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                
                // 「OK」なら違反なし
                if ($result === 'OK' || strpos($result, 'OK') === 0) {
                    return null;
                }
                
                // それ以外は違反理由として返す
                return $result ?: '申し訳ありません。ポリシー違反の可能性があるため送信できませんでした。';
            }

            Log::error('Gemini API error (policy check)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // APIエラー時はユーザーの入力を阻害しないようnullを返す
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (policy check)', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * キーワードボタンからGoogleマップ用の口コミ提案文を生成
     *
     * @param Store $store       店舗オブジェクト（業種・AI設定取得用）
     * @param string|array $keywords
     * @param string $gender
     * @param string $age
     * @param string $visitType
     * @param string $item       利用品目（例: ブランド品 / 貴金属 / 焼肉コース 等、業種依存）
     * @param array  $personaExtra カスタム質問を含む全 persona（key => value のマップ）
     * @return string|null
     */
    public function generateSuggestion(Store $store, $keywords, string $gender = '', string $age = '', string $visitType = '', string $item = '', array $personaExtra = []): ?string
    {
        // 月間利用上限チェック
        $limitError = $this->checkMonthlyLimit();
        if ($limitError) {
            return $limitError;
        }
        $this->logUsage('suggest', $store->id);

        // 業種プリセットを解決
        $presets = $this->resolvePresets($store);
        $focuses        = $presets['focuses'];
        $styles         = $presets['styles'];
        $ngWords        = $presets['ngWords'];
        $tonePreference = $presets['tonePreference'];
        $storeName      = $store->name;

        // キーワードを統一的に扱う
        if (is_array($keywords)) {
            $keyword = implode('・', $keywords);
        } else {
            $keyword = $keywords;
        }
        // 書き手のペルソナ：ユーザー入力があれば使用、なければランダム
        if ($gender && $age) {
            $persona = "{$age}代の{$gender}";
        } elseif ($gender) {
            $ages = ['20', '30', '40', '50', '60'];
            $persona = $ages[array_rand($ages)] . "代の{$gender}";
        } elseif ($age) {
            $genders = ['男性', '女性'];
            $persona = "{$age}代の" . $genders[array_rand($genders)];
        } else {
            $ages = ['20', '30', '40', '50', '60'];
            $genders = ['男性', '女性'];
            $persona = $ages[array_rand($ages)] . '代の' . $genders[array_rand($genders)];
        }

        // 来店タイプをペルソナに追加
        if ($visitType) {
            $persona .= "（{$visitType}のお客様）";
        }
        // 利用品目をペルソナに追加（例: 「ブランド品を持ち込んだお客様」「焼肉コースを注文したお客様」）
        if ($item) {
            $persona .= "（利用品目: {$item}）";
        }
        // カスタム質問の回答（gender/age/visit_type/item 以外）もペルソナに追加
        foreach ($personaExtra as $key => $value) {
            if (in_array($key, ['gender', 'age', 'visit_type', 'item'], true)) continue;
            if (is_string($value) && $value !== '') {
                $persona .= "（{$key}: {$value}）";
            }
        }

        // 選択項目を文章生成の「内容」に反映させる強制指示
        $itemInstruction = '';
        if ($item) {
            $itemInstruction = "- 【最重要・内容】この口コミは「{$item}」についての体験を書く。文章のどこかで「{$item}」に関する内容（具体的な体験・感想・印象）を必ず自然に含める。他の利用品目や話題には触れない。";
        }
        if ($visitType) {
            $itemInstruction .= ($itemInstruction ? "\n" : '') . "- 【来店の文脈】「{$visitType}」という状況を踏まえた自然な口コミにする（文中に直接「{$visitType}」という単語を書く必要はないが、その状況に合わせた表現にする）。";
        }
        // カスタム項目の反映
        foreach ($personaExtra as $key => $value) {
            if (in_array($key, ['gender', 'age', 'visit_type', 'item'], true)) continue;
            if (is_string($value) && $value !== '') {
                $itemInstruction .= ($itemInstruction ? "\n" : '') . "- 【{$key}】「{$value}」という状況や内容を自然に反映する。";
            }
        }

        // 文章のトーン
        $allTones = [
            'あっさりと落ち着いた口調',
            '少し感動した様子の口調',
            '友人に話しかけるような親しみやすい口調',
            'シンプルで率直な口調',
            '安心感を伝える口調',
            'ほっとした気持ちがにじむ口調',
            '控えめだけど満足している口調',
            '素直に嬉しさを表現する口調',
            '淡々としつつも好印象な口調',
            '丁寧で誠実さが伝わる口調',
            '軽やかで明るい口調',
            'しみじみと感謝を伝える口調',
            'さっぱりとした爽やかな口調',
            'ちょっと驚いた感じの口調',
            '穏やかで温かみのある口調',
            '実用的で要点を押さえた口調',
            'さりげなく好印象を伝える口調',
            '少し照れながらも嬉しそうな口調',
            '落ち着いていて信頼感のある口調',
            '前向きで気持ちの良い口調',
        ];

        // トーン設定に応じて選択肢を絞る
        if ($tonePreference === 'formal') {
            $tones = array_values(array_filter($allTones, fn($t) => in_array($t, [
                'あっさりと落ち着いた口調', 'シンプルで率直な口調', '丁寧で誠実さが伝わる口調',
                '穏やかで温かみのある口調', '実用的で要点を押さえた口調', '落ち着いていて信頼感のある口調',
            ])));
        } elseif ($tonePreference === 'casual') {
            $tones = array_values(array_filter($allTones, fn($t) => in_array($t, [
                '友人に話しかけるような親しみやすい口調', '軽やかで明るい口調', 'ちょっと驚いた感じの口調',
                '少し照れながらも嬉しそうな口調', '前向きで気持ちの良い口調', 'さっぱりとした爽やかな口調',
            ])));
        } else {
            $tones = $allTones;
        }

        // 店舗のAIカスタム指示
        $customInstruction = !empty($store->ai_custom_instruction)
            ? "\n【この店舗への追加情報】\n" . $store->ai_custom_instruction . "\n"
            : '';

        $tone  = $tones[array_rand($tones)];
        $focus = $focuses[array_rand($focuses)];
        $style = $styles[array_rand($styles)];

        // トーン設定に応じた文体指示（最重要レベルで強制）
        if ($tonePreference === 'casual') {
            $toneInstruction = '- 【最重要・文体】カジュアルで親しみやすい文体にすること。堅い敬語は避け、「〜ですね！」「〜でした！」「〜かも」「〜ですよね」「めっちゃ」「すごく」など、友達に話すような柔らかく砕けた表現を多用する。文末に「！」や絵文字を積極的に使い、明るく軽快な印象にする。「〜いただき」「〜くださり」「〜ございます」のような堅い敬語表現は使わないこと';
        } elseif ($tonePreference === 'formal') {
            $toneInstruction = '- 【最重要・文体】フォーマルで落ち着いた文体にすること。「です/ます」調を厳守し、「〜いただきました」「〜ございました」など丁寧で品のある敬語表現を使う。絵文字は使わない。「！」は控えめに。知的で落ち着いた印象にする';
        } else {
            $toneInstruction = '- 【重要】フランクになりすぎず、最低限の敬語や丁寧な言葉遣い（です/ます調）を守る';
        }

        // 書き手の性格
        $personalities = [
            '論理的で事実ベースに書くタイプ。感情より具体的な体験を重視する',
            '感覚的で雰囲気を大切にするタイプ。空間や居心地の印象を書きがち',
            '社交的でオープンなタイプ。人とのやりとりや会話の印象を書きがち',
            '慎重で石橋を叩くタイプ。事前の不安が解消された安心感を書きがち',
            '感情豊かで素直なタイプ。嬉しかった・良かったという気持ちをストレートに書く',
            '寡黙で言葉少なめなタイプ。最小限の言葉でポイントだけ伝える',
            '世話好きで他人思いなタイプ。他の人にもおすすめしたい気持ちを書きがち',
            '好奇心旺盛なタイプ。新しい体験としての面白さや発見を書きがち',
            '几帳面で段取りを重視するタイプ。手続きのスムーズさや流れを書きがち',
            '直感的で第一印象を大事にするタイプ。お店に入った瞬間の印象を書きがち',
            'おおらかでこだわらないタイプ。細かいことより全体の満足感を書く',
            '心配性だけど結果に安心するタイプ。不安からの安心というストーリーで書く',
            '合理的で効率を重視するタイプ。時間対効果や手軽さを書きがち',
            '人見知りだけど温かい気持ちを持つタイプ。控えめだけど感謝を丁寧に伝える',
            '行動力があり即決タイプ。テンポよく端的に良かった点を書く',
            'のんびり穏やかなタイプ。ゆったりした空気感で柔らかく書く',
        ];
        $personality = $personalities[array_rand($personalities)];

        // 文字数パターン（ランダム・重み付き）— 店舗設定に応じて調整
        $suggestionLength = $store->ai_suggestion_length ?? 'medium';
        if ($suggestionLength === 'short') {
            $lengthPatterns = [
                '15〜30文字程度の短い文章にする。1文で簡潔にまとめる',
                '15〜30文字程度の短い文章にする。1文で簡潔にまとめる',
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '30〜50文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '30〜50文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '30〜50文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '40〜60文字程度の文章にする。1〜2文でまとめる',
                '40〜60文字程度の文章にする。1〜2文でまとめる',
            ];
        } elseif ($suggestionLength === 'long') {
            $lengthPatterns = [
                '50〜70文字程度の文章にする。2文でまとめる',
                '50〜70文字程度の文章にする。2文でまとめる',
                '60〜90文字程度の文章にする。2〜3文でまとめる',
                '60〜90文字程度の文章にする。2〜3文でまとめる',
                '60〜90文字程度の文章にする。2〜3文でまとめる',
                '80〜120文字程度の文章にする。2〜3文でしっかりめにまとめる',
                '80〜120文字程度の文章にする。2〜3文でしっかりめにまとめる',
                '80〜120文字程度の文章にする。2〜3文でしっかりめにまとめる',
                '100〜150文字程度の文章にする。3〜4文でしっかりめにまとめる',
                '100〜150文字程度の文章にする。3〜4文でしっかりめにまとめる',
            ];
        } else {
            $lengthPatterns = [
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',        // 25%
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '20〜40文字程度の短い文章にする。1文で簡潔にまとめる',
                '40〜60文字程度の短い文章にする。1〜2文で簡潔にまとめる',    // 30%
                '40〜60文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '40〜60文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '40〜60文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '40〜60文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '40〜60文字程度の短い文章にする。1〜2文で簡潔にまとめる',
                '40〜70文字程度の文章にする。1〜2文でまとめる',              // 20%
                '40〜70文字程度の文章にする。1〜2文でまとめる',
                '40〜70文字程度の文章にする。1〜2文でまとめる',
                '40〜70文字程度の文章にする。1〜2文でまとめる',
                '50〜70文字程度の文章にする。2文でまとめる',                 // 15%
                '50〜70文字程度の文章にする。2文でまとめる',
                '50〜70文字程度の文章にする。2文でまとめる',
                '70〜90文字程度の文章にする。2〜3文でしっかりめにまとめる',  // 10%
                '70〜90文字程度の文章にする。2〜3文でしっかりめにまとめる',
            ];
        }
        $lengthInstruction = $lengthPatterns[array_rand($lengthPatterns)];

        // 「初めて」系表現の偏りを防ぐ：スタイルが新規客でない場合、またはリピーターの場合は回避指示を追加
        $avoidFirstTime = '';
        if ($visitType === 'リピーター' || (strpos($style, '初めて') === false && strpos($style, '新規') === false)) {
            $avoidFirstTime = '- 「初めて」「初めての」「初めて利用」という表現は使わないこと。既に何度か来店している前提で書く' . "\n";
        }

        // 複数テーマ選択時は文字数を増やす
        $multiThemeInstruction = '';
        if (is_array($keywords) && count($keywords) >= 2) {
            $multiThemeInstruction = '- 【重要】テーマが複数あるが、全てのテーマを無理に詰め込まず、自然に組み合わせて1つの口コミとしてまとめること' . "\n";
            // 複数テーマ時は短すぎる文字数パターンを除外して少し長めにする
            $lengthPatterns = [
                '40〜60文字程度の文章にする。1〜2文で簡潔にまとめる',
                '40〜70文字程度の文章にする。1〜2文でまとめる',
                '50〜70文字程度の文章にする。2文でまとめる',
                '50〜70文字程度の文章にする。2文でまとめる',
                '70〜90文字程度の文章にする。2〜3文でしっかりめにまとめる',
                '70〜90文字程度の文章にする。2〜3文でしっかりめにまとめる',
                '80〜120文字程度の文章にする。2〜3文でしっかりめにまとめる',
            ];
            $lengthInstruction = $lengthPatterns[array_rand($lengthPatterns)];
        }

        // NGワードリストを文字列に整形（業種+店舗固有の組み合わせ）
        $ngWordLines = '';
        if (!empty($ngWords)) {
            $ngWordLines = '- ' . implode('、', $ngWords) . "\n";
        }
        $ngWordLines .= '- 盗品や不正を連想させる表現（例：「もらったもの」「拾った」「見つけた」）' . "\n";

        $prompt = <<<EOT
Googleマップに投稿する口コミ文を1つ生成してください。

★★★ 絶対厳守ルール（これを最優先で守ること）★★★
{$toneInstruction}
- 【最重要・文字数】{$lengthInstruction}。この文字数を厳密に守ること。指定より長い文章は絶対に書かない
{$itemInstruction}
★★★ ここまで ★★★

【店舗名】{$storeName}
【テーマ】{$keyword}
【書き手のペルソナ】{$persona}
【書き手の性格】{$personality}
【書き手のスタイル】{$style}
【文章のトーン】{$tone}
【文章の切り口】{$focus}
{$customInstruction}
条件：
- 【最重要】{$lengthInstruction}
- 最後は「。」「！」「絵文字」のいずれかで終わる。絵文字で終わる場合は「。」や「！」は付けない。たまに句読点も絵文字もなく言い切りで終わってもよい（例：「また来たいです」）。「。！」「！。」のような句読を連続で使わない
- 「」（カギ括弧）は一切使わない
- 指定されたペルソナ・スタイル・トーン・切り口を反映した自然な口語体にする
- 【重要】性別に関係なく、女性語・お嬢様言葉は絶対に使わない。「〜わ」「〜のよ」「〜の」「〜かしら」などの女性的な語尾は禁止。男女とも同じ丁寧語（です/ます）で統一する
- 【重要】お客様自身を下げるような発言（卑下など）や、不快感を与えるネガティブ・おかしい文章は絶対に含めない
{$avoidFirstTime}{$multiThemeInstruction}- 絵文字・説明文・前置き不要。口コミ文のみ出力

【NGワード・禁止表現】以下の内容は絶対に含めないこと：
- 具体的な金額（例：「○○円」「○万円」）
- 他店名や競合店の名前
- スタッフの個人名・フルネーム
- 個人情報（住所、電話番号、メールアドレスなど）
- 「最高額」「業界一」「日本一」などの誇大表現
- 【最重要】女性語・役割語は一切禁止。語尾に「わ」「のよ」「の」「かしら」を付ける表現は性別問わず全て禁止（例：「〜たわ」「〜だわ」「〜るわ」「〜ですわ」「〜ましたわ」「〜できるの」「〜のよね」「〜のよ」「〜かしら」「〜ますのよ」「〜でございますわ」）。文末が「〜の。」「〜の！」で終わるのも禁止
- 不自然な語尾延ばし（例：「〜ねぇ」「〜ねー」「〜よぉ」「〜なぁ」「〜かなぁ」「〜とはねぇ」）。年配者風の口調にしないこと
EOT;
        $prompt .= $ngWordLines;


        try {
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]],
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 1.0,
                    'maxOutputTokens' => 150,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                return $text ? $this->cleanSuggestionText($text) : null;
            }

            Log::error('Gemini API error (suggestion)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (suggestion)', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * AI生成テキストの後処理クリーニング
     */
    private function cleanSuggestionText(string $text): string
    {
        // 前後の空白・改行を除去
        $text = trim($text);

        // 「」カギ括弧で囲まれていたら中身だけ取り出す
        $text = preg_replace('/^「(.+)」$/us', '$1', $text);

        // 「」が文中に残っていても除去
        $text = str_replace(['「', '」'], '', $text);

        // 。！ や ！。 の連続句読点を正規化（！ or 。 どちらか一方に統一）
        $text = preg_replace('/。！+/', '。', $text);
        $text = preg_replace('/！。+/', '！', $text);
        $text = preg_replace('/。{2,}/', '。', $text);
        $text = preg_replace('/！{2,}/', '！', $text);

        // 禁止語尾パターンの後処理（AIが指示を無視する場合の安全策）
        $text = preg_replace('/ですわ([。！💯✨😊😭🙏👍]|$)/u', 'です$1', $text);
        $text = preg_replace('/ましたわ([。！💯✨😊😭🙏👍]|$)/u', 'ました$1', $text);
        $text = preg_replace('/ねぇ([。！💯✨😊😭🙏👍]|$)/u', 'ね$1', $text);
        $text = preg_replace('/ねー([。！]|$)/u', 'ね$1', $text);
        $text = preg_replace('/よぉ([。！]|$)/u', 'よ$1', $text);
        $text = preg_replace('/なぁ([。！]|$)/u', 'な$1', $text);

        return trim($text);
    }

    /**
     * 返信テキストの後処理クリーニング
     */
    private function cleanReplyText(string $text): string
    {
        $text = trim($text);
        // 連続する改行（空行）を単一の改行に置換
        $text = preg_replace('/\n\s*\n/', "\n", $text);
        return $text;
    }

    /**
     * 口コミ内容からお客様が新規・リピーターかをAIで判定
     */
    public function detectCustomerType(string $reviewComment): string
    {
        if (empty(trim($reviewComment))) {
            return 'unknown';
        }

        $prompt = <<<EOT
以下の口コミを読んで、このお客様が「新規」「リピーター」「不明」のどれかを判定してください。

【口コミ内容】
{$reviewComment}

【判定基準】
- 「また来ました」「何度も利用」「いつも」「リピート」「前回も」「毎回」など、複数回利用を示す表現がある → repeater
- 「初めて」「初来店」「初利用」など、初めての利用を示す表現がある → new
- どちらとも判断できない → unknown

【出力】
「new」「repeater」「unknown」のいずれか1語のみ出力してください。それ以外は何も出力しないでください。
EOT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $result = strtolower(trim($data['candidates'][0]['content']['parts'][0]['text'] ?? ''));
                if (in_array($result, ['new', 'repeater', 'unknown'])) {
                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::error('Gemini API exception (detectCustomerType)', ['message' => $e->getMessage()]);
        }

        return 'unknown';
    }

    /**
     * Google口コミへの返信文をAIで生成（MEO対策ワード入り）
     */
    public function generateReplyComment(
        Store $store,
        int $rating,
        string $reviewComment,
        string $category,
        array $keywords,
        string $reviewerName = '',
        string $customerType = 'new'
    ): ?string {
        // 月間利用上限チェック
        $limitError = $this->checkMonthlyLimit();
        if ($limitError) {
            return $limitError;
        }
        $this->logUsage('reply', $store->id);

        $storeName = $store->name;
        $bt = $store->businessType;
        $baseContext = $bt ? $bt->base_context : 'お店のサービスや体験';
        $keywordList = implode('、', $keywords);
        $hasKeywords = !empty($keywords) && $keywordList !== '';

        // 低評価（1〜3星）はGoogleの返信ポリシー（スパム/オフトピック/広告）に抵触しやすいため、
        // MEO要素（商品キーワード・サービス・地域名）を一切含めず謝罪と改善姿勢のみに集中させる
        $isLowRating = $rating > 0 && $rating <= 3;

        // 店舗紹介文（オーナーの自己紹介として使用）
        $storeDescription = !empty($store->ai_store_description)
            ? $store->ai_store_description
            : "{$storeName} のオーナー";

        // お店の追加情報（ai_custom_instruction）
        $customInstruction = '';
        if (!empty($store->ai_custom_instruction)) {
            $customInstruction = "\n【この店舗の特徴・追加情報】\n" . $store->ai_custom_instruction . "\n上記の情報を参考に、返信にお店の特徴を自然に盛り込んでください。\n";
        }

        // NGワード（業種 + 店舗固有）
        $ngWords = ($bt && !empty($bt->ng_words)) ? $bt->ng_words : [];
        if (!empty($store->ai_extra_ng_words)) {
            $extra = array_filter(array_map('trim', explode("\n", $store->ai_extra_ng_words)));
            $ngWords = array_merge($ngWords, array_values($extra));
        }
        $ngWordSection = '';
        if (!empty($ngWords)) {
            $ngWordSection = "\n【NGワード・禁止表現】以下のワードは返信文に含めないこと：\n- " . implode('、', $ngWords) . "\n";
        }

        // 返信構成（店舗カスタム or デフォルトパターン）
        if (!empty($store->ai_reply_instruction) && !$isLowRating) {
            // 店舗独自の返信方針（高評価時のみ。低評価時はGoogleポリシー優先で専用構造を使う）
            $structureText = $store->ai_reply_instruction;
        } elseif ($isLowRating) {
            // 低評価専用：謝罪・共感・改善姿勢のみ（商品紹介・キーワード・地域訴求は含めない）
            $lowRatingPatterns = [
                [
                    '1. ご来店への感謝とご期待に沿えなかったことへの率直なお詫び',
                    '2. 口コミで指摘された不満点に具体的に触れて共感・理解を示す',
                    '3. 改善への取り組み姿勢を簡潔に述べる',
                    '4. 再度の機会をいただきたい旨を伝え、店舗名を含めて締める',
                ],
                [
                    '1. 貴重なご意見への感謝と、ご不便をおかけしたことへのお詫び',
                    '2. 口コミ内容を真摯に受け止めている姿勢を示す',
                    '3. 今後の改善に向けた具体的な意識・姿勢を簡潔に述べる',
                    '4. 信頼回復に努める旨を伝え、店舗名を含めて締める',
                ],
            ];
            $selectedStructure = $lowRatingPatterns[array_rand($lowRatingPatterns)];
            $structureText = implode("\n", $selectedStructure);
        } else {
            // デフォルト：返信構成パターン（バリエーションでテンプレ感を回避）
            $structurePatterns = [
                [
                    '1. お客様への感謝の挨拶（ご来店・評価への感謝を丁寧に。リピーターと分かる場合は「この度も」などで触れる）',
                    '2. 口コミ内容への具体的な言及（褒められたポイントを口コミから抽出して必ず含める。口コミが空の場合はご利用への感謝を丁寧に述べる）',
                    '3. 取扱商品・サービスのキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                    '4. 締めの言葉（またのご来店を促す。店舗名を含める）',
                ],
                [
                    '1. お客様への感謝と口コミ内容への具体的な言及を一体化（感謝を述べつつ、褒められたポイントに触れる）',
                    '2. 当店のこだわりや姿勢を' . $category . 'に絡めて紹介',
                    '3. 取扱商品・サービスのキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                    '4. 締めの言葉（今後のご利用を促す。店舗名を含める）',
                ],
                [
                    '1. お客様への感謝の挨拶（ご来店・評価への感謝を丁寧に）',
                    '2. 口コミ内容に触れつつ、' . $category . 'における当店の強みを紹介',
                    '3. その他の取扱商品・サービスのキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                    '4. 再来店・紹介の促進メッセージ（店舗名を含める）',
                ],
                [
                    '1. お客様の口コミ内容に触れながら感謝を伝える（印象的なフレーズがあれば引用）',
                    '2. ' . $category . 'について当店が大切にしていることを紹介',
                    '3. 取扱商品・サービスのキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                    '4. 締めの言葉（地域密着の姿勢に触れつつ、またのご来店を促す。店舗名を含める）',
                ],
            ];

            $selectedStructure = $structurePatterns[array_rand($structurePatterns)];
            $structureText = implode("\n", $selectedStructure);
        }

        // 顧客タイプの指示
        if ($customerType === 'repeater') {
            $customerTypeInstruction = '- お客様はリピーターです。「いつもご利用いただき」「この度も」「日頃より」など、リピーターであることが伝わる表現を冒頭に含める';
        } elseif ($customerType === 'unknown') {
            $customerTypeInstruction = '- お客様が新規かリピーターか不明です。「この度は」「ご来店いただき」など、どちらにも当てはまる自然な表現を使う。初来店・リピーターのどちらかを決めつけない';
        } else {
            $customerTypeInstruction = '- お客様は新規のお客様です。「この度は初めてのご来店」「初めてご利用いただき」など、初来店であることに触れる表現を冒頭に含める';
        }

        // エリアキーワード（店舗設定から取得）
        // 低評価時はGoogleポリシー（広告・スパム・オフトピック）回避のため一切使わない
        $areaInstruction = '';
        if (!$isLowRating && !empty($store->ai_area_keywords)) {
            $areaPatterns = array_filter(array_map('trim', explode("\n", $store->ai_area_keywords)));
            if (!empty($areaPatterns)) {
                $areaInstruction = $areaPatterns[array_rand($areaPatterns)];
            }
        }

        // サービスキーワード（店舗設定から取得）
        // 低評価時はGoogleポリシー回避のため一切使わない
        if (!$isLowRating && !empty($store->ai_service_keywords)) {
            $serviceKeywords = array_filter(array_map('trim', explode("\n", $store->ai_service_keywords)));
        } else {
            $serviceKeywords = [];
        }
        $serviceKw = !empty($serviceKeywords) ? $serviceKeywords[array_rand($serviceKeywords)] : '';

        // トーン設定（店舗設定から取得）— 強い指示で確実に反映
        $tonePreference = $store->ai_tone_preference ?? 'auto';
        if ($tonePreference === 'casual') {
            $replyToneInstruction = '- 【絶対厳守】親しみやすく温かいカジュアルなオーナー返信にする。堅い敬語（「〜ございます」「〜いただき」）は使わず、「〜ですね！」「〜ですよ！」「〜してくださいね」など、お客様に友人のように語りかける柔らかい文体にする。距離感の近い、気さくな印象を最優先する';
        } elseif ($tonePreference === 'formal') {
            $replyToneInstruction = '- 【絶対厳守】格式高くフォーマルなオーナー返信にする。「〜いただきまして誠にありがとうございます」「〜賜りますよう」「〜ご高覧」など、上品で格調の高い敬語を正確に使う。ビジネスレターのような品格を保つ';
        } else {
            $replyToneInstruction = '- 丁寧で格式のあるオーナー返信にする（です・ます調、敬語）';
        }

        // 返信文字数（店舗設定から取得）— 厳密に制御
        // 低評価時はGoogleポリシー回避のため、店舗設定に関わらず短めに固定（長文だと商品宣伝が混入しやすい）
        $replyLength = $store->ai_reply_length ?? 'medium';
        if ($isLowRating) {
            $replyLengthInstruction = '【絶対厳守】150〜250文字以内の簡潔な返信にする。長文の言い訳・商品紹介・地域訴求は一切不要。お詫びと改善姿勢のみ';
        } elseif ($replyLength === 'short') {
            $replyLengthInstruction = '【絶対厳守】150〜250文字以内の簡潔な返信にする。250文字を超えてはならない';
        } elseif ($replyLength === 'long') {
            $replyLengthInstruction = '【絶対厳守】500〜700文字程度のしっかりした返信にする。500文字未満にしない';
        } else {
            $replyLengthInstruction = '300〜500文字程度のしっかりした返信にする';
        }

        // 過去の フィードバック例（管理者の👍👎学習データ）
        $feedbackSection = '';
        try {
            $goodExamples = \App\Models\AiReplyFeedback::goodExamplesFor($store->id, 2);
            $badExamples = \App\Models\AiReplyFeedback::badExamplesFor($store->id, 2);
            if (!empty($goodExamples) || !empty($badExamples)) {
                $feedbackSection = "\n\n【★過去の評価例（必ず参考にする）】\n";
                if (!empty($goodExamples)) {
                    $feedbackSection .= "■オーナーが『良い』と評価した返信スタイル（このトーン・構成を踏襲）：\n";
                    foreach ($goodExamples as $i => $ex) {
                        $rev = mb_substr((string) $ex['review'], 0, 80);
                        $rep = mb_substr((string) $ex['reply'], 0, 200);
                        $feedbackSection .= "  例" . ($i + 1) . ": 口コミ「{$rev}」 → 返信「{$rep}」\n";
                    }
                }
                if (!empty($badExamples)) {
                    $feedbackSection .= "■オーナーが『悪い』と評価した返信（このような書き方は避ける）：\n";
                    foreach ($badExamples as $i => $ex) {
                        $rep = mb_substr((string) $ex['reply'], 0, 150);
                        $reason = !empty($ex['reason']) ? '（理由: ' . mb_substr($ex['reason'], 0, 60) . '）' : '';
                        $feedbackSection .= "  例" . ($i + 1) . ": 「{$rep}」{$reason}\n";
                    }
                }
            }
        } catch (\Throwable $e) {
            // フィードバックテーブルが未マイグレーションの場合は無視
        }

        $prompt = "あなたは「{$storeDescription}」として、Googleマップに掲載されている「{$baseContext}」を営むお店のオーナーの立場で、お客様の口コミに返信します。\n";
        if (!$isLowRating) {
            $prompt .= "MEO（マップエンジン最適化）対策として、指定された取扱商品のキーワードと地域名を返信文に自然に織り込んでください。\n";
        }
        $prompt .= "\n【店舗名】{$storeName}\n";
        $prompt .= "【口コミの星評価】{$rating}つ星\n";
        $prompt .= "【口コミ内容】{$reviewComment}\n";
        if (!$isLowRating) {
            $prompt .= "【お客様が利用されたカテゴリ】{$category}\n";
            $prompt .= "【返信に含めたい取扱商品キーワード】{$keywordList}\n";
        }
        $prompt .= $customInstruction . $feedbackSection;
        $prompt .= "\n【返信の構成】\n以下の構成で返信してください：\n{$structureText}\n";
        $prompt .= "\n【顧客タイプ】\n{$customerTypeInstruction}\n";

        if ($isLowRating) {
            // 低評価：Googleの返信ポリシー（スパム・広告・オフトピック）に抵触しないよう、MEO要素を一切入れない
            $prompt .= "\n【低評価レビュー対応の絶対条件】";
            $prompt .= "\n- 店舗名「{$storeName}」を返信文中に必ず1回含める";
            $prompt .= "\n- 取扱商品・サービス・地域名・カテゴリのキーワードは一切含めない（Googleのスパム判定・広告判定を避けるため）";
            $prompt .= "\n- 商品の列挙、サービスの宣伝、エリアへの訴求は完全禁止";
            $prompt .= "\n- 口コミで指摘された不満点に直接向き合い、共感とお詫び、改善姿勢のみを述べる";
            $prompt .= "\n- 元の口コミと無関係な営業情報を盛り込まない（オフトピック判定回避）";
        } else {
            $prompt .= "\n【MEO対策条件】";
            if ($areaInstruction) {
                $prompt .= "\n- {$areaInstruction}";
            }
            if ($serviceKw) {
                $prompt .= "\n- 「{$serviceKw}」というワードを自然に1回混ぜる";
            }
            $prompt .= "\n- 店舗名「{$storeName}」を返信文中に必ず1回含める";
            if ($hasKeywords) {
                $prompt .= "\n- 指定された取扱商品キーワードをできるだけ多く自然な文脈で含める";
                if ($category !== '') {
                    $prompt .= "\n- 「{$category}」に関連する表現を口コミへの言及で自然に使う";
                }
            } else {
                $prompt .= "\n- 取扱商品キーワードが未指定のため、口コミ内容に即した一般的な返信にする。ただし店舗の業種（{$baseContext}）に関連する表現で自然な返信にする";
            }
        }

        $prompt .= <<<EOT


★★★ 絶対厳守ルール（これを最優先で守ること）★★★
{$replyToneInstruction}
- {$replyLengthInstruction}
★★★ ここまで ★★★

【Googleレビュー返信ポリシー遵守ルール（必ず守ること）】
- 商品・サービスを3つ以上連続して列挙しない（キーワードスタッフィング判定を避ける）
- 「○○エリアにお住まいの皆様」「○○市の方々」など特定地域の住民に呼びかける勧誘表現は一切使わない
- URL・電話番号・メールアドレス・SNSアカウントなどの連絡先情報は絶対に含めない
- 過度な営業・勧誘的トーン（他店比較、誇大表現、限定オファーの示唆など）は使わない
- 元の口コミ内容と無関係な情報（来店していない商品の宣伝、地域マーケティング訴求など）を主体にしない

【文体・フォーマット条件】
- 各パートの区切りで改行を入れて読みやすくする。ただし空行（空白行）は入れず、改行のみで区切る
- 口コミ内容が空（星評価のみ）の場合は、ご来店と評価への感謝を丁寧に述べる。「どのようなお品物かは存じませんが」のような知らないことを示す表現は絶対に使わない。「温かいお言葉」「嬉しいコメント」など口コミ文章があるかのような表現も使わない（コメントが無いため）。口コミが空の場合はご来店・ご利用・評価への感謝のみにとどめる
- 低評価（1〜3つ星）の場合は、お詫びと改善への姿勢のみを述べる。商品・サービスの紹介や地域訴求は一切含めない
- 高評価（4〜5つ星）の場合は、感謝を込めてまたのご来店を促す
- 「」（カギ括弧）は使わない
- **（アスタリスク）などのマークダウン記法は一切使わない。プレーンテキストのみで出力する
- 絵文字は使わない
- 説明文・前置き不要。返信文のみ出力
{$ngWordSection}
EOT;

        Log::info('generateReplyComment prompt debug', [
            'store' => $store->name,
            'tone_preference' => $store->ai_tone_preference,
            'reply_length' => $store->ai_reply_length,
            'replyToneInstruction' => mb_substr($replyToneInstruction, 0, 80),
            'replyLengthInstruction' => mb_substr($replyLengthInstruction, 0, 80),
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($text) {
                    $text = $this->cleanReplyText($text);
                    if ($reviewerName !== '') {
                        $text = $reviewerName . "様\n\n" . $text;
                    }
                    return $text;
                }
                return null;
            }

            Log::error('Gemini API error (reply)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (reply)', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 投稿のブロック②（お客様エピソード）をAI生成
     */
    public function generatePurchaseEpisode(array $params, ?Store $store = null): ?string
    {
        // 月間利用上限チェック
        $limitError = $this->checkMonthlyLimit();
        if ($limitError) {
            return $limitError;
        }
        $this->logUsage('episode', $store?->id ?? null);

        // UTF-8不正文字を除去
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        $productName = $params['product_name'] ?? '';
        $brandName = $params['brand_name'] ?? '';
        $category = $params['category'] ?? '';
        $customerGender = $params['customer_gender'] ?? '';
        $customerAge = $params['customer_age'] ?? '';
        $customerReason = $params['customer_reason'] ?? '';
        $productCondition = $params['product_condition'] ?? '';
        $accessories = $params['accessories'] ?? '';

        $customerInfo = '';
        if ($customerGender) $customerInfo .= "性別：{$customerGender}、";
        if ($customerReason) $customerInfo .= "ご利用の経緯：{$customerReason}、";
        $customerInfo = $customerInfo ? rtrim($customerInfo, '、') : '情報なし';

        // 業種に応じたプロンプトを組み立て
        $storeDescription = ($store && $store->ai_store_description) ? $store->ai_store_description : ($store ? $store->name . ' のスタッフ' : '店舗のスタッフ');
        $bt = $store?->businessType;
        $baseContext = $bt->base_context ?? 'お店でのサービス体験';
        $actionWord = $bt->post_action_word ?? 'お取り扱い';
        $hiddenFields = $bt->post_hidden_fields ?? [];

        // brand/productが非表示の業種（飲食店等）はカテゴリベースのプロンプト
        $hasBrandProduct = !in_array('brand_name', $hiddenFields) && ($brandName || $productName);

        // 買取店向けとその他業種で条件を分岐
        $isPawn = ($actionWord === 'お買取り');

        if ($hasBrandProduct) {
            // 買取・商品系のプロンプト
            $conditionInfo = $productCondition ?: '特記なし';
            $accessoriesInfo = $accessories ?: 'なし';
            $episodeType = $isPawn ? '買取エピソード' : 'お客様エピソード';
            $closingLine = $isPawn
                ? '今後もご不要なお品物がございましたら、ぜひお気軽にご相談ください。で締める'
                : '今後もお気軽にご利用ください。で締める';
            $contextLine = $isPawn
                ? "お客様から買取した馴れ初めを500文字程度で作成してください。"
                : "お客様とのやり取りのエピソードを500文字程度で作成してください。";
            $marketLine = $isPawn
                ? "- 中古市場での人気や需要にも軽く触れる\n"
                : "- この商品・サービスの魅力や人気にも軽く触れる\n";
            $satisfactionLine = $isPawn
                ? "- 査定額に満足いただけた旨を含める（具体的な金額は書かない）\n"
                : "- お客様にご満足いただけた旨を含める\n";

            $prompt = "あなたは{$storeDescription}として、Googleビジネスプロフィールに投稿する{$episodeType}の本文を作成してください。\n\n"
                . "{$contextLine}\n\n"
                . "■商品名：{$brandName} {$productName}\n"
                . "■お客様：{$customerInfo}\n"
                . "■商品の状態：{$conditionInfo}\n"
                . "■付属品・備考：{$accessoriesInfo}\n\n"
                . "【条件】\n"
                . "- 400〜500文字程度でしっかりとしたエピソードにする\n"
                . "- 丁寧な敬語（です・ます調）で統一\n"
                . "- お客様がご来店された経緯や背景を含める\n"
                . "- 商品の状態や特徴、魅力に触れる\n"
                . $marketLine
                . $satisfactionLine
                . "- {$closingLine}\n"
                . "- お客様の年齢は文章に含めない（「○○代の」などの表現は使わない）\n"
                . "- マークダウン記法は使わない\n"
                . "- 説明文・前置き不要。エピソード本文のみ出力";
        } else {
            // 飲食店など商品名不要の業種（カテゴリベース）
            $prompt = "あなたは{$storeDescription}として、Googleビジネスプロフィールに投稿するお店の紹介投稿の本文を作成してください。\n\n"
                . "「{$category}」カテゴリのメニュー・サービスについて魅力的に紹介してください。\n\n"
                . "■カテゴリ：{$category}\n"
                . "■お客様情報：{$customerInfo}\n\n"
                . "【条件】\n"
                . "- 400〜500文字程度でしっかりとした紹介文にする\n"
                . "- 丁寧な敬語（です・ます調）で統一\n"
                . "- このカテゴリのメニューの魅力やこだわりを具体的に紹介する\n"
                . "- お客様に楽しんでいただいた様子や反応を交える\n"
                . "- 「ぜひお気軽にご来店ください」のような温かい締めで終わる\n"
                . "- お客様の年齢は文章に含めない\n"
                . "- マークダウン記法は使わない\n"
                . "- 説明文・前置き不要。紹介文本文のみ出力";
        }

        try {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'maxOutputTokens' => 1500,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ];

            $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($jsonBody === false) {
                Log::error('Gemini: json_encode failed', ['error' => json_last_error_msg()]);
                return null;
            }

            $response = Http::withBody($jsonBody, 'application/json')
                ->post($this->apiUrl . '?key=' . $this->apiKey);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                return $text ? $this->cleanReplyText($text) : null;
            }

            Log::error('Gemini API error (purchase episode)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (purchase episode)', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ブロック③（店舗別エリアフッター）のテンプレートをAI生成
     */
    public function generateStoreFooterTemplate(Store $store, string $area): ?string
    {
        // 月間利用上限チェック
        $limitError = $this->checkMonthlyLimit();
        if ($limitError) {
            return $limitError;
        }
        $this->logUsage('footer', $store->id);

        $storeName = $store->name;
        $bt = $store->businessType;
        $baseContext = $bt ? $bt->base_context : 'お店のサービスや体験';
        $storeDesc = $store->ai_store_description ?? $storeName;

        $prompt = <<<EOT
以下の条件でGoogleビジネスプロフィール投稿用のエリアフッターテンプレートを1つ作成してください。

【店舗名】{$storeName}
【店舗の紹介】{$storeDesc}
【業種】{$baseContext}
【エリア】{$area}

【フォーマット】
「{エリア}で○○のご利用をご検討の方は、{店舗名}へぜひご相談ください。」
※ 業種（{$baseContext}）に合った自然な表現にしてください

※ ○○の部分は「○○」のまま残してください（商品カテゴリを後から入れるためのプレースホルダーです）
※ 1文のみ出力してください
※ 説明文・前置き不要。テンプレート文のみ出力
EOT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.5,
                    'maxOutputTokens' => 300,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                return $text ? trim($text) : null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (footer template)', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * 日本語の氏名からAIで性別を推定する
     * @return string|null '男性', '女性', or null（判定不能）
     */
    public function estimateGender(string $name): ?string
    {
        $prompt = <<<EOT
以下の日本語の氏名から、性別を推定してください。

【氏名】
{$name}

【ルール】
- 名前（下の名前）から判断してください
- 男性の場合は「男性」とだけ出力
- 女性の場合は「女性」とだけ出力
- 判断が難しい場合（中性的な名前、読みが不明など）は「不明」とだけ出力
- 余計な説明は不要です。「男性」「女性」「不明」のいずれかのみ出力してください
EOT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

                if (in_array($text, ['男性', '女性'])) {
                    return $text;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (gender estimation)', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * featureテキストからブランド名・商品名（型番込み）を抽出する
     * 例: "LV パピヨン30 M51385 SP0083 ブラウン モノグラム ショルダーバッグ レディース 付属品なし 中古"
     *   → ["brand_name" => "ルイヴィトン", "product_name" => "パピヨン30 M51385"]
     */
    public function extractProductInfo(string $feature): ?array
    {
        $prompt = <<<EOT
以下は商品の特徴テキストです。「ブランド名（メーカー名）」と「商品名」を抽出してJSON形式で出力してください。

【テキスト】
{$feature}

【ルール】
■ブランド名(brand_name):
- テキストに英語表記と日本語表記の両方がある場合は両方含める（例: COACH コーチ→COACH コーチ、ソニー SONY→ソニー SONY）
- テキストに略称しかない場合は正式名称に変換（例: LV→ルイヴィトン）
- テキストに片方の表記しかない場合はそのまま出力（例: Apple→Apple、シャネル→シャネル）
- メーカー名もブランド名として扱う（例: 高木酒造→高木酒造）
- ブランド/メーカーが不明な場合はnull

■商品名(product_name):
- モデル名・シリーズ名 + 型番を含める
- ブランド名・メーカー名は含めない
- 色、素材名、性別（メンズ/レディース）は含めない
- 「中古」「新品」「付属品なし」「動作確認済」「箱付属」等の状態・付属品情報は含めない
- サイズ・容量があれば含める

■具体例:
- 「LV パピヨン30 M51385 ブラウン モノグラム ショルダーバッグ」→ {"brand_name":"ルイヴィトン","product_name":"パピヨン30 M51385"}
- 「COACH コーチ ショルダーバッグ F58328 ベージュ PVCレザー」→ {"brand_name":"COACH コーチ","product_name":"ショルダーバッグ F58328"}
- 「ROLEX デイトジャスト 16233 SS×YG」→ {"brand_name":"ROLEX ロレックス","product_name":"デイトジャスト 16233"}
- 「ソニー SONY コンパクトデジタルカメラ DSC-HX60V ボディのみ」→ {"brand_name":"ソニー SONY","product_name":"コンパクトデジタルカメラ DSC-HX60V"}
- 「日本酒 十四代 EXTRA 播州白鶴錦 純米大吟醸 720ml」→ {"brand_name":"高木酒造","product_name":"十四代 EXTRA 播州白鶴錦 純米大吟醸 720ml"}
- 「Apple Watch SE 3 40mm MEH54J/A GPSモデル」→ {"brand_name":"Apple","product_name":"Apple Watch SE 3 40mm MEH54J/A"}

■出力形式:
JSONのみ出力。余計な説明やマークダウン記法は不要です。
EOT;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 200,
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

                // ```json ... ``` のマークダウンを除去
                $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
                $text = preg_replace('/\s*```$/', '', $text);

                $parsed = json_decode($text, true);
                if (is_array($parsed)) {
                    return [
                        'brand_name'   => $parsed['brand_name'] ?? null,
                        'product_name' => $parsed['product_name'] ?? null,
                    ];
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception (product info extraction)', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
