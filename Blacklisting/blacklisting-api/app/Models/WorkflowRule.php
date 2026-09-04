<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowRule extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'effective_date' => 'date',
        'config' => 'array',
    ];
}
