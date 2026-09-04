<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'type', // 'applicant', 'target', etc.
        'name_english',
        'name_nepali',
        'citizenship_number',
        'pan_number',
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
