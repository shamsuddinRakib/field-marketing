<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (!Schema::hasColumn('teachers', 'subject')) {
                $table->string('subject', 100)->nullable()->after('department');
            }
            // Use `class_name` instead of reserved `class` to avoid keyword issues; displays as "Class"
            if (!Schema::hasColumn('teachers', 'class_name') && !Schema::hasColumn('teachers', 'class')) {
                $table->string('class_name', 100)->nullable()->after('subject');
            } elseif (Schema::hasColumn('teachers', 'class') && !Schema::hasColumn('teachers', 'class_name')) {
                // If `class` already exists nothing to do; keep for backward compatibility
            }
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            if (Schema::hasColumn('teachers', 'subject')) {
                $table->dropColumn('subject');
            }
            if (Schema::hasColumn('teachers', 'class_name')) {
                $table->dropColumn('class_name');
            }
            if (Schema::hasColumn('teachers', 'class')) {
                $table->dropColumn('class');
            }
        });
    }
};
