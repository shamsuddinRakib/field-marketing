<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class AssignSpecimenItem extends Model
{
    protected $table = 'assign_specimen_items';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function specimen()
    {
        return $this->belongsTo(AssignSpecimen::class, 'assign_specimen_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
