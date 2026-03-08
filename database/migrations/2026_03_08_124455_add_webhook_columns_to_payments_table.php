<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payload')) {
                $table->text('payload')->nullable();
            }
            if (!Schema::hasColumn('payments', 'api_ref')) {
                $table->string('api_ref')->nullable();
            }
            // Do not add status since it already exists
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payload')) {
                $table->dropColumn('payload');
            }
            if (Schema::hasColumn('payments', 'api_ref')) {
                $table->dropColumn('api_ref');
            }
            // Do not drop status
        });
    }
};