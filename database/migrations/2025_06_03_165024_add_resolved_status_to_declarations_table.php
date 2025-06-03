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
        // For SQLite, we need to recreate the table with the new enum values
        // This is because SQLite doesn't support ALTER COLUMN for enum changes
        Schema::table('declarations', function (Blueprint $table) {
            // Drop the existing status column
            $table->dropColumn('status');
        });

        Schema::table('declarations', function (Blueprint $table) {
            // Add the status column with the new enum values including 'resolved'
            $table->enum('status', ['pending', 'approved', 'resolved', 'rejected'])->default('pending')->after('employer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('declarations', function (Blueprint $table) {
            // Drop the status column
            $table->dropColumn('status');
        });

        Schema::table('declarations', function (Blueprint $table) {
            // Restore the original enum values
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('employer_id');
        });
    }
};
