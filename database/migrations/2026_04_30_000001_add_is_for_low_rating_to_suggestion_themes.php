<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('suggestion_themes', function (Blueprint $table) {
            $table->boolean('is_for_low_rating')->default(false)->after('is_active')
                ->comment('低評価(星1〜3)向けテーマかどうか。true なら不満コメント生成用、false なら通常の高評価向け。');
        });
    }

    public function down()
    {
        Schema::table('suggestion_themes', function (Blueprint $table) {
            $table->dropColumn('is_for_low_rating');
        });
    }
};
