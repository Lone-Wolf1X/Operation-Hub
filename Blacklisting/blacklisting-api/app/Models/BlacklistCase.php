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
        'notice_issued_at' => 'datetime',
        'dishonour_issued_at' => 'datetime',
        'dishonour_valid_until' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($case) {
            if (empty($case->case_number)) {
                $case->case_number = 'CASE-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }
            if (empty($case->rule_id)) {
                $rule = WorkflowRule::where('category', $case->category ?? 'blacklisting')->first() 
                     ?? WorkflowRule::first();
                if (!$rule) {
                    try {
                        $rule = WorkflowRule::create([
                            'tenant_id' => $case->tenant_id ?? 'default_tenant',
                            'name' => 'Default Blacklisting Rule',
                            'category' => $case->category ?? 'blacklisting',
                            'version' => 1,
                            'effective_date' => now()->toDateString(),
                            'config' => [
                                'stages' => [
                                    'draft', 
                                    'checker_verified', 
                                    'notice_issued', 
                                    'dishonour_issued', 
                                    'waiting_period', 
                                    'applicant_confirmed', 
                                    'blacklisting_initiated',
                                    'cib_submitted', 
                                    'blacklisted'
                                ],
                                'transitions' => [
                                    ['from' => 'draft', 'to' => 'checker_verified'],
                                    ['from' => 'checker_verified', 'to' => 'notice_issued'],
                                    ['from' => 'notice_issued', 'to' => 'waiting_period'],
                                    ['from' => 'waiting_period', 'to' => 'blacklisting_initiated'],
                                    ['from' => 'blacklisting_initiated', 'to' => 'cib_submitted'],
                                    ['from' => 'cib_submitted', 'to' => 'blacklisted']
                                ]
                            ]
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Rule creation error: ' . $e->getMessage());
                    }
                }
                if ($rule) {
                    $case->rule_id = $rule->id;
                }
            }
        });
    }

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

    public function application()
    {
        return $this->belongsTo(CaseApplication::class, 'application_id');
    }

    public function chequeDetails()
    {
        return $this->hasMany(ChequeDetail::class, 'case_id');
    }
}
