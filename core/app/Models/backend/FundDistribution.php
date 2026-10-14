<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class FundDistribution extends Model
{
    protected $table = 'fund_distributions';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
