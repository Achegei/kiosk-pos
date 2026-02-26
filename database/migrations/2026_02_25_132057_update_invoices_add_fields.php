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
        Schema::table('invoices', function (Blueprint $table) {
            // Optional reference / notes
            $table->string('reference')->nullable()->after('proforma_quote_id');
            $table->text('notes')->nullable()->after('reference');

            // Foreign keys for integrity
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
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['customer_id']);

            $table->dropColumn(['reference','notes']);
        });
    }
};