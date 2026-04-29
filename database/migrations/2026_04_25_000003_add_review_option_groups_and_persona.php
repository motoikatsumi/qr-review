<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 業種ごとに最大 5 個まで質問項目（ラベル・選択肢・表示 on/off）を
     * 自由に組めるように JSON カラムを追加。
     * review 側にも全回答を保存する persona JSON を追加（後方互換のため既存 gender/age/visit_type/item カラムは残す）。
     */
    public function up(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->json('review_option_groups')->nullable()->after('review_item_options');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->json('persona')->nullable()->after('item');
        });
    }

    public function down(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn('review_option_groups');
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('persona');
        });
    }
};
