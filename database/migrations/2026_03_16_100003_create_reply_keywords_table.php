<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reply_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('reply_categories')->cascadeOnDelete();
            $table->string('label', 255);
            $table->string('keyword', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reply_keywords');
    }
};
