<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HardDeletes;

    protected $table = 'product_types';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
