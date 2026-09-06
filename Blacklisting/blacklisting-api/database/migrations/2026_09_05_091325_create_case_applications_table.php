<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->string('tenant_id')->index();
            $table->foreignId('applicant_id')->constrained('profiles');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_applications');
    }
};
