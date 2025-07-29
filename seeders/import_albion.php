<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use App\Model\Item;
use App\Model\ItemStat;
use App\Model\Recipe;
use App\Model\RecipeIngredient;
use App\Model\RecipeSkill;
use League\Flysystem\Filesystem;

class ImportAlbion extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $client = new GuzzleHttp\Client();
        $response = $client->get('https://raw.githubusercontent.com/ao-data/ao-bin-dumps/refs/heads/master/items.json');
        $json = $response->getBody()->getContents();
        $all = json_decode($json, true)['items'];
        $arrayTypes = ['simpleitem', 'consumableitem', 'hideoutitem', 'consumablefrominventoryitem', 'farmableitem', 'rewardtoken', 'equipmentitem'];
        $objectTypes = ['hideoutitem', 'rewardtoken'];
        $total = 0;
        foreach ($arrayTypes as $type) {
            $total += count($all[$type]);
        }
        echo "Total items to import: $total\n";
        $progress = 0;
        foreach ($arrayTypes as $type) {
            if (in_array($type, $objectTypes)) {
                $progress ++;
                echo "Progress creating all items: $progress / $total\n";
                $this->getItemByUniqueName($all[$type]);
            } else {
                foreach ($all[$type] as $key => $itm) {
                    $progress ++;
                    echo "Progress creating all items: $progress / $total\n";
                    $item = $this->getItemByUniqueName($itm);
                }
            }
        }
        echo "Building dependencies...\n";
        $progress = 0;
        foreach ($arrayTypes as $type) {
            
            if (in_array($type, $objectTypes)) {
                $progress ++;
                echo "Progress creating all items: $progress / $total\n";
                $item = $this->getItemByUniqueName($all[$type]);
                $this->buildDependencies($item, $all[$type]);
            } else {
                foreach ($all[$type] as $key => $itm) {
                    $progress ++;
                    echo "Progress building dependencies for $type: $progress / $total\n";
                    $item = $this->getItemByUniqueName($itm);
                    $this->buildDependencies($item, $itm);
                }
            }
            echo "Progress building dependencies for $type: $progress / $total\n";
        }
    }

    private function getItemByUniqueName(array $itm): ?Item
    {
        try {
            $item = Item::firstOrCreate(
                ['item_unique_name' => $itm['@uniquename']],
                [
                    'tier' => $itm['@tier'] ?? null,
                    'weight' => $itm['@weight'] ?? null,
                    'max_stack_size' => $itm['@maxstacksize'] ?? null,
                    'ui_sprite' => $itm['@uisprite'] ?? null,
                    'shop_category' => $itm['@shopcategory'] ?? null,
                    'shop_subcategory1' => $itm['@shopsubcategory1'] ?? null,
                    'unlocked_to_craft' => isset($itm['@unlockedtocraft']) ? boolval($itm['@unlockedtocraft']) : null,
                    'item_value' => $itm['@itemvalue'] ?? null,
                    'shop_subcategory2' => $itm['@shopsubcategory2'] ?? null,
                ]
            );
            return $item;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function buildDependencies(Item $item, array $itm): void
    {
       
        try {
            if (isset($itm['Stats']) && is_array($itm['Stats'])) {
                foreach ($itm['Stats'] as $stat) {
                    ItemStat::firstOrCreate(
                        ['item_unique_name' => $item->item_unique_name, 'stat_name' => $stat['Name']],
                        ['stat_value' => $stat['Value']]
                    );
                }
            }
            if (isset($itm['craftingrequirements']) && is_array($itm['craftingrequirements'])) {
                foreach ($itm['craftingrequirements'] as $rec) {
                    $recipe = Recipe::create([
                        'item_unique_name' => $item->item_unique_name,
                        'output_quantity' => $rec['@amountcrafted'] ?? $itm['craftingrequirements']['@amountcrafted'] ?? 1,
                        'crafting_time' => $rec['@time'] ?? $itm['craftingrequirements']['@time'] ?? null,
                        'recipe_type' => $rec['Type'] ?? $itm['craftingrequirements']['Type'] ?? null,
                        'conditions' => $rec['Conditions'] ?? $itm['craftingrequirements']['Conditions'] ?? null,
                        'crafting_focus' => $rec['@craftingfocus'] ?? $itm['craftingrequirements']['@craftingfocus'] ?? null
                    ]);
                    if (empty($rec['craftresource'])) {
                        $this->buildRecipeIngredients($recipe, $itm['craftingrequirements']['craftresource'] ?? []);
                        break;
                    } else {    
                        $this->buildRecipeIngredients($recipe, $rec['craftresource'] ?? []);
                    }
                }
            }
                    
        } catch (\Throwable $th) {
            var_dump($th->getTraceAsString());
            var_dump($item->toArray(), $itm);
            throw $th;
        }
    }

    private function buildRecipeIngredients(Recipe $recipe, array $ingredients): void
    {
        if (empty($ingredients)) {
            return;
        }
        $recipesIng = [];
        if (array_key_exists('@uniquename', $ingredients)) {
            $recipesIng[] = RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_item_unique_name' => $ingredients['@uniquename'],
                'quantity' => $ingredients['@count'] ?? 1,
                'enchantment_level' => $ingredients['@enchantmentlevel'] ?? null
            ]);
            return;
        }
        foreach ($ingredients as $ing) {
            $recipesIng[] =RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_item_unique_name' => $ing['@uniquename'],
                'quantity' => $ing['@count'] ?? 1,
                'enchantment_level' => $ing['@enchantmentlevel'] ?? null
            ]);
        }
    }
}
