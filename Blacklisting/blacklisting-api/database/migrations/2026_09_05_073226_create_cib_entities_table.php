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
        Schema::create('cib_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('entity_type')->default('Individual'); // Individual, Unit
            $table->string('status')->default('Blacklisted'); // Blacklisted, Released
            $table->date('cib_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cib_entities');
    }
};
