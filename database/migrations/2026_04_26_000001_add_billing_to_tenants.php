<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'master';

    public function up()
    {
        Schema::connection('master')->table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('monthly_fee_per_store')->default(11000)->after('plan')->comment('店舗あたりの月額料金（円）');
            $table->unsignedInteger('monthly_fee_override')->nullable()->after('monthly_fee_per_store')->comment('カスタム月額料金（指定時はこちらを優先）');
            $table->string('billing_company_name')->nullable()->after('monthly_fee_override')->comment('請求書宛名（会社名と異なる場合）');
            $table->string('billing_postal_code', 10)->nullable()->after('billing_company_name');
            $table->text('billing_address')->nullable()->after('billing_postal_code');
        });

        Schema::connection('master')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('invoice_number', 30)->unique()->comment('請求書番号 例: INV-2026-04-0001');
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedInteger('subtotal')->comment('小計（税抜）');
            $table->decimal('tax_rate', 5, 2)->default(10.00)->comment('消費税率');
            $table->unsignedInteger('tax_amount')->comment('消費税額');
            $table->unsignedInteger('total_amount')->comment('合計（税込）');
            $table->string('status', 20)->default('draft')->comment('draft/sent/paid/overdue/cancelled');
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('billing_company_name_snapshot')->nullable()->comment('発行時点の宛名スナップショット');
            $table->text('billing_address_snapshot')->nullable();
            $table->text('billing_postal_code_snapshot')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'billing_period_start']);
        });

        Schema::connection('master')->create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->string('description', 255)->comment('項目名 例: 質屋アシスト西千石店 月額利用料');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('amount')->comment('quantity × unit_price');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::connection('master')->dropIfExists('invoice_items');
        Schema::connection('master')->dropIfExists('invoices');
        Schema::connection('master')->table('tenants', function (Blueprint $table) {
            $table->dropColumn(['monthly_fee_per_store', 'monthly_fee_override', 'billing_company_name', 'billing_postal_code', 'billing_address']);
        });
    }
};
