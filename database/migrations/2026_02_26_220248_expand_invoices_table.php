<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->after('id');
            
            // Company snapshot
            $table->string('company_name')->nullable()->after('proforma_quote_id');
            $table->string('company_email')->nullable()->after('company_name');
            $table->string('company_phone')->nullable()->after('company_email');
            $table->string('company_address')->nullable()->after('company_phone');
            $table->string('company_logo')->nullable()->after('company_address');

            // Client snapshot
            $table->string('client_name')->nullable()->after('customer_id');
            $table->string('client_email')->nullable()->after('client_name');
            $table->string('client_phone')->nullable()->after('client_email');
            $table->string('client_address')->nullable()->after('client_phone');

            // Financial info
            $table->decimal('tax_percent', 5, 2)->default(0)->after('notes');
            $table->decimal('discount', 10, 2)->default(0)->after('tax_percent');
            $table->date('expiry_date')->nullable()->after('discount');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_number',
                'company_name', 'company_email', 'company_phone', 'company_address', 'company_logo',
                'client_name', 'client_email', 'client_phone', 'client_address',
                'tax_percent', 'discount', 'expiry_date'
            ]);
        });
    }
}