<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header table already exists in production; keep idempotent for fresh installs.
        if (! Schema::hasTable('assign_specimens')) {
            Schema::create('assign_specimens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->unsignedBigInteger('library_id')->nullable()->index();
                $table->string('note', 255)->nullable();
                $table->enum('status', ['pending', 'approved'])->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('assign_specimen_items')) {
            Schema::create('assign_specimen_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('assign_specimen_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->integer('quantity');
                $table->timestamps();

                $table->foreign('assign_specimen_id')->references('id')->on('assign_specimens')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assign_specimen_items');
    }
};
