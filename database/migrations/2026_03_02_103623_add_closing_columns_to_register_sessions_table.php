<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->decimal('cash_sales',12,2)->default(0);
            $table->decimal('mpesa_sales',12,2)->default(0);
            $table->decimal('credit_sales',12,2)->default(0);
            $table->decimal('difference',12,2)->default(0);
            $table->decimal('cash_drops',12,2)->default(0);
            $table->decimal('cash_expenses',12,2)->default(0);
            $table->decimal('cash_payouts',12,2)->default(0);
            $table->decimal('cash_deposits',12,2)->default(0);
            $table->decimal('cash_adjustments',12,2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('register_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'cash_sales','mpesa_sales','credit_sales','difference',
                'cash_drops','cash_expenses','cash_payouts','cash_deposits','cash_adjustments'
            ]);
        });
    }
};