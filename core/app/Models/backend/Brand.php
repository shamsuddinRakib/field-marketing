<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HardDeletes;

    protected $table = 'brands';
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_image',

    ];
    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
