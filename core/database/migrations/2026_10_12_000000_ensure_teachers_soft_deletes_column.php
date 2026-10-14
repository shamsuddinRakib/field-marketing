<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teachers') && ! Schema::hasColumn('teachers', 'deleted_at')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Keep the column if it existed before this migration.
    }
};