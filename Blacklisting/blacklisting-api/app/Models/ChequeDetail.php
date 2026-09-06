<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeDetail extends Model
{
    protected $fillable = [
        'case_id',
        'drawer_id',
        'cheque_date',
        'cheque_date_bs',
        'dishonour_date_ad',
        'dishonour_date_bs',
        'amount',
        'amount_in_words',
        'cheque_number',
        'payee_name',
        'payer_name',
        'account_number',
        'presentment_dates',
        'return_reason',
        'other_reason',
    ];

    protected $casts = [
        'presentment_dates' => 'array',
    ];

    public function blacklistCase()
    {
        return $this->belongsTo(BlacklistCase::class, 'case_id');
    }

    public function drawer()
    {
        return $this->belongsTo(Profile::class, 'drawer_id');
    }
}
