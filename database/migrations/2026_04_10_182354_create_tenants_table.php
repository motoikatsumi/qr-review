<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ※ このマイグレーションは master DB（qr_master）に対して実行する
 *   php artisan migrate --database=master
 */
class CreateTenantsTable extends Migration
{
    protected $connection = 'master';

    public function up()
    {
        Schema::connection('master')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('subdomain')->unique();
            $table->string('db_name')->unique();
            $table->string('db_username')->nullable();
            $table->string('db_password')->nullable();
            $table->string('plan')->default('standard');
            $table->string('contact_email');
            $table->string('contact_name')->nullable();
            $table->integer('ai_monthly_limit')->default(200);
            $table->boolean('is_active')->default(true);
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection('master')->create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('master')->dropIfExists('super_admins');
        Schema::connection('master')->dropIfExists('tenants');
    }
}
