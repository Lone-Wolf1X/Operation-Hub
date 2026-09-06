<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',          // 'applicant' | 'target' | 'drawer'
        'entity_type',   // 'individual' | 'institutional'
        'sol_id',
        'sol_name_en',
        'sol_name_np',
        // Individual
        'name_english',
        'name_nepali',
        'father_name_en',
        'father_name_np',
        'grandfather_name_en',
        'grandfather_name_np',
        'spouse_name_en',
        'spouse_name_np',
        'dob',
        'dob_bs',
        'gender',
        'citizenship_number',
        'citizenship_issue_district',
        'citizenship_issue_date',
        'citizenship_authority',
        // Institutional
        'registration_type',
        'registration_number',
        'registration_date_ad',
        'registration_date_bs',
        'registration_district',
        'pan_number',
        // Address (Permanent)
        'perm_province_en',
        'perm_province_np',
        'perm_district_en',
        'perm_district_np',
        'perm_municipality_en',
        'perm_municipality_np',
        'perm_ward_no',
        'perm_tole_en',
        'perm_tole_np',
        // Address (Temporary)
        'temp_province_en',
        'temp_province_np',
        'temp_district_en',
        'temp_district_np',
        'temp_municipality_en',
        'temp_municipality_np',
        'temp_ward_no',
        'temp_tole_en',
        'temp_tole_np',
        'contact_details',
    ];

    protected $casts = [
        'contact_details' => 'array',
    ];

    public function casesAsApplicant()
    {
        return $this->hasMany(BlacklistCase::class, 'applicant_id');
    }

    public function casesAsTarget()
    {
        return $this->hasMany(BlacklistCase::class, 'target_id');
    }
}
