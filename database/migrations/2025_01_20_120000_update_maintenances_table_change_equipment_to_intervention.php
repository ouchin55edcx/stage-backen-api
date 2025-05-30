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
            $table->dropForeign(['equipment_id']);
            
            // Drop the equipment_id column
            $table->dropColumn('equipment_id');
            
            // Add the intervention_id column with foreign key constraint
            $table->foreignId('intervention_id')->after('id')->constrained('interventions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['intervention_id']);
            
            // Drop the intervention_id column
            $table->dropColumn('intervention_id');
            
            // Add back the equipment_id column with foreign key constraint
            $table->foreignId('equipment_id')->after('id')->constrained('equipments')->onDelete('cascade');
        });
    }
};
