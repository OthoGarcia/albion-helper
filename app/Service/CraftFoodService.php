<?php

namespace App\Service;

use App\Model\Item;
use App\Model\Recipe;
use Hyperf\Collection\Collection;

use function Hyperf\Collection\collect;

class CraftFoodService
{
    const BASE_TAX = 0.1125; // Tax fee percentage
    const TAX_AMOUNT = 300; // Taxa de imposto
    const BASE_REFINING_RETURN_RATE = 15.2; // Taxa de retorno do refinamento
    /**
     * Retorna o refinamento mais lucrativo para um item.
     *
     * @param array $refinements Lista de refinamentos possíveis. Cada item deve conter 'name', 'cost', 'expected_profit'.
     * @return array|null Refinamento mais lucrativo ou null se lista vazia.
     */
    public function getMostProfitableFoodCrafting(array $queryParams): array
    {
        $cityId = $queryParams['cityId'] ?? null;
        $taxAmount = (int) ($queryParams['taxAmount'] ?? self::TAX_AMOUNT);
        $items = $this->getItemsToRefine();
        $refinements = $this->getRecipeFromItems(
            $items,
            $cityId,
            $taxAmount
        );
        $itemsData = $this->findProfitableRecipes($refinements);
        $itemsData = $this->mapResponse($itemsData);
        return array_values($itemsData) ;
    }

    private function getItemsToRefine(): Collection
    {
        $items = Item::whereIn('shop_subcategory1', ['food', 'potions'])
            ->where('shop_category', '=', 'consumables')
            ->where('shop_subcategory2', '!=', 'event')
            ->with(['itemPrices.city' => function ($query) {
                $query->select('id', 'name', 'refine_type', 'refine_bonus_percentage');
            }])
            ->with(['itemPrices' => function ($query) {
                $query->where('quality', '=', 0)
                    ->where('sell_price_min', '>', 0)
                    ->orderBy('sell_price_min', 'asc');
            }]);

        return $items->get();
    }

    private function getRecipeFromItems(Collection $items, int|null $cityId, int|null $taxAmount): Collection
    {
        $itemsData = [];
        foreach ($items as $item) {
            $sellPriceMin = $item->itemPrices->first()->sell_price_min ?? 0;
            if ($sellPriceMin <= 0) {
                continue; // Skip items with no sell price
            }
            $query = $sellMinPriceByCity = $item->itemPrices();
            if ($cityId) {
                $query
                ->where('city_id', '=', $cityId);
            }
            $sellMinPriceByCity = $query
                ->whereRaw("TIMEDIFF(now(), sell_price_min_date) < '02:00:00'")
                ->orderBy('sell_price_min_date', 'desc')
                ->get();

            $itemsData[$item->item_unique_name] = [
                'id' => $item->item_unique_name,
                'refining_cost_per_unit' => round((($item->item_value * self::BASE_TAX) * $taxAmount) / 100),
                // Encontra o menor preço por cidade
                'refine_type' => $item->shop_subcategory2,
                'sell_price_min_by_city' => $sellMinPriceByCity
                    ->groupBy('city_id')
                    ->map(function ($prices, $cityId) {
                        $minPrice = $prices->min('sell_price_min');
                        $priceEntry = $prices->where('sell_price_min', $minPrice)->first();
                        $lastDate = $priceEntry->sell_price_min_date;
                        $hoursSinceUpdate = $lastDate ? round((time() - strtotime($lastDate)) / 3600, 2) : null;
                        return [
                            'city_id' => $cityId,
                            'city_name' => $priceEntry->city->name ?? '',
                            'city_refine_type' => $priceEntry->city->refine_type ?? '',
                            'city_refine_bonus_percentage' => $priceEntry->city->refine_bonus_percentage,
                            'sell_price_min' => $minPrice,
                            'last_sell_price_min_date' => $lastDate,
                            'sell_price_min_hours_since_update' => $hoursSinceUpdate,
                        ];
                    })->values()->toArray(),
            ];
        }
        $keys = array_keys($itemsData);
        $refinements = Recipe::whereIn('item_unique_name', $keys)
            ->with(['ingredients.item.itemPrices' => function ($query) use ($cityId) {
                if ($cityId) {
                    $query->where('city_id', '=', $cityId);
                }
                $query
                    ->where('quality', '=', 0)
                    ->where('sell_price_min', '>', 0)
                    ->whereRaw("TIMEDIFF(now(), sell_price_min_date) < '02:00:00'")
                    ->orderBy('sell_price_min_date', 'desc');
            }])
            ->get();
        foreach ($refinements as $refinement) {
            $itemName = $refinement->item->item_unique_name;
            // Para cada cidade do item, cria uma receita específica
            foreach ($itemsData[$itemName]['sell_price_min_by_city'] as $cityData) {
                $cityId = $cityData['city_id'];
                $cityName = $cityData['city_name'];
                // Ingredientes filtrados pelo preço mínimo na mesma cidade
                $refinementIngredients = $refinement->ingredients->map(function ($ingredient) use ($cityId) {
                    // Filtra preços do ingrediente apenas para a cidade atual
                    $cheapestIngredient = $ingredient->item->itemPrices->first();
                    $ingredient->item->min_price = $cheapestIngredient ? $cheapestIngredient->sell_price_min : 0;
                    $ingredient->item->last_min_price_date = $cheapestIngredient ? $cheapestIngredient->sell_price_min_date : '';
                    $ingredient->item->city_id = $cheapestIngredient ? $cheapestIngredient->city_id : $cityId;
                    $lastDate = $ingredient->item->last_min_price_date;
                    $ingredient->item->min_price_hours_since_update = $lastDate ? round((time() - strtotime($lastDate)) / 3600, 2) : null;
                    return $ingredient;
                });
                // Adiciona receita específica para a cidade
                $recipeArray = $refinement->toArray();
                $recipeArray['ingredients'] = $refinementIngredients->toArray();
                $recipeArray['city_id'] = $cityId;
                $recipeArray['city_name'] = $cityName;
                $itemsData[$itemName]['recipes'][] = $recipeArray;
            }
        }
        return collect($itemsData);
    }


