<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('use_wordpress')->default(true)->after('custom_hashtags')
                ->comment('WordPress 連携を使うか（false なら画像を直接 Cloudinary に上げる）');
        });
    }

    public function down()
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('use_wordpress');
        });
    }
};
