<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        if (! Schema::hasColumn('branches', 'code')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('branches', 'settings')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->json('settings')->nullable();
            });
        }

        if (! Schema::hasColumn('branches', 'deleted_at')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The original branches table is created by the earlier migration.
    }
};
