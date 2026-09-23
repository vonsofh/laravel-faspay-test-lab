<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('faspay-test-lab.tables.callbacks', 'faspay_test_lab_callbacks');

        Schema::table($table, function (Blueprint $table): void {
            $table->string('request_method', 12)->nullable()->after('service');
            $table->string('request_path')->nullable()->after('request_method');
            $table->string('content_type')->nullable()->after('client_ip');
            $table->string('user_agent', 500)->nullable()->after('content_type');
            $table->text('error')->nullable()->after('response_body');
        });
    }

    public function down(): void
    {
        $table = config('faspay-test-lab.tables.callbacks', 'faspay_test_lab_callbacks');

        Schema::table($table, function (Blueprint $table): void {
            $table->dropColumn(['request_method', 'request_path', 'content_type', 'user_agent', 'error']);
        });
    }
};
