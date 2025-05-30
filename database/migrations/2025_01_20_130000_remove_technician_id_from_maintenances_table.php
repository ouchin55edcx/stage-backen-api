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
        Schema::table('maintenances', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['technician_id']);
            
            // Drop the technician_id column
            $table->dropColumn('technician_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            // Add back the technician_id column with foreign key constraint
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};
