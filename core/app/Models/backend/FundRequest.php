<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class FundRequest extends Model
{
    protected $table = 'fund_requests';
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
