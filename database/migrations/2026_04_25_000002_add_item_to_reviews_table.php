<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reviews テーブルに「品目」カラムを追加
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('item', 50)->nullable()->after('visit_type');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('item');
        });
    }
};
