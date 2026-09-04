<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index(); // Multi-tenancy isolation
            $table->string('case_number')->unique();
            $table->string('customer_name');
            $table->string('customer_id')->nullable();
            $table->string('account_number')->nullable();
            
            $table->string('category'); 
            $table->foreignId('rule_id')->constrained('workflow_rules');
            
            // Decoupled Statuses
            $table->string('current_stage')->default('draft'); // Spatie State Machine node
            $table->string('status')->default('active'); // Global case status
            $table->string('process_status')->default('running'); // Operational hold/resume status
            
            // Financial Tracking (Partial payments don't overwrite total liability)
            $table->decimal('total_liability', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            
            $table->timestamp('current_deadline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
