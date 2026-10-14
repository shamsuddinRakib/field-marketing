<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'roles',
            'permissions',
            'firewall_rules',
            'branches',
            'categories',
            'brands',
            'units',
            'products',
            'sizes',
            'colors',
            'category_types',
            'product_types',
            'institutions',
            'libraries',
            'marketing_representatives',
            'subjects',
            'teachers',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        // Keep repaired columns to avoid removing columns that predated this migration.
    }
};