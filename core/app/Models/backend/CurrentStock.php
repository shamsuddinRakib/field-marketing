<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class CurrentStock extends Model
{
    protected $table = 'current_stocks';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}
