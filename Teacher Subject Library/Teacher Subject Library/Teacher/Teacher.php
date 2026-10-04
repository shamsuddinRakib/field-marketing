<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_name',
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
