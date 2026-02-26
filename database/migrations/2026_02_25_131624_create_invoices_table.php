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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');       // link to tenant/shop
            $table->unsignedBigInteger('staff_id');        // staff creating the invoice
            $table->unsignedBigInteger('customer_id')->nullable(); // optional customer
            $table->unsignedBigInteger('proforma_quote_id')->nullable(); // optional: source quote
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['Paid','Pending','On Credit'])->default('Pending');
            $table->timestamps();

            $table->index(['tenant_id','staff_id','customer_id']);
            $table->foreign('proforma_quote_id')->references('id')->on('proforma_quotes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
