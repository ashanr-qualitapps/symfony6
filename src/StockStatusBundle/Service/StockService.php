<?php

namespace App\StockStatusBundle\Service;

class StockService
{
    /**
     * Return a simple stock price payload for given symbol.
     * In a real implementation this would call an API or a data source.
     *
     * @return array{symbol:string,price:float,currency:string,timestamp:int}
     */
    public function getCurrentPrice(string $symbol): array
    {
        // sample data — replace with real provider
        return [
            'symbol' => strtoupper($symbol),
            'price' => round(100 * (1 + lcg_value()), 2),
            'currency' => 'USD',
            'timestamp' => time(),
        ];
    }
}
