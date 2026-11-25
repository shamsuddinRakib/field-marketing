<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class ProductDistribution extends Model
{
    public const DISTRIBUTION_TYPES = [
        'specimen' => 'Specimen Copy',
        'sale' => 'Sale Copy',
    ];

    protected $table = 'product_distributions';
    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    public function distributionTypeLabel(): string
    {
        return self::DISTRIBUTION_TYPES[$this->distribution_type] ?? ucfirst((string) ($this->distribution_type ?? 'specimen'));
    }

    public function items()
    {
        return $this->hasMany(ProductDistributionItem::class, 'product_distribution_id');
    }

   public function user()
   {
    return $this->belongsTo(User::class);
   }

   public function bookRequests()
   {
    return $this->hasMany(BookRequest::class);
   }
}
