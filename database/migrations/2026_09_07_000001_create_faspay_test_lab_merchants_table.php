<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('base_url')->default('https://debit-sandbox.faspay.co.id');
                $table->string('partner_id', 32);
                $table->string('merchant_id', 32);
                $table->longText('private_key');
                $table->string('channel_id', 16)->default('77001');
                $table->string('qris_channel_code', 32)->default('836');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $table = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
        Schema::dropIfExists($table);
    }
};
