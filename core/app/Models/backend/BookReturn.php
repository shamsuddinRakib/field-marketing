<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReturn extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'partial' => 'Partially Returned',
        'received' => 'Received',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'marketing_representative_id',
        'institution_id',
        'product_id',
        'issued_quantity',
        'returned_quantity',
        'note',
        'status',
        'received_by',
        'received_date',
    ];

    protected $casts = [
        'issued_quantity' => 'integer',
        'returned_quantity' => 'integer',
        'received_date' => 'date',
    ];

    public function representative(): BelongsTo
    {
        return $this->belongsTo(MarketingRepresentative::class, 'marketing_representative_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
