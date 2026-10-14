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

    protected $fillable = [
        'marketing_representative_id',
        'request_group_id',
        'product_distribution_id',
        'product_id',
        'quantity',
        'note',
        'request_date',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
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

    public function productDistribution(): BelongsTo
    {
        return $this->belongsTo(ProductDistribution::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(BookReturn::class, 'book_request_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
