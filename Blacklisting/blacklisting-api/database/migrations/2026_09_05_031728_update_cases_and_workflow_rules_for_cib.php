<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Drop old customer_name
            $table->dropColumn('customer_name');
            $table->dropColumn('customer_id');
            
            // Add Profile references
            $table->foreignId('applicant_id')->nullable()->constrained('profiles');
            $table->foreignId('target_id')->nullable()->constrained('profiles');
            
            // Add CIB workflow columns
            $table->string('cib_document_path')->nullable();
            $table->string('blacklist_number')->nullable();
            $table->string('blacklist_date')->nullable();
            $table->string('blacklist_time')->nullable();
        });
        
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->string('cib_processing_department')->default('province');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('customer_name')->nullable();
            $table->string('customer_id')->nullable();
            
            $table->dropForeign(['applicant_id']);
            $table->dropForeign(['target_id']);
            $table->dropColumn(['applicant_id', 'target_id']);
            
            $table->dropColumn(['cib_document_path', 'blacklist_number', 'blacklist_date', 'blacklist_time']);
        });
        
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->dropColumn('cib_processing_department');
        });
    }
};
