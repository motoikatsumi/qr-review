<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiServiceAndReplySettingsToStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('ai_service_keywords')->nullable()->after('ai_area_keywords')
                  ->comment('口コミ返信に含めるサービスキーワード（1行1ワード）');
            $table->text('ai_reply_instruction')->nullable()->after('ai_service_keywords')
                  ->comment('口コミ返信の方針・スタイル（自由記述）');
        });
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['ai_service_keywords', 'ai_reply_instruction']);
        });
    }
}
