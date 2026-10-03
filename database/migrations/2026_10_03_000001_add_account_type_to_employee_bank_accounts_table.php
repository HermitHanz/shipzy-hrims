<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_bank_accounts', function (Blueprint $table) {
            // Nullable so accounts submitted before this column existed stay valid.
            $table->string('account_type', 20)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('employee_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
