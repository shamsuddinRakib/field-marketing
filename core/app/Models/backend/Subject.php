<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HardDeletes;

    protected $table = 'subjects';
    protected $fillable = [
        'subject_name',
    ];
}
