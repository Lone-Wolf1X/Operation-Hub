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
        Schema::table('cases', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('status');
            $table->unsignedBigInteger('verified_by')->nullable()->after('is_verified');
            
            $table->timestamp('notice_issued_at')->nullable()->after('verified_by');
            $table->string('notice_proof_path')->nullable()->after('notice_issued_at');
            $table->string('postal_receipt_path')->nullable()->after('notice_proof_path');
            $table->string('email_proof_path')->nullable()->after('postal_receipt_path');
            
            $table->timestamp('dishonour_issued_at')->nullable()->after('email_proof_path');
            $table->timestamp('dishonour_valid_until')->nullable()->after('dishonour_issued_at');
            
            $table->boolean('applicant_confirmation_received')->default(false)->after('dishonour_valid_until');
            $table->text('maker_notes')->nullable()->after('applicant_confirmation_received');

            // Foreign key for verified_by if the users table exists.
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'is_verified',
                'verified_by',
                'notice_issued_at',
                'notice_proof_path',
                'postal_receipt_path',
                'email_proof_path',
                'dishonour_issued_at',
                'dishonour_valid_until',
                'applicant_confirmation_received',
                'maker_notes'
            ]);
        });
    }
};
