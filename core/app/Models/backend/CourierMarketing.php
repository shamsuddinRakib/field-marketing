<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierMarketing extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'pending' => 'Pending',
        'dispatched' => 'Dispatched',
        'in_transit' => 'In Transit',
        'delivered' => 'Delivered',
        'partially_received' => 'Partially Received',
        'received' => 'Received',
        'failed' => 'Failed',
        'returned' => 'Returned',
    ];

    protected $fillable = [
        'marketing_representative_id',
        'teacher_id',
        'institution_id',
        'quantity',
        'request_date',
        'courier_company',
        'tracking_number',
        'dispatch_date',
        'delivery_date',
        'status',
        'received_by',
        'received_at',
        'created_by',
        'note',
    ];

    protected $casts = [
        'request_date' => 'date',
        'dispatch_date' => 'date',
        'delivery_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function representative(): BelongsTo
    {
        return $this->belongsTo(
            MarketingRepresentative::class,
            'marketing_representative_id'
        );
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            CourierMarketingItem::class,
            'courier_marketing_id'
        );
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]
            ?? ucfirst((string) $this->status);
    }
}
