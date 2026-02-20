<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {

            $table->id();

            // who did the action
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // optional device tracking
            $table->string('device_uuid')->nullable();

            // action info
            $table->string('action'); 
            // examples: created, updated, deleted, login, checkout, refund

            $table->string('table_name')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();

            // before / after values
            $table->longText('old_values')->nullable();
            $table->longText('new_values')->nullable();

            // request info
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // indexing for fast searches
            $table->index('user_id');
            $table->index('table_name');
            $table->index('record_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
