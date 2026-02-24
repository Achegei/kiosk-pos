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
        Schema::create('tenants', function (Blueprint $table) {
             $table->id(); // bigIncrements, primary key
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('subscription_status')->default('trial');
            $table->timestamp('expiry_date')->nullable();
            $table->timestamps(); // created_at & updated_at
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
