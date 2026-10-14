<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'department';
    protected $fillable = [
        'department_name',
    ];
}
