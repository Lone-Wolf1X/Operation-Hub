<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to profiles table
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('sol_id', 50)->nullable()->after('type');
            $table->string('sol_name_en')->nullable()->after('sol_id');
            $table->string('sol_name_np')->nullable()->after('sol_name_en');

            $table->string('father_name_en')->nullable()->after('name_nepali');
            $table->string('father_name_np')->nullable()->after('father_name_en');
            $table->string('grandfather_name_en')->nullable()->after('father_name_np');
            $table->string('grandfather_name_np')->nullable()->after('grandfather_name_en');
            $table->string('spouse_name_en')->nullable()->after('grandfather_name_np');
            $table->string('spouse_name_np')->nullable()->after('spouse_name_en');

            $table->date('dob')->nullable()->after('spouse_name_np');
            $table->string('gender', 20)->nullable()->after('dob');
            $table->string('citizenship_issue_district')->nullable()->after('citizenship_number');
            $table->date('citizenship_issue_date')->nullable()->after('citizenship_issue_district');

            // Permanent Address (English & Nepali)
            $table->string('perm_province_en')->nullable()->after('citizenship_issue_date');
            $table->string('perm_province_np')->nullable()->after('perm_province_en');
            $table->string('perm_district_en')->nullable()->after('perm_province_np');
            $table->string('perm_district_np')->nullable()->after('perm_district_en');
            $table->string('perm_municipality_en')->nullable()->after('perm_district_np');
            $table->string('perm_municipality_np')->nullable()->after('perm_municipality_en');
            $table->string('perm_ward_no', 20)->nullable()->after('perm_municipality_np');
            $table->string('perm_tole_en')->nullable()->after('perm_ward_no');
            $table->string('perm_tole_np')->nullable()->after('perm_tole_en');

            // Temporary Address (English & Nepali)
            $table->string('temp_province_en')->nullable()->after('perm_tole_np');
            $table->string('temp_province_np')->nullable()->after('temp_province_en');
            $table->string('temp_district_en')->nullable()->after('temp_province_np');
            $table->string('temp_district_np')->nullable()->after('temp_district_en');
            $table->string('temp_municipality_en')->nullable()->after('temp_district_np');
            $table->string('temp_municipality_np')->nullable()->after('temp_municipality_en');
            $table->string('temp_ward_no', 20)->nullable()->after('temp_municipality_np');
            $table->string('temp_tole_en')->nullable()->after('temp_ward_no');
            $table->string('temp_tole_np')->nullable()->after('temp_tole_en');
        });

        // 2. Create sol_branches master table
        if (!Schema::hasTable('sol_branches')) {
            Schema::create('sol_branches', function (Blueprint $table) {
                $table->id();
                $table->string('sol_id', 50)->unique();
                $table->string('branch_name_en');
                $table->string('branch_name_np')->nullable();
                $table->string('province_en')->nullable();
                $table->string('province_np')->nullable();
                $table->string('district_en')->nullable();
                $table->string('district_np')->nullable();
                $table->string('municipality_np')->nullable();
                $table->string('ward_no', 20)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Copy from admin_db_utf8.branch_profiles if database exists
            try {
                $branches = DB::select("SELECT * FROM admin_db_utf8.branch_profiles WHERE sol_id IS NOT NULL AND sol_id != ''");
                foreach ($branches as $b) {
                    DB::table('sol_branches')->updateOrInsert(
                        ['sol_id' => $b->sol_id],
                        [
                            'branch_name_en' => $b->branch_name_en ?? ($b->location_name ?? 'Branch ' . $b->sol_id),
                            'branch_name_np' => $b->branch_name_np ?? null,
                            'province_en'    => $b->province ?? null,
                            'province_np'    => $b->province_np ?? null,
                            'district_en'    => $b->district ?? null,
                            'district_np'    => $b->district_np ?? null,
                            'municipality_np'=> $b->municipality_np ?? $b->local_body ?? null,
                            'ward_no'        => $b->ward_no ?? null,
                            'phone'          => $b->phone ?? null,
                            'email'          => $b->email ?? null,
                            'is_active'      => $b->is_active ?? 1,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                // Ignore if database does not exist
            }
        }

        // 3. Create nepal_locations master table
        if (!Schema::hasTable('nepal_locations')) {
            Schema::create('nepal_locations', function (Blueprint $table) {
                $table->id();
                $table->string('province_np');
                $table->string('province_en')->nullable();
                $table->string('district_np');
                $table->string('district_en')->nullable();
                $table->string('municipality_vdc_np');
                $table->string('municipality_vdc_en')->nullable();
                $table->integer('ward_count')->default(0);
                $table->timestamps();
            });

            // Copy from das_db_utf8.demopnepal if database exists
            try {
                $locs = DB::select("SELECT * FROM das_db_utf8.demopnepal");
                foreach ($locs as $l) {
                    DB::table('nepal_locations')->insert([
                        'province_np'         => $l->province,
                        'district_np'         => $l->district,
                        'municipality_vdc_np' => $l->municipality_vdc,
                        'ward_count'          => $l->wada_count ?? 0,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore if database does not exist
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nepal_locations');
        Schema::dropIfExists('sol_branches');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'sol_id', 'sol_name_en', 'sol_name_np',
                'father_name_en', 'father_name_np',
                'grandfather_name_en', 'grandfather_name_np',
                'spouse_name_en', 'spouse_name_np',
                'dob', 'gender', 'citizenship_issue_district', 'citizenship_issue_date',
                'perm_province_en', 'perm_province_np',
                'perm_district_en', 'perm_district_np',
                'perm_municipality_en', 'perm_municipality_np',
                'perm_ward_no', 'perm_tole_en', 'perm_tole_np',
                'temp_province_en', 'temp_province_np',
                'temp_district_en', 'temp_district_np',
                'temp_municipality_en', 'temp_municipality_np',
                'temp_ward_no', 'temp_tole_en', 'temp_tole_np',
            ]);
        });
    }
};
