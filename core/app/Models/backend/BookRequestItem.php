<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BookRequestItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'book_request_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'book_request_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function bookRequest(): BelongsTo
    {
        return $this->belongsTo(BookRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
