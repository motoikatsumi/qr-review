<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('business_type_id')
                ->nullable()
                ->after('is_active')
                ->constrained('business_types')
                ->nullOnDelete();

            // AI文章生成カスタマイズ設定
            $table->text('ai_custom_instruction')->nullable()->after('business_type_id');
            $table->text('ai_extra_ng_words')->nullable()->after('ai_custom_instruction');
            // auto=ランダム, formal=フォーマル, casual=カジュアル
            $table->string('ai_tone_preference', 20)->default('auto')->after('ai_extra_ng_words');
        });
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['business_type_id']);
            $table->dropColumn(['business_type_id', 'ai_custom_instruction', 'ai_extra_ng_words', 'ai_tone_preference']);
        });
    }
};
