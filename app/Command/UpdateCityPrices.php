<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\City;
use GuzzleHttp\Client;
use Hyperf\Collection\Collection;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

use function Hyperf\Collection\collect;

#[Command]
class UpdateCityPrices extends HyperfCommand
{
    private const CITY_PRICES_URL = 'https://west.albion-online-data.com/api/v2/stats/prices/';
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('update:city-prices');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Update city prices command');
    }

    public function handle()
    {
        $this->line('Updating city prices...', 'info');
        $updates = [
            'items' => $this->getRefinementsItems(),
            'food' => $this->getCraftFoodItems()
        ];
        $cities = $this->getCities();

        $client = new Client();
    
        foreach ($updates as $key => $update) {
            $response = $client->get(
                uri: self::CITY_PRICES_URL . implode(',', $update),
                options: [
                    'timeout' => 10,
                    'query' => [
                        'locations' => $cities->pluck('name')->implode(','),
                        'qualities' => '1',
                    ]
                ]
            );
            $data = json_decode($response->getBody()->getContents(), true);
            $this->processCityPrices($data);
        }
        
    }

    private function getRefinementsItems(): array
    {
        $items = Db::select("select DISTINCT 
            ri.ingredient_item_unique_name as item_unique_name
            from items i 
            join recipes r on r.item_unique_name = i.item_unique_name
            join recipe_ingredients ri on ri.recipe_id  = r.recipe_id 
            where shop_subcategory1 = 'refinedresources'
            UNION 
            select i2.item_unique_name from items i2 where i2.shop_subcategory1 = 'refinedresources'"
        );
        return collect($items)->pluck('item_unique_name')->toArray();
    }

    private function getCraftFoodItems(): array
    {
        $items = Db::select("select DISTINCT 
            ri.ingredient_item_unique_name as item_unique_name
            from items i 
            join recipes r on r.item_unique_name = i.item_unique_name
            join recipe_ingredients ri on ri.recipe_id  = r.recipe_id 
            where shop_category = 'consumables'
            UNION 
            select i2.item_unique_name from items i2 where i2.shop_category = 'consumables'"
        );
        return collect($items)->pluck('item_unique_name')->toArray();
    }

    private function getCities(): Collection
    {
        $cityPrices = City::select('name')->get();

        return $cityPrices;
    }

    private function processCityPrices(array $data): void
    {
        $cities = City::all()->keyBy('name');
        foreach ($data as $item) {
            $city = $cities->get($item['city']);
        
            if ($city) {
                $cityItemPrice = $city->itemPrices()->updateOrCreate(
                    [
                        'item_unique_name' => $item['item_id'],
                        'sell_price_min' => $item['sell_price_min'],
                        'sell_price_min_date' => $this->getBaseDate($item['sell_price_min_date']),
                    ],
                    [
                        'sell_price_min' => $item['sell_price_min'],
                        'sell_price_min_date' => $this->getBaseDate($item['sell_price_min_date']),
                        'sell_price_max' => $item['sell_price_max'],
                        'sell_price_max_date' => $this->getBaseDate($item['sell_price_max_date']),
                        'buy_price_min' => $item['buy_price_min'],
                        'buy_price_min_date' => $this->getBaseDate($item['buy_price_min_date']),
                        'buy_price_max' => $item['buy_price_max'],
                        'buy_price_max_date' => $this->getBaseDate($item['buy_price_max_date']),
                    ]
                );
                $this->line("Updated price for item {$item['item_id']} in city {$city->name}", 'info');
            }
        }
    }

    private function getBaseDate(string $itemDate): string
    {
        // Assuming item_id is the unique name for the item
        if (empty($itemDate) || $itemDate == '0001-01-01T00:00:00') {
            $itemDate = '1970-01-01 00:00:01'; // Default date if no date is provided
        }
        return date('Y-m-d H:i:s', strtotime($itemDate));
    }
}
