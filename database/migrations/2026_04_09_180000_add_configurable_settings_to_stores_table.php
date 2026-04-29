<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedTinyInteger('notify_threshold')->default(3)->after('notify_email')
                  ->comment('低評価通知メールの閾値（この星以下で通知）');
            $table->text('ai_store_description')->nullable()->after('ai_reply_instruction')
                  ->comment('買取エピソード等で使う店舗紹介文');
            $table->string('ai_reply_length', 20)->default('medium')->after('ai_store_description')
                  ->comment('AI返信の文字数: short/medium/long');
            $table->string('ai_suggestion_length', 20)->default('medium')->after('ai_reply_length')
                  ->comment('口コミ提案の長さ傾向: short/medium/long');
        });
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'notify_threshold',
                'ai_store_description',
                'ai_reply_length',
                'ai_suggestion_length',
            ]);
        });
    }
};
