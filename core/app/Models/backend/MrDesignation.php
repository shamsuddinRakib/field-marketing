<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class MrDesignation extends Model
{
    protected $fillable = [
        'designation_name',
    ];

    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        return Schema::hasTable('mr_designation') ? 'mr_designation' : 'mr_designations';
    }
}

