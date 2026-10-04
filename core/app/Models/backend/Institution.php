<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'institution_name',
        'code',
        'email',
        'phone',
        'upazila_id',
        'district_id',
        'division_id',
        'address',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function upazila()
    {
        return $this->belongsTo(Upazila::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
