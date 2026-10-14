<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 50)->unique();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('meta_title', 150)->nullable();
            $table->string('meta_description', 150)->nullable();
            $table->string('meta_keywords', 150)->nullable();
            $table->string('meta_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('subcategories');
    }
};
