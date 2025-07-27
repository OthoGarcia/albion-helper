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
            ['name'=>'Lymhurst','type'=>'Royal City','region'=>'Forest Continent'],
            ['name'=>'Bridgewatch','type'=>'Royal City','region'=>'Steppe Continent'],
            ['name'=>'Fort Sterling','type'=>'Royal City','region'=>'Mountain Continent'],
            ['name'=>'Martlock','type'=>'Royal City','region'=>'Highlands Continent'],
            ['name'=>'Thetford','type'=>'Royal City','region'=>'Swamp Continent'],
            ['name'=>'Caerleon','type'=>'Faction City','region'=>'Central Royal Continent','notes'=>'Possui Black Market'],
        ];

       foreach ($cities as $city) {
           City::create($city);
       }
    }
}
