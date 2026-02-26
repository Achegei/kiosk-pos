<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proforma_quotes', function (Blueprint $table) {
            // Optional reference / notes
            $table->string('reference')->nullable()->after('status');
            $table->text('notes')->nullable()->after('reference');

            // Optional expiry date
            $table->timestamp('expires_at')->nullable()->after('notes');

            // Add foreign keys for integrity
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_quotes', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['customer_id']);

            $table->dropColumn(['reference','notes','expires_at']);
        });
    }
};