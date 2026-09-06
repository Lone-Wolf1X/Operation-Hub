<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('search_type'); // name, citizenship, pan, reg_number
            $table->string('search_term');
            $table->boolean('is_match_found')->default(false);
            $table->json('matched_profile_ids')->nullable(); // Store matched ID(s)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_logs');
    }
};
