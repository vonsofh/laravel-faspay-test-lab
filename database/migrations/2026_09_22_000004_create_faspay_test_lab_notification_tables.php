<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $merchantsTable = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
        $accountsTable = config('faspay-test-lab.tables.va_accounts', 'faspay_test_lab_va_accounts');
        $callbacksTable = config('faspay-test-lab.tables.callbacks', 'faspay_test_lab_callbacks');

        if (! Schema::hasTable($accountsTable)) {
            Schema::create($accountsTable, function (Blueprint $table) use ($merchantsTable): void {
                $table->id();
                $table->foreignId('faspay_merchant_id')->constrained($merchantsTable)->cascadeOnDelete();
                $table->string('channel_code', 16);
                $table->string('channel_name');
                $table->string('partner_service_id', 16);
                $table->string('customer_no', 24);
                $table->string('virtual_account_no', 40)->unique();
                $table->string('display_name', 30)->default('FASPAY SANDBOX');
                $table->unsignedBigInteger('amount_minor');
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->index(['faspay_merchant_id', 'active']);
            });
        }

        if (! Schema::hasTable($callbacksTable)) {
            Schema::create($callbacksTable, function (Blueprint $table) use ($accountsTable): void {
                $table->id();
                $table->foreignId('faspay_test_lab_va_account_id')->nullable()
                    ->constrained($accountsTable)->nullOnDelete();
                $table->string('service', 32);
                $table->string('external_id')->nullable();
                $table->string('reference_no')->nullable();
                $table->string('signature_status', 24)->nullable();
                $table->unsignedSmallInteger('http_status');
                $table->string('response_code', 16)->nullable();
                $table->json('request_headers')->nullable();
                $table->json('request_body')->nullable();
                $table->json('response_body')->nullable();
                $table->string('client_ip', 64)->nullable();
                $table->timestamps();
                $table->index(['service', 'created_at']);
                $table->index(['external_id', 'service']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(config('faspay-test-lab.tables.callbacks', 'faspay_test_lab_callbacks'));
        Schema::dropIfExists(config('faspay-test-lab.tables.va_accounts', 'faspay_test_lab_va_accounts'));
    }
};
