<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cib_blacklists', function (Blueprint $table) {
            $table->id();
            
            // Core Identity
            $table->string('entity_type')->comment('individual or institutional');
            $table->string('name');
            $table->string('upload_batch_id')->nullable()->index(); // For releasing old entries
            
            // Common Fields
            $table->integer('blacklist_count')->nullable();
            $table->string('blacklist_number')->nullable()->index();
            $table->date('blacklist_date')->nullable();
            $table->string('blacklist_type')->nullable();
            $table->string('blacklist_nature_of_relation')->nullable();

            // Individual Fields
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('father_name')->nullable();
            $table->integer('citizenship_count')->nullable();
            $table->string('citizenship_number')->nullable()->index();
            $table->date('ctz_issue_date')->nullable();
            $table->string('ctz_issue_district')->nullable();
            $table->string('blacklist_sector')->nullable();

            // Institutional Fields
            $table->text('pan_details')->nullable();
            $table->integer('pan_count')->nullable();
            $table->string('pan')->nullable()->index();
            $table->date('pan_issue_date')->nullable();
            $table->string('pan_issue_district')->nullable();
            $table->integer('company_count')->nullable();
            $table->string('company_reg_number')->nullable()->index();
            $table->date('reg_date')->nullable();
            $table->string('reg_authority')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cib_blacklists');
    }
};
