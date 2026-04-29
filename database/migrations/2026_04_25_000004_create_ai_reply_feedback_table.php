<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ai_reply_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('feedback_type', 20); // 'good' or 'bad'
            $table->tinyInteger('rating'); // 1-5（プレビュー時に指定された評価）
            $table->text('sample_review_comment')->nullable(); // プレビュー時の口コミ本文
            $table->text('generated_reply'); // AIが生成した返信文
            $table->string('category', 100)->nullable();
            $table->text('keywords')->nullable(); // カンマ区切り
            $table->string('customer_type', 20)->default('new');
            $table->text('comment')->nullable(); // 管理者のコメント（任意）
            $table->timestamps();

            $table->index(['store_id', 'feedback_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_reply_feedback');
    }
};
