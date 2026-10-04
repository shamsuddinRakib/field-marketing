<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class SpotSale extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'teacher_id',
        'library_id',
        'status', // pending | approved
        'note',
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

    /* ================= Helpers ================= */

    public function customerType(): ?string
    {
        if ($this->teacher_id) {
            return 'teacher';
        }
        if ($this->library_id) {
            return 'library';
        }

        return null;
    }

    public function customerName(): string
    {
        if ($this->teacher_id && $this->relationLoaded('teacher') && $this->teacher) {
            return $this->teacher->teacher_name;
        }
        if ($this->library_id && $this->relationLoaded('library') && $this->library) {
            return $this->library->library_name ?: $this->library->name;
        }

        return '—';
    }
}
