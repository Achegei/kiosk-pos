<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add tenant_id if it doesn't exist yet
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')
                      ->nullable()
                      ->constrained('tenants')
                      ->cascadeOnDelete()
                      ->after('id'); // place it after 'id', adjust as needed
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop if the column exists
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropForeign(['tenant_id']); // drop FK first
                $table->dropColumn('tenant_id');
            }
        });
    }
};