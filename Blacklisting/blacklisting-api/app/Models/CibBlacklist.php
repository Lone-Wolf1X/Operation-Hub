<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CibBlacklist extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'blacklist_date' => 'date',
        'ctz_issue_date' => 'date',
        'pan_issue_date' => 'date',
        'date_of_birth' => 'date',
        'reg_date' => 'date',
    ];
}
