<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->string('rank', 10)->nullable()->after('product_status');
        });
    }

    public function down()
    {
        Schema::table('purchase_posts', function (Blueprint $table) {
            $table->dropColumn('rank');
        });
    }
};
