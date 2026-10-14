<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HardDeletes;

    protected $fillable = [
        'teacher_name',
        'phone',
        'institution_id',
        'designation',
        'department',
        'subject',
        'class_name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
