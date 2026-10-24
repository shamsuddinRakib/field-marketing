<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class ProductDistributionItem extends Model
{
    protected $table = 'product_distribution_items';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function distribution()
    {
        return $this->belongsTo(ProductDistribution::class, 'product_distribution_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
