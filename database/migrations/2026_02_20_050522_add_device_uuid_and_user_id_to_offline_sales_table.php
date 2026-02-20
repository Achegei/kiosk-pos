<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->string('device_uuid')->nullable()->after('synced');
            $table->unsignedBigInteger('user_id')->nullable()->after('device_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('offline_sales', function (Blueprint $table) {
            $table->dropColumn(['device_uuid', 'user_id']);
        });
    }
};
