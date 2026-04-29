<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE site_settings MODIFY value TEXT NOT NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE site_settings MODIFY value VARCHAR(255) NOT NULL');
    }
};
