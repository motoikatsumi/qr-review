<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('suggestion_categories', function (Blueprint $table) {
            $table->boolean('is_for_low_rating')->default(false)->after('is_active')
                ->comment('低評価(星1〜3)向けカテゴリかどうか。配下のテーマ全てがこの対象になる。');
        });

        // 既存データのバックフィル: 配下に is_for_low_rating=true のテーマがあるカテゴリは
        // カテゴリ自体も低評価向けとマークする
        DB::statement("
            UPDATE suggestion_categories sc
            SET is_for_low_rating = 1
            WHERE EXISTS (
                SELECT 1 FROM suggestion_themes st
                WHERE st.category_id = sc.id AND st.is_for_low_rating = 1
            )
        ");
    }

    public function down()
    {
        Schema::table('suggestion_categories', function (Blueprint $table) {
            $table->dropColumn('is_for_low_rating');
        });
    }
};
