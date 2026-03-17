<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('google_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('google_review_id', 255)->unique();
            $table->string('reviewer_name', 255);
            $table->string('reviewer_photo_url', 500)->nullable();
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->text('reply_comment')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['store_id', 'reviewed_at']);
            $table->index('replied_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('google_reviews');
    }
};
