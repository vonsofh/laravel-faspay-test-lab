<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('faspay-test-lab.tables.runs', 'faspay_test_lab_runs');
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'public_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->uuid('public_id')->nullable()->after('id');
        });

        DB::table($table)
            ->whereNull('public_id')
            ->orderBy('id')
            ->eachById(function (object $run) use ($table): void {
                DB::table($table)->where('id', $run->id)->update([
                    'public_id' => (string) Str::uuid(),
                ]);
            });

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->unique('public_id');
        });
    }

    public function down(): void
    {
        $table = config('faspay-test-lab.tables.runs', 'faspay_test_lab_runs');
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'public_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['public_id']);
            $blueprint->dropColumn('public_id');
        });
    }
};