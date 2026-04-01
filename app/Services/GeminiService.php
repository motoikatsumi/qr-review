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
     *
     * @param string $storeName
     * @param string|array $keywords
     * @param string $gender
     * @param string $age
     * @param string $visitType
     * @return string|null
     */
    public function generateSuggestion(string $storeName, $keywords, string $gender = '', string $age = '', string $visitType = ''): ?string
    {
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
            'さっぱりとした爽やかな口調',
            'ちょっと驚いた感じの口調',
            '穏やかで温かみのある口調',
            '実用的で要点を押さえた口調',
            'さりげなく好印象を伝える口調',
            '少し照れながらも嬉しそうな口調',
            '落ち着いていて信頼感のある口調',
            '前向きで気持ちの良い口調',
        ];

        // 文章の切り口（ランダム）
        $focuses = [
            'お店の雰囲気や居心地も交えて',
            '結果（査定額や対応）を中心に',
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
            '久しぶりに利用して改めて良さを実感したタイプ',
            '家族に勧められて来店したタイプ',
            '口コミを見て来店を決めたタイプ',
            '通りがかりにふらっと入ってみたタイプ',
            '買い替え資金のために持ち込んだタイプ',
            '思い出の品を手放す決心をしたタイプ',
            'たまたま近くに用事があって立ち寄ったタイプ',
            '他の人の参考になればと思って書いているタイプ',
            '質屋に対する先入観が変わったタイプ',
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
- 【重要】性別に関係なく、女性語・お嬢様言葉は絶対に使わない。「〜わ」「〜のよ」「〜の」「〜かしら」などの女性的な語尾は禁止。男女とも同じ丁寧語（です/ます）で統一する
- 【重要】お客様自身を下げるような発言（卑下など）や、不快感を与えるネガティブ・おかしい文章は絶対に含めない
{$avoidFirstTime}{$multiThemeInstruction}- 絵文字・説明文・前置き不要。口コミ文のみ出力

【NGワード・禁止表現】以下の内容は絶対に含めないこと：
- 具体的な金額や査定額（例：「○○円」「○万円」）
- 他店名や競合店の名前
- スタッフの個人名・フルネーム
- 個人情報（住所、電話番号、メールアドレスなど）
- 「最高額」「業界一」「日本一」などの誇大表現
- 【最重要】女性語・役割語は一切禁止。語尾に「わ」「のよ」「の」「かしら」を付ける表現は性別問わず全て禁止（例：「〜たわ」「〜だわ」「〜るわ」「〜ですわ」「〜ましたわ」「〜できるの」「〜のよね」「〜のよ」「〜かしら」「〜ますのよ」「〜でございますわ」）。文末が「〜の。」「〜の！」で終わるのも禁止
- 不自然な語尾延ばし（例：「〜ねぇ」「〜ねー」「〜よぉ」「〜なぁ」「〜かなぁ」「〜とはねぇ」）。年配者風の口調にしないこと
- 家族の死や不幸に関する表現（例：「亡くなった」「亡き」「他界」「遺品」「形見」「故人」「供養」）。ユーザーの実体験と異なる可能性があるため絶対に使わない
- 借金・生活苦を連想させる表現（例：「借金」「返済」「生活費」「お金に困って」「金欠」）
- 離婚・別れに関する表現（例：「離婚」「元妻」「元夫」「別れた」「元カノ」「元カレ」）
- 病気・入院に関する表現（例：「病気」「入院」「手術費」「治療費」「闘病」）
- 具体的なブランド名・品名（例：「ロレックス」「ルイヴィトン」「シャネル」「エルメス」）。持ち込んだ品と異なる可能性があるため
- 盗品や不正を連想させる表現（例：「もらったもの」「拾った」「見つけた」）
- ギャンブル関連の表現（例：「パチンコ」「競馬」「スロット」「賭け」）
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
     * Google口コミへの返信文をAIで生成（MEO対策ワード入り）
     */
    public function generateReplyComment(
        string $storeName,
        int $rating,
        string $reviewComment,
        string $category,
        array $keywords,
        string $reviewerName = '',
        string $customerType = 'new'
    ): ?string {
        $keywordList = implode('、', $keywords);
        $hasKeywords = !empty($keywords) && $keywordList !== '';

        // 返信構成パターン（バリエーションでテンプレ感を回避）
        $structurePatterns = [
            [
                '1. お客様への感謝の挨拶（ご来店・評価への感謝を丁寧に。リピーターと分かる場合は「この度も」などで触れる）',
                '2. 口コミ内容への具体的な言及（売った品物・褒められたポイントを口コミから抽出して必ず含める。口コミが空の場合はご利用への感謝を丁寧に述べる）',
                '3. 取扱商品のキーワード紹介（「当店では○○をはじめ、△△、□□など幅広いお品物の査定・買取を行っております。」のように自然に。指定キーワードをできるだけ多く含める）',
                '4. 締めの言葉（またのご来店を促す。店舗名を含める）',
            ],
            [
                '1. お客様への感謝と口コミ内容への具体的な言及を一体化（感謝を述べつつ、売った品物や褒められたポイントに触れる）',
                '2. 当店のこだわりや姿勢を{$category}の買取に絡めて紹介',
                '3. 取扱商品のキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                '4. 締めの言葉（今後のご利用を促す。店舗名を含める）',
            ],
            [
                '1. お客様への感謝の挨拶（ご来店・評価への感謝を丁寧に）',
                '2. 口コミ内容に触れつつ、{$category}の買取・査定における当店の強みを紹介',
                '3. その他の取扱商品のキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                '4. 再来店・紹介の促進メッセージ（店舗名を含める）',
            ],
            [
                '1. お客様の口コミ内容に触れながら感謝を伝える（印象的なフレーズがあれば引用）',
                '2. {$category}の買取について当店が大切にしていることを紹介',
                '3. 取扱商品のキーワード紹介（指定キーワードをできるだけ多く自然に含める）',
                '4. 締めの言葉（地域密着の姿勢に触れつつ、またのご来店を促す。店舗名を含める）',
            ],
        ];

        $selectedStructure = $structurePatterns[array_rand($structurePatterns)];
        // 単一引用符内の {$category} プレースホルダーを実際の値に置換
        $selectedStructure = array_map(function ($line) use ($category) {
            return str_replace('{$category}', $category, $line);
        }, $selectedStructure);
        $structureText = implode("\n", $selectedStructure);

        // 顧客タイプの指示
        if ($customerType === 'repeater') {
            $customerTypeInstruction = '- お客様はリピーターです。「いつもご利用いただき」「この度も」「日頃より」など、リピーターであることが伝わる表現を冒頭に含める';
        } elseif ($customerType === 'unknown') {
            $customerTypeInstruction = '- お客様が新規かリピーターか不明です。「この度は」「ご来店いただき」など、どちらにも当てはまる自然な表現を使う。初来店・リピーターのどちらかを決めつけない';
        } else {
            $customerTypeInstruction = '- お客様は新規のお客様です。「この度は初めてのご来店」「初めてご利用いただき」など、初来店であることに触れる表現を冒頭に含める';
        }

        // エリアキーワード（店舗名から自動判定）
        $storeAreaMap = [
            '西千石' => [
                '「鹿児島市」「西千石町」という地名を自然に1回ずつ混ぜる',
                '「鹿児島市」「天文館」という地名を自然に1回ずつ混ぜる',
                '「鹿児島」「西千石」という地名を自然に1回ずつ混ぜる',
                '「鹿児島市」という地名と「天文館エリア」という表現を自然に1回ずつ混ぜる',
                '「鹿児島市西千石」「中央駅周辺」という地名を自然に1回ずつ混ぜる',
            ],
            '宇宿' => [
                '「鹿児島市」「宇宿」という地名を自然に1回ずつ混ぜる',
                '「鹿児島市宇宿」「谷山エリア」という地名を自然に1回ずつ混ぜる',
                '「鹿児島」「宇宿・谷山」という地名を自然に1回ずつ混ぜる',
                '「鹿児島市」という地名と「宇宿エリア」という表現を自然に1回ずつ混ぜる',
            ],
            '伊敷' => [
                '「鹿児島市」「伊敷」という地名を自然に1回ずつ混ぜる',
                '「鹿児島市伊敷」「草牟田」という地名を自然に1回ずつ混ぜる',
                '「鹿児島」「伊敷・下伊敷」という地名を自然に1回ずつ混ぜる',
                '「鹿児島市」という地名と「伊敷・吉野エリア」という表現を自然に1回ずつ混ぜる',
            ],
            '鹿屋' => [
                '「鹿屋市」「寿」という地名を自然に1回ずつ混ぜる',
                '「鹿屋市」「札元」という地名を自然に1回ずつ混ぜる',
                '「鹿屋」「寿・札元・川西エリア」という地名を自然に1回ずつ混ぜる',
                '「鹿屋市」という地名と「川西エリア」という表現を自然に1回ずつ混ぜる',
            ],
            '国分' => [
                '「霧島市」「国分」という地名を自然に1回ずつ混ぜる',
                '「霧島市国分」「隼人」という地名を自然に1回ずつ混ぜる',
                '「霧島」「国分・隼人エリア」という地名を自然に1回ずつ混ぜる',
                '「霧島市」という地名と「国分エリア」という表現を自然に1回ずつ混ぜる',
            ],
        ];

        $areaKeywords = $storeAreaMap['西千石']; // デフォルト
        foreach ($storeAreaMap as $key => $areas) {
            if (str_contains($storeName, $key)) {
                $areaKeywords = $areas;
                break;
            }
        }
        $areaInstruction = $areaKeywords[array_rand($areaKeywords)];

        // サービスキーワード（ランダムに追加で1つ混ぜる指示）
        $serviceKeywords = [
            '高価買取',
            '買取・査定',
            '査定・鑑定',
            '質預かり・買取',
            '無料査定',
        ];
        $serviceKw = $serviceKeywords[array_rand($serviceKeywords)];

        $prompt = <<<EOT
あなたはGoogleマップに掲載されている質屋・買取店のオーナーとして、お客様の口コミに返信します。
MEO（マップエンジン最適化）対策として、指定された取扱商品のキーワードと地域名を返信文に自然に織り込んでください。

【店舗名】{$storeName}
【口コミの星評価】{$rating}つ星
【口コミ内容】{$reviewComment}
【お客様が利用されたカテゴリ】{$category}
【返信に含めたい取扱商品キーワード】{$keywordList}

【返信の構成】
以下の構成で返信してください：
{$structureText}

【顧客タイプ】
{$customerTypeInstruction}

【MEO対策条件】
- {$areaInstruction}
- 「{$serviceKw}」というワードを自然に1回混ぜる
- 店舗名「{$storeName}」を返信文中に必ず1回含める
EOT;
        if ($hasKeywords) {
            $prompt .= "\n- 指定された取扱商品キーワードをできるだけ多く自然な文脈で含める";
            if ($category !== '') {
                $prompt .= "\n- 「{$category}」に関連する表現を口コミへの言及で自然に使う";
            }
        } else {
            $prompt .= "\n- 取扱商品キーワードが未指定のため、口コミ内容に即した一般的な返信にする。ただし店舗が質屋・買取店であることは自然に伝える";
        }
        $prompt .= <<<EOT

【文体・フォーマット条件】
- 丁寧で格式のあるオーナー返信にする（です・ます調、敬語）
- 300〜500文字程度のしっかりした返信にする
- 各パートの区切りで改行を入れて読みやすくする。ただし空行（空白行）は入れず、改行のみで区切る
- 口コミ内容が空（星評価のみ）の場合は、ご来店と評価への感謝を丁寧に述べ、取扱商品の紹介を含める。「どのようなお品物かは存じませんが」のような知らないことを示す表現は絶対に使わない。口コミが空でも、お品物をお持ち込みいただいたことへの感謝やご利用いただいた体験への感謝を自然に述べる
- 低評価（1〜3つ星）の場合は、お詫びと改善への姿勢を示しつつ、取扱商品を紹介する
- 高評価（4〜5つ星）の場合は、感謝を込めてまたのご来店を促す
- 「」（カギ括弧）は使わない
- **（アスタリスク）などのマークダウン記法は一切使わない。プレーンテキストのみで出力する
- 絵文字は使わない
- 説明文・前置き不要。返信文のみ出力
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
     * 買取投稿のブロック②（お客様エピソード）をAI生成
     */
    public function generatePurchaseEpisode(array $params): ?string
    {
        // UTF-8不正文字を除去
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        $productName = $params['product_name'] ?? '';
        $brandName = $params['brand_name'] ?? '';
        $customerGender = $params['customer_gender'] ?? '';
        $customerAge = $params['customer_age'] ?? '';
        $customerReason = $params['customer_reason'] ?? '';
        $productCondition = $params['product_condition'] ?? '';
        $accessories = $params['accessories'] ?? '';

        $customerInfo = '';
        if ($customerGender) $customerInfo .= "性別：{$customerGender}、";
        if ($customerReason) $customerInfo .= "売却理由：{$customerReason}、";
        $customerInfo = $customerInfo ? rtrim($customerInfo, '、') : '情報なし';

        $conditionInfo = $productCondition ?: '特記なし';
        $accessoriesInfo = $accessories ?: 'なし';

        $prompt = "あなたは質屋・買取店 質屋アシスト のスタッフとして、Googleビジネスプロフィールに投稿する買取エピソードの本文を作成してください。\n\n"
            . "お客様から買取した馴れ初めを200〜300文字程度で簡潔に作成してください。\n\n"
            . "■商品名：{$brandName} {$productName}\n"
            . "■お客様：{$customerInfo}\n"
            . "■商品の状態：{$conditionInfo}\n"
            . "■付属品：{$accessoriesInfo}\n\n"
            . "【条件】\n"
            . "- 200〜300文字程度で簡潔にまとめる\n"
            . "- 丁寧な敬語（です・ます調）で統一\n"
            . "- お客様が商品を持ち込まれた経緯を簡潔に含める\n"
            . "- 商品の状態や特徴に軽く触れる\n"
            . "- 査定額に満足いただけた旨を含める（具体的な金額は書かない）\n"
            . "- 今後もご不要なお品物がございましたら、ぜひお気軽にご相談ください。で締める\n"
            . "- お客様の年齢は文章に含めない（「○○代の」などの表現は使わない）\n"
            . "- マークダウン記法は使わない\n"
            . "- 説明文・前置き不要。エピソード本文のみ出力";

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
    public function generateStoreFooterTemplate(string $storeName, string $area): ?string
    {
        $prompt = <<<EOT
以下の条件でGoogleビジネスプロフィール投稿用のエリアフッターテンプレートを1つ作成してください。

【店舗名】{$storeName}
【エリア】{$area}

【フォーマット】
「{エリア}で○○の売却や質預かりをご検討の方は、高価買取の{店舗名}へぜひご相談ください。LINE査定も受付中です。」

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
}
