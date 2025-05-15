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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('nsc');
            $table->enum('status', ['active', 'on_hold', 'in_progress']);
            $table->string('ip_address');
            $table->string('serial_number');
            $table->string('processor');
            $table->string('brand');
            $table->string('office_version');
            $table->string('label');
            $table->boolean('backup_enabled')->default(false);
            $table->foreignId('employer_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipments');
    }
};
