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
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    }

    /**
     * 顧客の評価とコメントからGoogleマップ用の口コミ文を生成
     */
    public function generateReviewText(string $storeName, int $rating, string $comment): ?string
    {
        $starText = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

        $prompt = <<<EOT
あなたはGoogleマップの口コミを代筆するアシスタントです。
以下の情報を元に、Googleマップに投稿する自然で丁寧な口コミ文を日本語で生成してください。

【店舗名】{$storeName}
【評価】{$starText}（{$rating}星）
【お客様のコメント】{$comment}

ルール：
- 100〜200文字程度で簡潔にまとめる
- お客様のコメントの内容を自然に反映する
- 実際に来店した人が書いたような自然な文体にする
- 絵文字は使わない
- 口コミ文のみを出力し、余計な説明は不要
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
                    'maxOutputTokens' => 500,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * キーワードボタンからGoogleマップ用の口コミ提案文を生成
     */
    public function generateSuggestion(string $storeName, string $keyword): ?string
    {
        $randomSeed = rand(1, 9999);

        $prompt = <<<EOT
あなたはGoogleマップの口コミを代筆するアシスタントです。
以下の情報を元に、Googleマップに投稿する自然で丁寧な口コミ文を日本語で生成してください。

【店舗名】{$storeName}
【お客様が伝えたいこと】{$keyword}
【シード番号】{$randomSeed}

ルール：
- 100〜200文字程度で簡潔にまとめる
- 質屋に実際に来店した人が書いたような自然な文体にする
- 毎回少し異なる表現・文章構成にする（シード番号を参考に）
- 絵文字は使わない
- 口コミ文のみを出力し、余計な説明は不要
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
                    'temperature' => 0.9,
                    'maxOutputTokens' => 500,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
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
}
