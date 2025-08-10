<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\HistoricalPrice;
use App\Models\BacktestHistory;
use App\Services\BacktestService;


class SimulatePriceMovement extends Component
{
    // In app/Console/Commands/SimulatePriceMovement.php
public function handle()
{
    $currentPrice = 50000; // Starting price
    
    while (true) {
        $change = rand(-50, 50); // Random price movement
        $currentPrice += $change;
        
        HistoricalPrice::create([
            'timestamp' => now(),
            'pair' => 'BTC/USD',
            'binance' => $currentPrice,
            'coinbase' => $currentPrice + rand(-10, 10),
            'high' => $currentPrice + abs($change),
            'low' => $currentPrice - abs($change),
            'spread' => rand(5, 15),
            'volume' => rand(1, 100)
        ]);
        
        sleep(10); // Add new price every 10 seconds
    }
}
}