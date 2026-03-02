<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Make receipt_number NOT NULL
            $table->unsignedBigInteger('receipt_number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Revert back to nullable if rolling back
            $table->unsignedBigInteger('receipt_number')->nullable()->change();
        });
    }
};