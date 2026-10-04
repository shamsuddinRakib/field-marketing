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

   public function product()
   {
    return $this->belongsTo(Product::class);
   }

   public function user()
   {
    return $this->belongsTo(User::class);
   }
}
