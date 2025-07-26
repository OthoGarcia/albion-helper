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
        Schema::create('item_stats', function (Blueprint $table) {
            $table->string('item_unique_name');
            $table->string('stat_name');
            $table->double('stat_value')->nullable();
            $table->primary(['item_unique_name', 'stat_name']);
            $table->foreign('item_unique_name')->references('item_unique_name')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_stats');
    }
};
