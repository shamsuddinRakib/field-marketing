<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
     protected $table = 'stock_ledgers';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}
