<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('offline_sales', function (Blueprint $table) {
            $table->id();
            $table->json('sale_data'); // store sale details as JSON
            $table->boolean('synced')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('offline_sales');
    }
};