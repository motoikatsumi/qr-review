<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstagramColumnsToPurchasePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->string('instagram_media_id')->nullable()->after('google_photo_error');
            $table->string('instagram_status')->nullable()->after('instagram_media_id');
            $table->text('instagram_error')->nullable()->after('instagram_status');
        });

        // Instagram API設定をsite_settingsに追加
        \App\Models\SiteSetting::set('instagram_access_token', 'IGAASFlFA24zxBZAFpzNWJzdlU4YlZAqQ1FNQjUzYUFKd1pYaGEwcDg2b3VsTnVSbU9mQmU5eEFKUUxOalhpdEZAKX3FTZAGZAsc1hONUd5ZAGoxVGEzSGpxejFsYThvc1c1NnJQbFpXYlZAQMDllRElTcEZAOQ2FEc2REbENWVjlDVWx0ZAwZDZD');
        \App\Models\SiteSetting::set('instagram_user_id', '17841407947499797');
        \App\Models\SiteSetting::set('instagram_app_id', '1272771954271036');
        \App\Models\SiteSetting::set('instagram_app_secret', '1f8a4d9c5ee792af7335b532ac72d5e5');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->dropColumn(['instagram_media_id', 'instagram_status', 'instagram_error']);
        });
    }
}
