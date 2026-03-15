<?php

namespace App\Services;

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
     * 送信された口コミ文がGoogleマップのポリシーに違反していないかチェックする
     * 違反がある場合はエラーメッセージを返し、問題なければnullを返す
     */
    public function validateGooglePolicy(string $comment): ?string
    {
        $prompt = <<<EOT
あなたはGoogleマップの口コミポリシー審査員です。
以下の口コミ文が、Googleマップの「禁止および制限されているコンテンツ」ポリシーに違反しているかどうかを審査してください。

【対象の口コミ文】
{$comment}

【審査基準となる主なポリシー違反】
1. スパムと虚偽のエンゲージメント（意味のない文字列、URLのみなど）
2. 関連性のないコンテンツ（質屋のサービスや体験と無関係な政治的・個人的な主張など）
3. 不適切なコンテンツ（ヘイトスピーチ、嫌がらせ、個人攻撃、卑猥な言葉、差別的な発言など）
4. 露骨な性的表現
5. 冒涜的な言葉

判定ルール：
- もし上記ポリシーのいずれかに【明確に違反している】場合は、その理由を日本語で簡潔に指摘してください（例：「不適切な言葉が含まれているため投稿できません」など）。
- 違反しておらず、通常の口コミとして【問題ない】場合は、必ず「OK」という2文字だけを出力してください。
- 多少の誤字脱字やネガティブな意見（例えば「買取価格が安かった」など）はポリシー違反ではないため「OK」としてください。
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
     */
    public function generateSuggestion(string $storeName, string $keyword, string $gender = '', string $age = ''): ?string
    {
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

        // 文章のトーン（ランダム）
        $tones = [
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
        ];

        // 文章の切り口（ランダム）
        $focuses = [
            'お店の雰囲気や居心地も交えて',
            '結果（査定額や対応）を中心に',
            '来店前の不安が解消されたことを中心に',
            'また来たいという気持ちを中心に',
            'スタッフへの感謝を中心に',
            '初めてでも安心できたことを中心に',
            '対応のスピード感を中心に',
            '説明の丁寧さ・わかりやすさを中心に',
            '店内の清潔感や印象を交えて',
            '思っていたより良かった驚きを中心に',
            '気軽に相談できる雰囲気を中心に',
            '接客の距離感がちょうど良かったことを中心に',
        ];

        // 書き手のスタイル（ランダム）
        $styles = [
            '絵文字を適度に使う親しみやすいタイプ',
            '初めてこの店を利用した新規のお客様',
            '何度も利用しているリピーターのお客様',
            '家族や友人に紹介したいと思っているタイプ',
            'ネットで他店と比較してから来店した慎重なタイプ',
            '普段あまり口コミを書かない素朴なタイプ',
            '短文でサクッと書くタイプ',
            '感謝の気持ちをしっかり伝えたいタイプ',
            '仕事の合間にサッと立ち寄った忙しいタイプ',
            '引っ越しや大掃除をきっかけに来店したタイプ',
            '遺品や形見の整理で来店した丁寧なタイプ',
            '初めての質屋体験で緊張していたタイプ',
        ];

        $tone  = $tones[array_rand($tones)];
        $focus = $focuses[array_rand($focuses)];
        $style = $styles[array_rand($styles)];

        // 文字数パターン（ランダム・重み付き）
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
        $lengthInstruction = $lengthPatterns[array_rand($lengthPatterns)];

        $prompt = <<<EOT
Googleマップに投稿する口コミ文を1つ生成してください。

【店舗名】{$storeName}
【テーマ】{$keyword}
【書き手のペルソナ】{$persona}
【書き手のスタイル】{$style}
【文章のトーン】{$tone}
【文章の切り口】{$focus}

条件：
- 【最重要】{$lengthInstruction}
- 最後は「。」「！」「絵文字」のいずれかで終わる。絵文字で終わる場合は「。」や「！」は付けない。たまに句読点も絵文字もなく言い切りで終わってもよい（例：「また来たいです」）。「。！」「！。」のような句読を連続で使わない
- 「」（カギ括弧）は一切使わない
- 指定されたペルソナ・スタイル・トーン・切り口を反映した自然な口語体にする
- 【重要】フランクになりすぎず、最低限の敬語や丁寧な言葉遣い（です/ます調）を守る
- 【重要】お客様自身を下げるような発言（卑下など）や、不快感を与えるネガティブ・おかしい文章は絶対に含めない
- 絵文字・説明文・前置き不要。口コミ文のみ出力

【NGワード・禁止表現】以下の内容は絶対に含めないこと：
- 具体的な金額や査定額（例：「○○円」「○万円」）
- 他店名や競合店の名前
- スタッフの個人名・フルネーム
- 個人情報（住所、電話番号、メールアドレスなど）
- 「最高額」「業界一」「日本一」などの誇大表現
- 不自然な役割語や方言的語尾（例：「〜ましたわ」「〜ですわ」「〜ますのよ」「〜でございますわ」「〜だわ」「〜るわ」「〜のよね」「〜のよ」「〜かしら」）
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

        return trim($text);
    }
}
