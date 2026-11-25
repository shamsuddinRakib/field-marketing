<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use App\Models\backend\Student;

class SpotSale extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'subtotal',
        'discount',
        'total',
        'teacher_id',
        'library_id',
        'student_id',
        'status', // pending | approved
        'note',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'total'    => 'float',
    ];

    /* ================= Relations ================= */

    public function items()
    {
        return $this->hasMany(SpotSaleItem::class, 'spot_sale_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }

    public function customer()
    {
        return $this->belongsTo(SpotSaleCustomer::class, 'customer_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /* Helpers */

    public function customerType(): ?string
    {
        if ($this->customer) {
            return $this->customer->customer_type;
        }
        if ($this->teacher_id) {
            return 'teacher';
        }
        if ($this->library_id) {
            return 'library';
        }
        if ($this->student_id) {
            return 'student';
        }

        return null;
    }

    public function customerName(): string
    {
        if ($this->customer) {
            return $this->customer->name ?? '—';
        }
        if ($this->teacher_id && $this->teacher) {
            return $this->teacher->teacher_name ?? '-';
        }
        if ($this->library_id && $this->library) {
            return $this->library->library_name ?? ($this->library->name ?? '-');
        }
        if ($this->student_id && $this->student) {
            return $this->student->student_name ?? '-';
        }

        return '—';
    }

    public function customerPhone(): string
    {
        if ($this->customer) {
            return $this->customer->phone ?? '—';
        }
        if ($this->teacher_id && $this->teacher) {
            return $this->teacher->phone ?? '—';
        }
        if ($this->library_id && $this->library) {
            return $this->library->phone ?? '—';
        }

        return '—';
    }
}
