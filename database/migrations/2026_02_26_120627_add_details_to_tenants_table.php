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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('phone');
            $table->string('email')->nullable()->after('logo');
            $table->string('street_address')->nullable()->after('email');
            $table->string('building_name')->nullable()->after('street_address');
            $table->string('office_number')->nullable()->after('building_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['logo', 'email', 'street_address', 'building_name', 'office_number']);
        });
    }
};
