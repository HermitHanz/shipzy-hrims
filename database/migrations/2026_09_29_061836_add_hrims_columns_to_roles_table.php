<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // NOTE: this must run AFTER Spatie's create_permission_tables migration.
    // If your published Spatie migration has a later timestamp, rename this file so it sorts after it.

    public function up(): void
    {
        $table = config('permission.table_names.roles', 'roles');

        Schema::table($table, function (Blueprint $table) {
            $table->string('label', 150)->nullable()->after('name');
            $table->text('description')->nullable()->after('label');
            $table->unsignedSmallInteger('level')->default(0)->after('description'); // 100 super, 50 hr admin, 10 employee
            $table->boolean('is_system')->default(false)->after('level');
        });
    }

    public function down(): void
    {
        $table = config('permission.table_names.roles', 'roles');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn(['label', 'description', 'level', 'is_system']);
        });
    }
};