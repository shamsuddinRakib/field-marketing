<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                if (!Schema::hasColumn('expense_items', 'from_location')) {
                    $table->string('from_location', 255)->nullable()->after('expense_category_id');
                }
                if (!Schema::hasColumn('expense_items', 'to_location')) {
                    $table->string('to_location', 255)->nullable()->after('from_location');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_items')) {
            Schema::table('expense_items', function (Blueprint $table) {
                if (Schema::hasColumn('expense_items', 'to_location')) {
                    $table->dropColumn('to_location');
                }
                if (Schema::hasColumn('expense_items', 'from_location')) {
                    $table->dropColumn('from_location');
                }
            });
        }
    }
};

