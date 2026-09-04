<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseEvent extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'payload' => 'array',
    ];

    public function blacklistCase()
    {
        return $this->belongsTo(BlacklistCase::class, 'case_id');
    }
}
