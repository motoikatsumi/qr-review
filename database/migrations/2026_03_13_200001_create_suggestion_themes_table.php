<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('suggestion_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('suggestion_categories')->cascadeOnDelete();
            $table->string('icon', 10);
            $table->string('label');
            $table->string('keyword');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('suggestion_themes');
    }
};
