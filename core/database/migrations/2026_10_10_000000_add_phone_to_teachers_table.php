<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table) {
                $table->id();
                $table->string('teacher_name', 191)->nullable();
                $table->string('phone', 20)->nullable();
                $table->unsignedBigInteger('institution_id')->nullable()->index();
                $table->string('designation', 100)->nullable();
                $table->string('department', 100)->nullable();
                $table->string('subject', 100)->nullable();
                $table->string('class_name', 100)->nullable();
                $table->boolean('status')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });

            return;
        }

        if (!Schema::hasColumn('teachers', 'phone')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('teacher_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teachers') && Schema::hasColumn('teachers', 'phone')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
