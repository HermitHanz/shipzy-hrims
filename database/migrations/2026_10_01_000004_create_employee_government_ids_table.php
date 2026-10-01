<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_government_ids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();

            // Per ID: encrypted number, last 4 digits (for masked display), and a keyed hash (duplicate detection).
            foreach (['sss', 'tin', 'philhealth', 'pagibig'] as $type) {
                $table->text("{$type}_number")->nullable();
                $table->string("{$type}_last4", 4)->nullable();
                $table->char("{$type}_hash", 64)->nullable()->unique();
            }

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_government_ids');
    }
};
