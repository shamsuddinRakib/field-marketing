<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MrProductCurrentStock extends Model
{
    use HasFactory;

    protected $table = 'mr_product_current_stocks';

    protected $fillable = [
        'mr_id',
        'product_id',
        'branch_id',
        'brand',
        'assign_stock',
        'current_stock',
        'status',
        'is_returned',
    ];

    // Relation with Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relation with Marketing Representative
    public function marketingRepresentative()
    {
        return $this->belongsTo(MarketingRepresentative::class, 'mr_id');
    }
}
