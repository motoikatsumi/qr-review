<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('store_wordpress', function (Blueprint $table) {
            $table->string('db_mode', 20)->default('create')->after('db_name'); // create, shared, pool
            $table->string('table_prefix', 50)->default('wp_')->after('db_mode');
        });

        // 事前作成 DB プール（mode=pool 用）
        Schema::create('tenant_wp_db_pool', function (Blueprint $table) {
            $table->id();
            $table->string('db_name', 100)->unique();
            $table->unsignedBigInteger('store_id')->nullable()->index(); // null=空き、設定あり=使用中
            $table->timestamp('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_wp_db_pool');
        Schema::table('store_wordpress', function (Blueprint $table) {
            $table->dropColumn(['db_mode', 'table_prefix']);
        });
    }
};
