<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * business_types にレビュー時の「品目」選択肢を追加
     */
    public function up(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->json('review_item_options')->nullable()->after('visit_type_options');
        });
    }

    public function down(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn('review_item_options');
        });
    }
};
