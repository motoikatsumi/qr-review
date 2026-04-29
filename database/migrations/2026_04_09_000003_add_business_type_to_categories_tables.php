<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('suggestion_categories', function (Blueprint $table) {
            $table->foreignId('business_type_id')
                ->nullable()
                ->after('name')
                ->constrained('business_types')
                ->nullOnDelete();
        });

        Schema::table('reply_categories', function (Blueprint $table) {
            $table->foreignId('business_type_id')
                ->nullable()
                ->after('name')
                ->constrained('business_types')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('suggestion_categories', function (Blueprint $table) {
            $table->dropForeign(['business_type_id']);
            $table->dropColumn('business_type_id');
        });

        Schema::table('reply_categories', function (Blueprint $table) {
            $table->dropForeign(['business_type_id']);
            $table->dropColumn('business_type_id');
        });
    }
};
