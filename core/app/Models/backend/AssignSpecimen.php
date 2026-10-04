<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class AssignSpecimen extends Model
{
    protected $table = 'assign_specimens';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
