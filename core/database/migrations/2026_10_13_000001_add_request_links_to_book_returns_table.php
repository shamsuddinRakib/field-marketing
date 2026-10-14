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
            if (! Schema::hasColumn('book_returns', 'book_request_id')) {
                $table->foreignId('book_request_id')->nullable()->constrained('book_requests')->nullOnDelete();
            }
            if (! Schema::hasColumn('book_returns', 'return_group_id')) {
                $table->uuid('return_group_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('book_returns')) {
            return;
        }

        Schema::table('book_returns', function (Blueprint $table) {
            if (Schema::hasColumn('book_returns', 'book_request_id')) {
                $table->dropConstrainedForeignId('book_request_id');
            }
            if (Schema::hasColumn('book_returns', 'return_group_id')) {
                $table->dropColumn('return_group_id');
            }
        });
    }
};