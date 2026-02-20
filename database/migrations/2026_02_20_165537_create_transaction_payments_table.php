<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('transaction_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->enum('method',['Cash','Mpesa','Credit']);

            $table->decimal('amount',10,2);

            // mpesa reference etc
            $table->string('reference')->nullable();

            $table->timestamps();

            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};
