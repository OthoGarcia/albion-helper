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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id('recipe_id');
            $table->string('item_unique_name');
            $table->integer('output_quantity')->default(1);
            $table->integer('crafting_time')->nullable();
            $table->string('recipe_type')->nullable();
            $table->json('conditions')->nullable();
            $table->integer('crafting_focus')->nullable();
            $table->timestamps();
            $table->foreign('item_unique_name')->references('item_unique_name')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
