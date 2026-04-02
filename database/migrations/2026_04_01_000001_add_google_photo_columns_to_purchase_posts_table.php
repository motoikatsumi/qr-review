<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->string('google_photo_name', 500)->nullable()->after('google_product_error');
            $table->string('google_photo_status', 20)->default('pending')->after('google_photo_name');
            $table->text('google_photo_error')->nullable()->after('google_photo_status');
        });
    }

    public function down()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->dropColumn(['google_photo_name', 'google_photo_status', 'google_photo_error']);
        });
    }
};
