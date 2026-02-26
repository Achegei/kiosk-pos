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
        Schema::create('proforma_quotes', function (Blueprint $table) {
             $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['Draft','Converted'])->default('Draft');
            $table->timestamps();

            $table->index(['tenant_id','staff_id','customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_quotes');
    }
};
