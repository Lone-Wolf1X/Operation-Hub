<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistCase extends Model
{
    protected $table = 'cases';
    protected $guarded = [];
    
    protected $casts = [
        'current_deadline' => 'datetime',
        'total_liability' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function rule()
    {
        return $this->belongsTo(WorkflowRule::class, 'rule_id');
    }

    public function events()
    {
        return $this->hasMany(CaseEvent::class, 'case_id')->latest();
    }

    public function applicant()
    {
        return $this->belongsTo(Profile::class, 'applicant_id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'target_id');
    }
}
