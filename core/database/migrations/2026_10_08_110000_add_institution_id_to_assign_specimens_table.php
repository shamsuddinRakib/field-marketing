<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assign_specimens', function (Blueprint $table) {
            if (!Schema::hasColumn('assign_specimens', 'institution_id')) {
                $table->unsignedBigInteger('institution_id')->nullable()->after('library_id');
                $table->index('institution_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assign_specimens', function (Blueprint $table) {
            if (Schema::hasColumn('assign_specimens', 'institution_id')) {
                $table->dropIndex(['institution_id']);
                $table->dropColumn('institution_id');
            }
        });
    }
};
