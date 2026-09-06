<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_match_found' => 'boolean',
        'matched_profile_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
