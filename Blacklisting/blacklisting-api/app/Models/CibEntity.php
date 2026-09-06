<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CibEntity extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'status',
        'cib_date',
    ];
}
