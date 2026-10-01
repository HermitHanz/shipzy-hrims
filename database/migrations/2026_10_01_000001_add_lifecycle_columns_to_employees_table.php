<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('regularization_date')->nullable()->after('hire_date');
            $table->string('separation_reason')->nullable()->after('separation_date');
            $table->timestamp('privacy_acknowledged_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['regularization_date', 'separation_reason', 'privacy_acknowledged_at', 'onboarding_completed_at']);
        });
    }
};
