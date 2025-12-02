<?php

namespace App\StockStatusBundle\Controller;

use App\StockStatusBundle\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StockStatusController extends AbstractController
{
    private StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    #[Route('/api/stock-status', name: 'api_stock_status', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // Return sample prices for a few symbols
        $symbols = ['AAPL', 'GOOG', 'MSFT'];
        $data = [];

        foreach ($symbols as $s) {
            $data[] = $this->stockService->getCurrentPrice($s);
        }

        return new JsonResponse(['data' => $data]);
    }
}
