<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $merchantsTable = config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
        $runsTable = config('faspay-test-lab.tables.runs', 'faspay_test_lab_runs');

        if (! Schema::hasTable($runsTable)) {
            Schema::create($runsTable, function (Blueprint $table) use ($merchantsTable): void {
                $table->id();
                $table->foreignId('faspay_merchant_id')->constrained($merchantsTable)->cascadeOnDelete();
                $table->string('service')->default('qris');
                $table->json('results');
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $runsTable = config('faspay-test-lab.tables.runs', 'faspay_test_lab_runs');
        Schema::dropIfExists($runsTable);
    }
};
