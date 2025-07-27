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
        Schema::create('city_item_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('item_unique_name');
            $table->integer('quality')->default(0);
            $table->integer('sell_price_min')->nullable();
            $table->timestamp('sell_price_min_date')->nullable();
            $table->integer('sell_price_max')->nullable();
            $table->timestamp('sell_price_max_date')->nullable();
            $table->integer('buy_price_min')->nullable();
            $table->timestamp('buy_price_min_date')->nullable();
            $table->integer('buy_price_max')->nullable();
            $table->timestamp('buy_price_max_date')->nullable();
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('cities')->onDelete('cascade');
            $table->foreign('item_unique_name')->references('item_unique_name')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('city_item_prices');
    }
};
