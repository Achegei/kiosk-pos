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
        // Modify the status enum to include Returned and Adjusted
        DB::statement("ALTER TABLE invoices 
            MODIFY COLUMN status ENUM('Pending','Paid','On Credit','Returned','Adjusted') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
        public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE invoices 
            MODIFY COLUMN status ENUM('Pending','Paid','On Credit') NOT NULL DEFAULT 'Pending'");
    }
};
