<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE: tables already existed in production DB; this migration
        // documents the actual schema (kept idempotent for fresh installs).
        if (! Schema::hasTable('spot_sales')) {
            Schema::create('spot_sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index()->comment('MR user');
                $table->integer('total');
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->unsignedBigInteger('library_id')->nullable()->index();
                $table->enum('status', ['pending', 'approved'])->default('pending');
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('spot_sale_items')) {
            Schema::create('spot_sale_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('spot_sale_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('price', 6, 2);
                $table->integer('quantity');
                $table->decimal('total', 6, 2);
                $table->timestamps();

                $table->foreign('spot_sale_id')->references('id')->on('spot_sales')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('spot_sale_items');
        Schema::dropIfExists('spot_sales');
    }
};
