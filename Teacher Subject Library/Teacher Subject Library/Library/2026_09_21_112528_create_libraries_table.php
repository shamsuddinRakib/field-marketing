<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('libraries')) {
            return;
        }

        Schema::create('libraries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->nullable();
            $table->string('library_name', 191)->nullable();
            $table->string('code', 100)->nullable()->unique();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->unsignedBigInteger('upazila_id')->nullable();
            $table->unsignedInteger('district_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->text('address')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('upazila_id');
            $table->index('district_id');
            $table->index('division_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libraries');
    }
};
