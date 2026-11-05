<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class CourierMarketingItem extends Model
{
    protected $fillable = [
        'courier_marketing_id',
        'book_request_item_id',
        'product_id',
        'quantity',
        'received_quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    public function courierMarketing(): BelongsTo
    {
        return $this->belongsTo(
            CourierMarketing::class,
            'courier_marketing_id'
        );
    }

    public function bookRequestItem(): BelongsTo
    {
        return $this->belongsTo(
            BookRequestItem::class,
            'book_request_item_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
