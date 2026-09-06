<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseApplication extends Model
{
    protected $table = 'case_applications';
    protected $guarded = [];

    public function applicant()
    {
        return $this->belongsTo(Profile::class, 'applicant_id');
    }

    public function cases()
    {
        return $this->hasMany(BlacklistCase::class, 'application_id');
    }
}
