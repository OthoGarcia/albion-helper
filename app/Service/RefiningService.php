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
    public function getMostProfitableRefinement(array $refinements): Collection
    {
        $items = $this->getItemsToRefine();
        $refinements = $this->getRecipeFromItems($items);
        return $refinements;
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
                'city' => $item->itemPrices->first()->city->name ?? '',
            ];
        }
        $keys = array_keys($itemsData);
        $refinements = Recipe::whereIn('item_unique_name', $keys)
            ->with('ingredients')
            ->get();
        foreach ($refinements as $refinement) {
            $itemName = $refinement->item->item_unique_name;
            $itemsData[$itemName]['recipes'][] = $refinement->toArray();
        }

        return collect($itemsData);
    }
}
