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
        Schema::create('items', function (Blueprint $table) {
            $table->string('item_unique_name')->primary();
            $table->integer('tier')->nullable();
            $table->float('weight')->nullable();
            $table->integer('max_stack_size')->nullable();
            $table->string('ui_sprite')->nullable();
            $table->string('shop_category')->nullable();
            $table->string('shop_subcategory1')->nullable();
            $table->boolean('unlocked_to_craft')->nullable();
            $table->string('shop_subcategory2')->nullable();
            $table->integer('item_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
