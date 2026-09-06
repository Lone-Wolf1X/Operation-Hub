<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add entity_type and dob_bs to profiles table
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('entity_type', 30)->default('individual')->after('type');
            // Individual extra
            $table->string('dob_bs', 20)->nullable()->after('dob');
            $table->string('citizenship_authority')->nullable()->after('citizenship_issue_date');
            // Institutional extra
            $table->string('registration_type', 50)->nullable()->after('pan_number');
            $table->string('registration_number')->nullable()->after('registration_type');
            $table->string('registration_date_ad')->nullable()->after('registration_number');
            $table->string('registration_date_bs', 20)->nullable()->after('registration_date_ad');
            $table->string('registration_district')->nullable()->after('registration_date_bs');
        });

        // 2. Add drawer_id, dishonour dates, cheque_date_bs to cheque_details
        Schema::table('cheque_details', function (Blueprint $table) {
            $table->foreignId('drawer_id')
                  ->nullable()
                  ->after('case_id')
                  ->constrained('profiles')
                  ->nullOnDelete();
            $table->string('cheque_date_bs', 20)->nullable()->after('cheque_date');
            $table->date('dishonour_date_ad')->nullable()->after('cheque_date_bs');
            $table->string('dishonour_date_bs', 20)->nullable()->after('dishonour_date_ad');
        });

        // 3. Add notice_generated_at and dishonour_cert_generated_at to cases table
        Schema::table('cases', function (Blueprint $table) {
            if (!Schema::hasColumn('cases', 'notice_generated_at')) {
                $table->timestamp('notice_generated_at')->nullable()->after('notice_expires_at');
            }
            if (!Schema::hasColumn('cases', 'dishonour_cert_generated_at')) {
                $table->timestamp('dishonour_cert_generated_at')->nullable()->after('notice_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cheque_details', function (Blueprint $table) {
            $table->dropForeign(['drawer_id']);
            $table->dropColumn(['drawer_id', 'cheque_date_bs', 'dishonour_date_ad', 'dishonour_date_bs']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'entity_type', 'dob_bs', 'citizenship_authority',
                'registration_type', 'registration_number',
                'registration_date_ad', 'registration_date_bs', 'registration_district',
            ]);
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn(['notice_generated_at', 'dishonour_cert_generated_at']);
        });
    }
};
