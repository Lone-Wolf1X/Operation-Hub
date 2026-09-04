<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('type')->default('target'); // 'applicant' or 'target'
            $table->string('name_english');
            $table->string('name_nepali')->nullable();
            $table->string('citizenship_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->jsonb('contact_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
