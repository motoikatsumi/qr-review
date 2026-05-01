<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->json('reply_category_groups')->nullable()->after('post_categories')
                ->comment('Google返信生成時に商品カテゴリをグループ化してランダム抽出するための設定。配列の配列。各サブ配列が1テーマグループ。');
        });
    }

    public function down()
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn('reply_category_groups');
        });
    }
};
