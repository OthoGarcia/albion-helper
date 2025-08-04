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
        Schema::table('cities', function (Blueprint $table) {
            $table->string('refine_type')->nullable();
            $table->decimal('refine_bonus_percentage', 5, 2)->default(0);
        });

        $cities = [
            ['name'=>'Lymhurst','type'=>'Royal City','region'=>'Forest Continent', 'refine_type'=>'cloth', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Martlock','type'=>'Royal City','region'=>'Highlands Continent', 'refine_type'=>'leather', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Thetford','type'=>'Royal City','region'=>'Swamp Continent', 'refine_type'=>'metalbars', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Bridgewatch','type'=>'Royal City','region'=>'Steppe Continent', 'refine_type'=>'stoneblock', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Fort Sterling','type'=>'Royal City','region'=>'Mountain Continent', 'refine_type'=>'planks', 'refine_bonus_percentage'=>36.7],
        ];
        foreach ($cities as $city) {
            \App\Model\City::updateOrCreate(
                ['name' => $city['name']],
                [
                    'refine_type' => $city['refine_type'],
                    'refine_bonus_percentage' => $city['refine_bonus_percentage']
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('refine_type');
            $table->dropColumn('refine_bonus_percentage');
        });
    }
};
