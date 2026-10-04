<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class FundTransferLedger extends Model
{
    protected $table = 'fund_transfer_ledgers';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
}
