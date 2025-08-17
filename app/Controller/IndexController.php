<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use App\Service\CraftFoodService;
use App\Service\RefiningService;
use Hyperf\HttpServer\Contract\RequestInterface;

class IndexController extends AbstractController
{
    public function refinements(RequestInterface $request, RefiningService $refiningService)
    {
        $requestData = $request->all();
        $items = $refiningService->getMostProfitableRefinement($requestData);
        return $this->response->json($items);
    }

    public function craftFood(RequestInterface $request, CraftFoodService $craftFoodService)
    {
        $requestData = $request->all();
        $items = $craftFoodService->getMostProfitableFoodCrafting($requestData);
        return $this->response->json($items);
    }
}
