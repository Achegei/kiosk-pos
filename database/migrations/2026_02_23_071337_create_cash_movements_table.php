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
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();

        // MULTI TENANT (REQUIRED)
        $table->unsignedBigInteger('tenant_id');

        // REGISTER SESSION
        $table->unsignedBigInteger('register_session_id')->nullable();

        // USER
        $table->unsignedBigInteger('user_id')->nullable();

        // MOVEMENT TYPE
        $table->enum('type', [
            'drop',
            'expense',
            'payout',
            'deposit',
            'adjustment'
        ]);

        // MONEY
        $table->decimal('amount', 10, 2);

        // COMMENT / NOTE
        $table->string('note',255)->nullable();

        $table->timestamps();

        // INDEXES (VERY IMPORTANT FOR 10k TENANTS)
        $table->index('tenant_id');
        $table->index('register_session_id');
        $table->index('type');
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
