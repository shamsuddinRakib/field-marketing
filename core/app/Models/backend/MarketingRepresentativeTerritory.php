<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class MarketingRepresentativeTerritory extends Model
{
    protected $table = 'marketing_representative_territories';

    protected $fillable = [
        'marketing_representative_id',
        'upazila_id',
        'district_id',
        'division_id',
    ];

    public function representative()
    {
        return $this->belongsTo(MarketingRepresentative::class, 'marketing_representative_id');
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

