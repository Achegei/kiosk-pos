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
        Schema::create('proforma_quote_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proforma_quote_id');
            $table->unsignedBigInteger('product_id'); // pulls from products
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // product price at time of quote
            $table->decimal('total', 10, 2); // quantity * price
            $table->timestamps();

            $table->foreign('proforma_quote_id')
                ->references('id')
                ->on('proforma_quotes')
                ->onDelete('cascade');
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_quote_items');
    }
};
