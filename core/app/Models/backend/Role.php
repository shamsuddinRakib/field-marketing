<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
      use HardDeletes;

     protected $table = 'roles';
      protected $guarded = [];
}
