<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class CurrentFund extends Model
{
    protected $table = 'current_funds';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}
