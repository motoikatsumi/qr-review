<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFacebookColumnsToPurchasePostsTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->string('facebook_post_id')->nullable()->after('instagram_error');
            $table->string('facebook_status')->nullable()->after('facebook_post_id');
            $table->text('facebook_error')->nullable()->after('facebook_status');
        });

        // Facebook API設定をsite_settingsに追加
        \App\Models\SiteSetting::set('facebook_page_id', '61574396163482');
        \App\Models\SiteSetting::set('facebook_page_access_token', 'EAAaTA9xo2k4BRPmgMhRoueLRCH92eLbRACuUTHRYZAToXeyOjbbdQZCKCVZAucNcd3T13m2IzosZAoS0WrqFYJRqqEP1GZAAFwB7vyBFR2Sq00PDR23QIIIreHHZBqbZCzZBetcu627jf2WcZAEPeyq97dmu9ls1LomfFodqcoKJZCQjRZCahEpK2xMTgIBDlD4bnsZA6y6s1nLROaxgGOc66ZB7UDuXlGv1aOuXb');
    }

    public function down()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->dropColumn(['facebook_post_id', 'facebook_status', 'facebook_error']);
        });
    }
}
