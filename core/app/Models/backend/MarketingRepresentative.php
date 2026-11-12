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
        //'organization',
        'mr_designation_id',
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

    public function mrDesignation()
    {
        return $this->belongsTo(MrDesignation::class, 'mr_designation_id');
    }

    public function designation()
    {
        return $this->belongsTo(MrDesignation::class, 'mr_designation_id');
    }

    public function territories()
    {
        return $this->hasMany(MarketingRepresentativeTerritory::class, 'marketing_representative_id');
    }

    public function upazilas()
    {
        return $this->belongsToMany(Upazila::class, 'marketing_representative_territories', 'marketing_representative_id', 'upazila_id');
    }

    public function districts()
    {
        return $this->belongsToMany(District::class, 'marketing_representative_territories', 'marketing_representative_id', 'district_id', 'id', 'district_id')->distinct();
    }
}