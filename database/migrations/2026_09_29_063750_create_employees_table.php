<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 30)->unique();

            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('work_email')->nullable()->unique();
            $table->string('phone', 30)->nullable();

            // Organization placement (these drive the branch / department / team scopes)
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->string('job_title', 150);
            $table->string('employment_type', 30)->default('regular');   // regular, probationary, contractual, part_time...
            $table->string('status', 20)->default('active')->index();    // active, on_leave, separated
            $table->date('hire_date');
            $table->date('separation_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};