<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name');
            $table->string('product_name');
            $table->string('product_status')->default('中古品');
            $table->string('category');
            $table->string('customer_gender')->nullable();
            $table->string('customer_age')->nullable();
            $table->text('customer_reason')->nullable();
            $table->text('product_condition')->nullable();
            $table->text('accessories')->nullable();
            $table->text('block1_text');
            $table->text('block2_text');
            $table->text('block3_text');
            $table->text('full_text');
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('wp_post_id')->nullable();
            $table->unsignedBigInteger('wp_media_id')->nullable();
            $table->string('wp_image_url', 500)->nullable();
            $table->string('google_post_id', 500)->nullable();
            $table->string('google_product_id', 500)->nullable();
            $table->string('wp_status', 20)->default('pending');
            $table->string('google_post_status', 20)->default('pending');
            $table->string('google_product_status', 20)->default('pending');
            $table->text('wp_error')->nullable();
            $table->text('google_post_error')->nullable();
            $table->text('google_product_error')->nullable();
            $table->string('wp_category_slug')->nullable();
            $table->string('wp_tag_name')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_posts');
    }
};
