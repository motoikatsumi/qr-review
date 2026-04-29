<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomHashtagsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 業種：デフォルトハッシュタグ
        Schema::table('business_types', function (Blueprint $table) {
            $table->text('post_default_hashtags')->nullable()->after('post_hidden_fields');
        });

        // 店舗：店舗固有ハッシュタグ
        Schema::table('stores', function (Blueprint $table) {
            $table->text('custom_hashtags')->nullable()->after('ai_store_description');
        });

        // 投稿：投稿ごとのハッシュタグ（編集可能）
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->text('custom_hashtags')->nullable()->after('wp_tag_name');
        });
    }

    public function down()
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn('post_default_hashtags');
        });
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('custom_hashtags');
        });
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->dropColumn('custom_hashtags');
        });
    }
}
