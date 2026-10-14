<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HardDeletes;
   
    protected $fillable = ['name','code','sort','is_active'];
    protected $casts = ['is_active'=>'boolean','sort'=>'integer'];

    public function product()
    {
        return $this->hasMany(Product::class);
    }
}