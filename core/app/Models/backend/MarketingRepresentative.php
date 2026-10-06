<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingRepresentative extends Model
{
    use SoftDeletes;

    protected $fillable = [
        //'name',
        'user_id',
        'employee_id',
        // 'password',
        // 'mobile',
        // 'email',
        'organization',
        'branch_id',
        'upazila_id',
        'district_id',
        'division_id',
        'address',
        'status',
    ];

    protected $casts = [
        'password' => 'hashed',
        'status' => 'boolean',
    ];

     public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

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