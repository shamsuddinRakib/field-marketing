<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('book_requests') && ! Schema::hasColumn('book_requests', 'request_group_id')) {
            Schema::table('book_requests', function (Blueprint $table) {
                $table->uuid('request_group_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('book_requests') && Schema::hasColumn('book_requests', 'request_group_id')) {
            Schema::table('book_requests', function (Blueprint $table) {
                $table->dropColumn('request_group_id');
            });
        }
    }
};