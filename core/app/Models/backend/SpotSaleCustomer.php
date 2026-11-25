<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpotSaleCustomer extends Model
{
    use SoftDeletes;

    protected $table = 'spot_sale_customers';

    protected $fillable = [
        'user_id',
        'customer_type',
        'name',
        'phone',
        'institution_id',
        'course_class_id',
        'address',
    ];

    /* ================= Relations ================= */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function courseClass()
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }

    public function spotSales()
    {
        return $this->hasMany(SpotSale::class, 'customer_id');
    }
}
