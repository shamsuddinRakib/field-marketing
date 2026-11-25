<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class BookRequest extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const REQUEST_TYPES = [
        'specimen' => 'Specimen Copy',
        'sale' => 'Sale Copy',
    ];

    protected $fillable = [
        'marketing_representative_id',
        'request_group_id',
        'request_type',
        // 'product_id',
        // 'quantity',
        'note',
        'request_date',
        'status',
    ];

    protected $casts = [
        // 'quantity' => 'integer',
        'request_date' => 'date',
    ];

    public function representative(): BelongsTo
    {
        return $this->belongsTo(MarketingRepresentative::class, 'marketing_representative_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function requestTypeLabel(): string
    {
        return self::REQUEST_TYPES[$this->request_type] ?? ucfirst((string) ($this->request_type ?? 'specimen'));
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookRequestItem::class, 'book_request_id');
    }
}
