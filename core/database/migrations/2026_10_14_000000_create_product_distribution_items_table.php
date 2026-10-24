<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header table already exists in production; keep idempotent for fresh installs.
        if (! Schema::hasTable('product_distributions')) {
            Schema::create('product_distributions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('note', 255)->nullable();
                $table->enum('status', ['pending', 'delivered'])->default('pending');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_distribution_items')) {
            Schema::create('product_distribution_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('product_distribution_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->integer('quantity');
                $table->timestamps();

                $table->foreign('product_distribution_id')->references('id')->on('product_distributions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_distribution_items');
        // Do not drop header here to avoid data loss on rollback of fresh installs.
    }
};
