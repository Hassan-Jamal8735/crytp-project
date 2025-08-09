<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BacktestService
{
    public function run(array $params): Collection
    {
        return DB::table('historical_prices')
            ->whereRaw('(coinbase - binance)/binance > ?', [$params['spread_threshold']])
            ->where('volume', '>', $params['min_volume'])
            ->orderBy('timestamp')
            ->cursor()
            ->map(function ($trade) use ($params) {
                $actualSpread = ($trade->coinbase - $trade->binance) / $trade->binance;
                $netProfit = $this->calculateNetProfit(
                    $trade->binance,
                    $actualSpread,
                    $params['fee'],
                    $trade->volume
                );

                return [
                    'timestamp' => $trade->timestamp,
                    'profit' => $netProfit,
                    'action' => $netProfit > 0 ? 'EXECUTE' : 'SKIP',
                    'price' => $trade->binance,
                    'spread' => $actualSpread * 100
                ];
            })
            ->filter(fn($trade) => $trade['action'] === 'EXECUTE') // Only count executed trades
            ->collect();
    }

    public function analyzeResults(iterable $trades, string $pair = 'BTC/USDT'): array
    {
        $trades = collect($trades);
        if ($trades->isEmpty()) {
            return [
                'error' => 'No valid trades executed',
                'parameters' => [
                    'min_spread' => 0.15,
                    'min_volume' => 0.5,
                    'fee' => 0.05
                ]
            ];
        }

        $profits = $trades->pluck('profit')->toArray();
        $positiveProfits = array_filter($profits, fn($p) => $p > 0);
        $negativeProfits = array_filter($profits, fn($p) => $p < 0);

        $stats = [
            'total_trades' => count($profits),
            'profitable_trades' => count($positiveProfits),
            'total_profit' => array_sum($profits),
            'max_drawdown' => min($profits + [0]),
            'balance' => 10000 + array_sum($profits),
            'sharpe_ratio' => $this->calculateSharpeRatio($profits),
            'sortino_ratio' => $this->calculateSortinoRatio($profits),
            'benchmark' => $this->calculateBenchmark($trades, $pair),
            'metrics' => [
                'avg_profit' => $this->safeDivide(array_sum($profits), count($profits)),
                'win_rate' => $this->safeDivide(count($positiveProfits), count($profits)),
                'profit_factor' => $this->safeDivide(
                    array_sum($positiveProfits), 
                    abs(array_sum($negativeProfits))
                ),
                'avg_trade_duration' => $this->calculateAvgDuration($trades)
            ]
        ];

        return $stats;
    }

    protected function calculateNetProfit(float $price, float $spread, float $fee, float $volume): float
{
    // More realistic slippage model (0.1% base + 0.08% per BTC)
    $slippage = min(0.004, 0.001 + ($volume * 0.0008));
    
    // Net profit percentage after all costs
    $netProfitPercent = $spread - $fee - $slippage;
    
    // Only require 0.05% minimum profit (more opportunities)
    if ($netProfitPercent >= 0.0005) {
        // 90% execution probability
        return (rand(1, 10) <= 9 ? $price * $netProfitPercent : 0);
    }
    
    return 0;
}

    protected function calculateBenchmark(Collection $trades, string $pair): array
    {
        $timestamps = $trades->pluck('timestamp');
        if ($timestamps->isEmpty()) return [];

        $prices = DB::table('historical_prices')
            ->where('pair', $pair)
            ->whereBetween('timestamp', [$timestamps->first(), $timestamps->last()])
            ->orderBy('timestamp')
            ->pluck('binance');

        if ($prices->count() < 2) return [];

        $hodlReturn = ($prices->last() - $prices->first()) / $prices->first();
        
        return [
            'return_percent' => round($hodlReturn * 100, 4),
            'return_usd' => round($hodlReturn * 10000, 2),
            'outperformance' => round($trades->sum('profit') - ($hodlReturn * 10000), 2)
        ];
    }

    protected function calculateSharpeRatio(array $returns): float
    {
        if (count($returns) < 2) return 0;
        
        $avg = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(fn($x) => pow($x - $avg, 2), $returns)) / count($returns);
        $stdDev = sqrt($variance);
        
        return $stdDev != 0 ? $avg / $stdDev : 0;
    }

    protected function calculateSortinoRatio(array $returns): float
    {
        if (count($returns) < 2) return 0;
        
        $avg = array_sum($returns) / count($returns);
        $downsideReturns = array_filter($returns, fn($x) => $x < 0);
        
        if (empty($downsideReturns)) return 100; // Perfect case
        
        $downsideVariance = array_sum(array_map(fn($x) => pow($x, 2), $downsideReturns)) / count($downsideReturns);
        $downsideDev = sqrt($downsideVariance);
        
        return $downsideDev != 0 ? $avg / $downsideDev : 0;
    }

    protected function calculateAvgDuration(Collection $trades): float
    {
        if ($trades->count() < 2) return 0;
        
        $first = strtotime($trades->first()['timestamp']);
        $last = strtotime($trades->last()['timestamp']);
        
        return round(($last - $first) / $trades->count() / 60, 2); // In minutes
    }

    protected function safeDivide(float $numerator, float $denominator): float
    {
        return $denominator != 0 ? $numerator / $denominator : 0;
    }
} 