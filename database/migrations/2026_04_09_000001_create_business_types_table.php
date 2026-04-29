<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // 表示名（例: 質屋, 焼肉店, 車販売）
            $table->string('slug')->unique();                // 内部識別子（例: pawn, yakiniku, car_dealer）
            $table->text('base_context');                    // AIプロンプトの業種説明文
            $table->json('focus_presets');                   // 文章の切り口リスト
            $table->json('style_presets');                   // 書き手スタイルリスト
            $table->json('ng_words');                        // 業種ごとの禁止ワードリスト
            $table->json('visit_type_options');              // 来店タイプ選択肢
            $table->boolean('use_pawn_system')->default(false);    // 質屋在庫連携を使うか
            $table->boolean('use_purchase_posts')->default(false); // 買取投稿機能を使うか
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('business_types');
    }
};
