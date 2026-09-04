<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index(); // Row-level multi-tenancy
            $table->string('name');
            $table->string('category'); // e.g. cheque_dishonour
            $table->integer('version')->default(1);
            $table->date('effective_date');
            $table->json('config'); // Highly configurable JSON payload for timers, stages, exceptions
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