    private function findProfitableRecipes(Collection $items): ?Collection
    {
        return $items->map(function ($item) {
            $cityProfits = [];
            foreach ($item['sell_price_min_by_city'] as $cityData) {
                $cityId = $cityData['city_id'];
                $cityName = $cityData['city_name'];
                $sellPriceMin = $cityData['sell_price_min'];
                $minTotalCost = null;
                $bestRecipe = null;

                if ($item['refine_type'] === $cityData['city_refine_type']) {
                    $refining_bonus = (float) $cityData['city_refine_bonus_percentage'];
                }
                else {
                    $refining_bonus = self::BASE_REFINING_RETURN_RATE;
                }

                // Filtra receitas da cidade
                $recipesForCity = collect($item['recipes'])->where('city_id', $cityId);
                foreach ($recipesForCity as $recipe) {
                    // Verifica se todos os ingredientes têm min_price > 0
                    $allIngredientsHavePrice = collect($recipe['ingredients'])->every(function ($ingredient) {
                        return isset($ingredient['item']['min_price']) && $ingredient['item']['min_price'] > 0;
                    });

                    if (!$allIngredientsHavePrice) {
                        continue; // pula receitas com ingredientes sem preço
                    }

                    $totalCost = collect($recipe['ingredients'])->reduce(function ($carry, $ingredient) {
                        return $carry + ($ingredient['item']['min_price'] * $ingredient['quantity']);
                    }, 0);
                    $totalReturn = collect($recipe['ingredients'])->reduce(function ($carry, $ingredient) use ($refining_bonus, $recipe) {
                        return $carry + ($ingredient['quantity'] * $ingredient['item']['min_price'] * ($refining_bonus/100));
                    }, 0);
                    $totalReturn = round($totalReturn, 0);
                    $totalCost += $item['refining_cost_per_unit'];
                    $cost = $totalCost - $totalReturn;
                    if ($minTotalCost === null || $cost < $minTotalCost) {
                        $minTotalCost = $cost;
                        $bestRecipe = $recipe;
                        $bestRecipe['total_cost'] = $totalCost;
                        $bestRecipe['return_value'] = $totalReturn;
                        $bestRecipe['cost'] = $cost;
                    }
                }

                if ($bestRecipe) {
                    $outputQuantity = (int)$bestRecipe['output_quantity'];
                    $expectedProfit = ($sellPriceMin * $outputQuantity) - $minTotalCost;
                    $expectedProfit = $expectedProfit - ($minTotalCost * 0.08); // Deduzir 8% de taxa de venda
                    $expectedProfit = $expectedProfit - ($minTotalCost * 0.025); // Deduzir 2.5% de taxa de venda
                    $expectedProfit = round($expectedProfit, 0);
                    $cityProfits[] = [
                        'id' => $item['id'],
                        'refining_cost_per_unit' => $item['refining_cost_per_unit'],
                        'sell_price_min' => $sellPriceMin,
                        'last_sell_price_min_date' => $cityData['last_sell_price_min_date'],
                        'city' => $cityName,
                        'expected_profit' => $expectedProfit,
                        'recipes' => [$bestRecipe],
                    ];
                }
            }
            return $cityProfits;
        })
            ->flatten(1)
            ->filter(function ($item) {
                return $item['expected_profit'] != 0 && !empty($item['recipes']);
            })
            ->sortByDesc('expected_profit');
    }

    private function mapResponse(Collection $items): array
    {
        return $items->map(function ($item) {   
            return [
                'id' => $item['id'],
                'sell_price_min' => $item['sell_price_min'],
                'refining_cost_per_unit' => $item['refining_cost_per_unit'] ?? 0,
                'last_sell_price_min_date' => $item['last_sell_price_min_date'],
                'sell_price_min_hours_since_update' => $item['sell_price_min_hours_since_update'] ?? null,
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
                        'return_value' => $recipe['return_value'],
                        'cost' => $recipe['cost'],
                        'ingredients' => collect($recipe['ingredients'])->map(function ($ingredient) {
                            return [
                                'ingredient_item_unique_name' => $ingredient['ingredient_item_unique_name'],
                                'quantity' => $ingredient['quantity'],
                                'enchantment_level' => $ingredient['enchantment_level'],
                                'min_price' => $ingredient['item']['min_price'] ?? 0,
                                'last_min_price_date' => $ingredient['item']['last_min_price_date'] ?? '',
                                'city' => $ingredient['item']['city_id'] ?? null,
                                'min_price_hours_since_update' => $ingredient['item']['min_price_hours_since_update'] ?? null,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
