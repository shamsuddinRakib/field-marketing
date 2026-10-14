<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('book_returns')) {
            return;
        }

        Schema::table('book_returns', function (Blueprint $table) {
            if (! Schema::hasColumn('book_returns', 'product_distribution_id')) {
                $table->foreignId('product_distribution_id')->nullable()->constrained('product_distributions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('book_returns')) {
            return;
        }

        Schema::table('book_returns', function (Blueprint $table) {
            if (Schema::hasColumn('book_returns', 'product_distribution_id')) {
                $table->dropConstrainedForeignId('product_distribution_id');
            }
        });
    }
};
