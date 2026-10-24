<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class ProductDistribution extends Model
{
   protected $table = 'product_distributions';
   protected $guarded = [
    'id',
    'created_at',
    'updated_at'
   ];

    public function items()
    {
        return $this->hasMany(ProductDistributionItem::class, 'product_distribution_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
