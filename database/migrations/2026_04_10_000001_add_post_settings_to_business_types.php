<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_types', function (Blueprint $table) {
            // 投稿カテゴリ一覧 [{name, wp_slug, wp_path}, ...]
            $table->json('post_categories')->nullable()->after('use_purchase_posts');
            // ブロック①テンプレート（{brand},{product},{status},{category} のプレースホルダー使用）
            $table->text('post_title_template')->nullable()->after('post_categories');
            // 公開タイトルに使うアクション語（例：お買取り、ご提供、お取り扱い）
            $table->string('post_action_word', 50)->nullable()->after('post_title_template');
            // 商品ランク（S/A/B/C/D）を使用するか
            $table->boolean('use_product_rank')->default(false)->after('post_action_word');
            // 商品状態の選択肢 ["中古品","新品","未使用品"]
            $table->json('post_status_options')->nullable()->after('use_product_rank');
            // お客様の利用理由プリセット
            $table->json('post_reason_presets')->nullable()->after('post_status_options');
            // 付属品プリセット
            $table->json('post_accessory_presets')->nullable()->after('post_reason_presets');
        });
    }

    public function down()
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn([
                'post_categories',
                'post_title_template',
                'post_action_word',
                'use_product_rank',
                'post_status_options',
                'post_reason_presets',
                'post_accessory_presets',
            ]);
        });
    }
};
