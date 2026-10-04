<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class SpotSaleItem extends Model
{
    protected $fillable = [
        'spot_sale_id',
        'product_id',
        'price',
        'quantity',
        'total',
    ];

    protected $casts = [
        'price'    => 'float',
        'quantity' => 'integer',
        'total'    => 'float',
    ];

    /* ================= Relations ================= */

    public function spotSale()
    {
        return $this->belongsTo(SpotSale::class, 'spot_sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
