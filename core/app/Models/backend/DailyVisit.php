<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class DailyVisit extends Model
{
   protected $table = 'daily_visits';
   protected $guarded = [
    'id',
    'created_at',
    'updated_at'
   ];

   public function user()
   {
    return $this->belongsTo(User::class);
   }

   public function product()
   {
    return $this->belongsTo(Product::class);
   }

   public function teacher()
   {
    return $this->belongsTo(Teacher::class);
   }
   public function library()
   {
    return $this->belongsTo(Library::class);
   }
}
