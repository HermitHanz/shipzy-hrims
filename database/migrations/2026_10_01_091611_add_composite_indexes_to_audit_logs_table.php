<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // The viewer always sorts newest-first and usually filters by actor or event.
            $table->index(['actor_id', 'created_at'], 'audit_actor_created_idx');
            $table->index(['action', 'created_at'], 'audit_action_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_actor_created_idx');
            $table->dropIndex('audit_action_created_idx');
        });
    }
};