<?php

namespace App\Service;

use App\Model\Item;
use App\Model\Recipe;
use Hyperf\Collection\Collection;

use function Hyperf\Collection\collect;

class RefiningService
{
    /**
     * Retorna o refinamento mais lucrativo para um item.
     *
     * @param array $refinements Lista de refinamentos possíveis. Cada item deve conter 'name', 'cost', 'expected_profit'.
     * @return array|null Refinamento mais lucrativo ou null se lista vazia.
     */
    public function getMostProfitableRefinement(array $refinements): array
    {
        $items = $this->getItemsToRefine();
        $refinements = $this->getRecipeFromItems($items);
        $itemsData = $this->findProfitableRecipes($refinements);
        $itemsData = $this->mapResponse($itemsData);
        return $itemsData;
    }

    private function getItemsToRefine(): Collection
    {
        $items = Item::where('shop_subcategory1', '=', 'refinedresources')
            ->where('item_unique_name', 'LIKE', '%Cloth%')
            ->with(['itemPrices.city' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['itemPrices' => function ($query) {
                $query->where('quality', '=', 0)
                    ->where('sell_price_min', '>', 0)
                    ->orderBy('sell_price_min', 'asc');
            }]);

        return $items->get();
    }

    private function getRecipeFromItems(Collection $items): Collection
    {
        $itemsData = [];
        foreach ($items as $item) {
            $sellPriceMin = $item->itemPrices->first()->sell_price_min ?? 0;
            if ($sellPriceMin <= 0) {
                continue; // Skip items with no sell price
            }
            $itemsData[$item->item_unique_name] = [
                'id' => $item->item_unique_name,
                'sell_price_min' => $sellPriceMin,
                'last_sell_price_min_date' => $item->itemPrices->first()->sell_price_min_date ?? '',
                'city' => $item->itemPrices->first()->city->name ?? '',
            ];
        }
        $keys = array_keys($itemsData);
        $refinements = Recipe::whereIn('item_unique_name', $keys)
            ->with(['ingredients.item.itemPrices' => function ($query) {
                $query->where('quality', '=', 0)
                    ->where('sell_price_min', '>', 0)
                    ->orderBy('sell_price_min', 'asc');
            }])
            ->get();
        foreach ($refinements as $refinement) {
            $itemName = $refinement->item->item_unique_name;
            $refinement->ingredients = $refinement->ingredients->map(function ($ingredient) {
                $cheapestIngredient = $ingredient->item->itemPrices->first();
                $ingredient->item->min_price = $cheapestIngredient->sell_price_min ?? 0;
                $ingredient->item->last_min_price_date = $cheapestIngredient->sell_price_min_date ?? '';
                $ingredient->item->city_id = $cheapestIngredient->city_id ?? '';
                return $ingredient;
            });
            $itemsData[$itemName]['recipes'][] = $refinement->toArray();
        }
        return collect($itemsData);
    }

    private function findProfitableRecipes(Collection $items): ?Collection
    {
        return $items->map(function ($item) {
            $minTotalCost = 0;

            $item['recipes'] = collect($item['recipes'])->map(function ($recipe) use (&$minTotalCost) {
                $totalCost = collect($recipe['ingredients'])->reduce(function ($carry, $ingredient) {
                    return $carry + ($ingredient['item']['min_price'] * $ingredient['quantity']);
                }, 0);

                if ($minTotalCost === 0 || $totalCost < $minTotalCost) {
                    $minTotalCost = $totalCost;
                }
                $recipe['total_cost'] = $totalCost;
                return $recipe;
            })->toArray();
            $expectedProfit = $item['sell_price_min'] - $minTotalCost;
            $item['expected_profit'] = $expectedProfit;
            return $item;
        });
    }

    private function mapResponse(Collection $items): array
    {
        return $items->map(function ($item) {
            return [
                'id' => $item['id'],
                'sell_price_min' => $item['sell_price_min'],
                'last_sell_price_min_date' => $item['last_sell_price_min_date'],
                'city' => $item['city'],
                'expected_profit' => $item['expected_profit'],
                'recipes' => collect($item['recipes'])->map(function ($recipe) {
                    return [
                        'recipe_id' => $recipe['recipe_id'],
                        'item_unique_name' => $recipe['item_unique_name'],
                        'output_quantity' => $recipe['output_quantity'],
                        'crafting_time' => $recipe['crafting_time'],
                        'recipe_type' => $recipe['recipe_type'],
                        'conditions' => $recipe['conditions'],
                        'crafting_focus' => $recipe['crafting_focus'],
                        'total_cost' => $recipe['total_cost'],
                        'ingredients' => collect($recipe['ingredients'])->map(function ($ingredient) {
                            return [
                                'ingredient_item_unique_name' => $ingredient['ingredient_item_unique_name'],
                                'quantity' => $ingredient['quantity'],
                                'enchantment_level' => $ingredient['enchantment_level'],
                                'min_price' => $ingredient['item']['min_price'] ?? 0,
                                'last_min_price_date' => $ingredient['item']['last_min_price_date'] ?? '',
                                'city' => $ingredient['item']['city_id'] ?? null,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
