<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->unsignedBigInteger('recipe_id');
            $table->string('ingredient_item_unique_name');
            $table->string('enchantment_level')->nullable();
            $table->integer('quantity')->default(1);
            $table->foreign('recipe_id')->references('recipe_id')->on('recipes')->onDelete('cascade');
            $table->foreign('ingredient_item_unique_name')->references('item_unique_name')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
