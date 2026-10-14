<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HardDeletes
{
    protected static function bootHardDeletes(): void
    {
        static::addGlobalScope('legacySoftDeletes', function (Builder $query) {
            $query->whereNull($query->getModel()->qualifyColumn('deleted_at'));
        });
    }
}