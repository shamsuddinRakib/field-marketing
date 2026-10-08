<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class MrLocation extends Model
{
    protected $table = 'mr_locations';

    protected $fillable = [
        'user_id',
        'date',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'date' => 'date',
        'latitude' => 'array',
        'longitude' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
