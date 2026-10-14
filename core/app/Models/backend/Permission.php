<?php

namespace App\Models\backend;

use App\Models\Traits\HardDeletes;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HardDeletes;
    protected $fillable = ['key','name','module','type','description','sort','is_active'];

    public function routes()
    {
        return $this->hasMany(PermissionRoute::class);
    }
}
