<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\PurchasePostController;
use App\Models\PurchasePost;
use App\Models\Store;
use ReflectionClass;
use Tests\TestCase;

/**
 * 投稿の自動ハッシュタグ生成 (PurchasePostController::generateHashtags) の回帰防止テスト。
 *
 * 過去の障害:
 *   d291853 で multi-tenant 化した時に brand/product/category の自動付与が失われ、
 *   custom_hashtags があるとそれだけが使われるようになっていた。
 */
class HashtagGenerationTest extends TestCase
{
    private function callGenerate(PurchasePost $post): string
    {
        $ctrl = new PurchasePostController();
        $ref = new ReflectionClass($ctrl);
        $m = $ref->getMethod('generateHashtags');
        $m->setAccessible(true);
        return $m->invoke($ctrl, $post);
    }

    public function test_brand_and_product_and_category_are_always_added(): void
    {
        $post = new PurchasePost();
        $post->category = 'カメラ・レンズ';
        $post->brand_name = 'コンタックス';
        $post->product_name = 'TLA200ストロボ';
        $post->custom_hashtags = "買取\n高価買取";

        $tags = $this->callGenerate($post);

        $this->assertStringContainsString('#買取', $tags);
        $this->assertStringContainsString('#高価買取', $tags);
        $this->assertStringContainsString('#カメラレンズ', $tags, 'カテゴリのハッシュタグが付与されていない');
        $this->assertStringContainsString('#カメラレンズ買取', $tags, 'カテゴリ+「買取」セットが付与されていない');
        $this->assertStringContainsString('#コンタックス', $tags, 'ブランド名のハッシュタグが付与されていない');
        $this->assertStringContainsString('#コンタックス買取', $tags, 'ブランド+「買取」セットが付与されていない');
        $this->assertStringContainsString('#TLA200ストロボ', $tags, '商品名(型番込み)のハッシュタグが付与されていない');
    }

    public function test_no_duplicates_when_brand_overlaps_custom_hashtags(): void
    {
        $post = new PurchasePost();
        $post->category = '時計';
        $post->brand_name = 'ロレックス';
        $post->custom_hashtags = "ロレックス\nロレックス買取";

        $tags = $this->callGenerate($post);

        // 同じハッシュタグが2回出ないこと
        $this->assertEquals(
            substr_count($tags, '#ロレックス '),
            substr_count(' ' . $tags . ' ', ' #ロレックス '),
            '重複除去が機能していない'
        );
    }

    public function test_empty_post_falls_back_to_store_name(): void
    {
        $store = new Store();
        $store->name = 'テスト店舗';

        $post = new PurchasePost();
        $post->setRelation('store', $store);

        $tags = $this->callGenerate($post);

        $this->assertStringContainsString('#テスト店舗', $tags, '空ポストで店舗名フォールバックが効いていない');
    }
}
