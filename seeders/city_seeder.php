<?php

declare(strict_types=1);

use App\Model\City;
use Hyperf\Database\Seeders\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()   
    {
       $cities = [
            ['name'=>'Lymhurst','type'=>'Royal City','region'=>'Forest Continent', 'refine_type'=>'cloth', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Martlock','type'=>'Royal City','region'=>'Highlands Continent', 'refine_type'=>'leather', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Thetford','type'=>'Royal City','region'=>'Swamp Continent', 'refine_type'=>'metalbars', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Bridgewatch','type'=>'Royal City','region'=>'Steppe Continent', 'refine_type'=>'stoneblock', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Fort Sterling','type'=>'Royal City','region'=>'Mountain Continent', 'refine_type'=>'planks', 'refine_bonus_percentage'=>36.7],
            ['name'=>'Caerleon','type'=>'Faction City','region'=>'Central Royal Continent','notes'=>'Possui Black Market'],
        ];

       foreach ($cities as $city) {
           City::create($city);
       }
    }
}
