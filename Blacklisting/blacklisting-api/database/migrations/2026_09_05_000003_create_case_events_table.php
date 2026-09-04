<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_events', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            
            $table->string('event_type'); // e.g. Notice Issued, Hold Requested
            $table->foreignId('user_id')->nullable(); // Action Performer
            
            $table->json('payload')->nullable(); // Immutable snapshot of changes/data
            $table->string('reason')->nullable();
            
            $table->timestamps(); // Timeline sorting base
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_events');
    }
};
