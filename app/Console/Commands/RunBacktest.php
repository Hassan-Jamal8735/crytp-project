<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BacktestService;

class RunBacktest extends Command
{
    protected $signature = 'backtest:run {--safe}';
    protected $description = 'Run arbitrage backtest';

    public function handle()
    {
        $backtest = new BacktestService();
        
        $params = [
            'spread_threshold' => $this->option('safe') ? 0.0015 : 0.0020,
            'fee' => 0.0005,
            'min_volume' => 0.5
        ];

        $results = $backtest->analyzeResults(
            $backtest->run($params)
        );

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Trades', number_format($results['total_trades'])],
                ['Profitable Trades', number_format($results['profitable_trades'])],
                ['Total Profit', '$'.number_format($results['total_profit'], 2)],
                ['Win Rate', number_format($results['metrics']['win_rate']*100, 2).'%'],
                ['Sharpe Ratio', number_format($results['sharpe_ratio'], 2)],
                ['Vs HODL', '$'.number_format($results['benchmark']['outperformance'] ?? 0, 2)]
            ]
        );
    }
}