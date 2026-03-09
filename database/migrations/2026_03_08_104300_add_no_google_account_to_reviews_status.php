<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddNoGoogleAccountToReviewsStatus extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE reviews MODIFY COLUMN status ENUM('redirected_to_google', 'email_sent', 'no_google_account') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE reviews MODIFY COLUMN status ENUM('redirected_to_google', 'email_sent') NOT NULL");
    }
}
