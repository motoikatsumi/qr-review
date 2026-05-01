<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Services\GeminiService;
use Tests\TestCase;

/**
 * AI口コミ生成で店舗名が文中に出力されないことを保証する回帰防止テスト。
 *
 * 過去の問題:
 *   プロンプトに「【店舗名】」を含めていたため、Geminiが「○○店は」のように
 *   店舗名を文中に書いてしまうケースがあった(Googleポリシー的にも不自然)。
 *
 * このテストは実際にGemini APIを呼び出すため、ネットワーク接続とAPIキーが必要。
 * APIエラー/上限超過時はスキップ。
 */
class SuggestionDoesNotContainStoreNameTest extends TestCase
{
    public function test_high_rating_suggestion_does_not_include_store_name(): void
    {
        $store = Store::where('name', 'like', '質屋%')->first();
        if (!$store) {
            $this->markTestSkipped('テスト用の質屋店舗が存在しない');
        }

        $gemini = new GeminiService();
        $text = $gemini->generateSuggestion($store, ['対応が良かった'], '男性', '40', '新規', '', [], 5);

        if (!$text) {
            $this->markTestSkipped('AI 生成に失敗(API上限/ネットワーク)');
        }

        $this->assertGeneratedTextDoesNotContainStoreName($text, $store->name);
    }

    public function test_low_rating_suggestion_does_not_include_store_name(): void
    {
        $store = Store::where('name', 'like', '質屋%')->first();
        if (!$store) {
            $this->markTestSkipped('テスト用の質屋店舗が存在しない');
        }

        $gemini = new GeminiService();
        $text = $gemini->generateSuggestion(
            $store,
            ['査定額に納得できなかった点'],
            '男性', '40', '新規', '', [], 2
        );

        if (!$text) {
            $this->markTestSkipped('AI 生成に失敗(API上限/ネットワーク)');
        }

        $this->assertGeneratedTextDoesNotContainStoreName($text, $store->name);
    }

    private function assertGeneratedTextDoesNotContainStoreName(string $text, string $storeName): void
    {
        // フルネーム: 例「質屋アシスト宇宿店」
        $this->assertStringNotContainsString($storeName, $text,
            "生成文に店舗名「{$storeName}」が含まれている");

        // 店舗名末尾の「○○店」パターン
        // 例 "質屋アシスト宇宿店" → 「宇宿店」
        if (preg_match('/([^\s]+)店$/u', $storeName, $m)) {
            $shortStore = $m[1] . '店';
            $this->assertStringNotContainsString($shortStore, $text,
                "生成文に店舗の短縮名「{$shortStore}」が含まれている");
        }

        // ブランド名共通部分の「質屋アシスト」
        $this->assertStringNotContainsString('質屋アシスト', $text,
            '生成文にブランド名「質屋アシスト」が含まれている');
    }
}
