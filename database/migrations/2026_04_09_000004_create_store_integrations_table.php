<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreIntegrationsTable extends Migration
{
    public function up()
    {
        Schema::create('store_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('service'); // 'instagram' | 'facebook' | 'wordpress'
            $table->text('access_token')->nullable();   // encrypt()で暗号化
            $table->text('refresh_token')->nullable();  // encrypt()で暗号化
            $table->timestamp('token_expires_at')->nullable();
            $table->json('extra_data')->nullable();     // ig_user_id, page_id, wp_url, wp_username など
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'service']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_integrations');
    }
}
