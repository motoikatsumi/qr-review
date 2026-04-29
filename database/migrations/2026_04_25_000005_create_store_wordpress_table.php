<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店舗ごとに自動構築した WordPress（FB/IG 連携用ブリッジ）の情報を保持するテーブル
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('store_wordpress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->unique();
            $table->string('install_path', 500); // 物理パス（例: /Applications/MAMP/htdocs/qr-tenant-wp/{slug}）
            $table->string('site_url', 500);     // WP サイト URL
            $table->string('admin_url', 500);    // wp-admin URL
            $table->string('db_name', 100);
            $table->string('admin_username', 100);
            $table->string('admin_password_encrypted', 500); // Crypt::encryptString
            $table->string('admin_email', 255);
            $table->string('app_password_encrypted', 500)->nullable(); // WP App Password（API 用）
            $table->string('status', 30)->default('pending'); // pending, installing, ready, failed
            $table->json('installed_plugins')->nullable();
            $table->json('connected_services')->nullable(); // ['facebook' => true, 'instagram' => true]
            $table->text('last_error')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_wordpress');
    }
};
