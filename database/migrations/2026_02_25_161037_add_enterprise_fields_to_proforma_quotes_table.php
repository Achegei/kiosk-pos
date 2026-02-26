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
        Schema::table('proforma_quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('proforma_quotes','quote_number'))
            $table->string('quote_number')->nullable();

        if (!Schema::hasColumn('proforma_quotes','company_name'))
            $table->string('company_name')->nullable();

        if (!Schema::hasColumn('proforma_quotes','company_email'))
            $table->string('company_email')->nullable();

        if (!Schema::hasColumn('proforma_quotes','company_phone'))
            $table->string('company_phone')->nullable();

        if (!Schema::hasColumn('proforma_quotes','company_address'))
            $table->string('company_address')->nullable();

        if (!Schema::hasColumn('proforma_quotes','company_logo'))
            $table->string('company_logo')->nullable();

        if (!Schema::hasColumn('proforma_quotes','client_name'))
            $table->string('client_name')->nullable();

        if (!Schema::hasColumn('proforma_quotes','client_email'))
            $table->string('client_email')->nullable();

        if (!Schema::hasColumn('proforma_quotes','client_phone'))
            $table->string('client_phone')->nullable();

        if (!Schema::hasColumn('proforma_quotes','client_address'))
            $table->string('client_address')->nullable();

        if (!Schema::hasColumn('proforma_quotes','tax_percent'))
            $table->decimal('tax_percent',5,2)->default(0);

        if (!Schema::hasColumn('proforma_quotes','discount'))
            $table->decimal('discount',10,2)->default(0);

        if (!Schema::hasColumn('proforma_quotes','expiry_date'))
            $table->date('expiry_date')->nullable();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'quote_number',
                'company_name',
                'company_email',
                'company_phone',
                'company_address',
                'company_logo',
                'client_name',
                'client_email',
                'client_phone',
                'client_address',
                'tax_percent',
                'discount'
            ]);
        });
    }
};
