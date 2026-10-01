<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Link to the HR record. FK constraint gets added once the employees table exists.
            $table->unsignedBigInteger('employee_id')->nullable()->unique()->after('id');
            $table->string('status', 20)->default('active')->index()->after('password'); // active, inactive, suspended
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employee_id', 'status', 'last_login_at']);
        });
    }
};