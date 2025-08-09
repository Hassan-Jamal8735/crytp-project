<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HistoricalPrice;

class HistoricalPricesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        HistoricalPrice::create([
            'timestamp' => '2023-03-12 14:00:00',
            'pair' => 'BTC/USDT',
            'binance' => 50123.45,
            'coinbase' => 50257.89,
            'spread' => 0.87
        ]);
    }
}
