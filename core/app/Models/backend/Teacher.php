<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'teacher_name',
        'phone',
        'institution_id',
        'designation',
        'department',
        'subject',
        'class_name',
        'upazila_id',
        'district_id',
        'division_id',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function upazila()
    {
        return $this->belongsTo(Upazila::class, 'upazila_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }
}
