<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoricalPrice;

class TestPrediction extends Command
{
    protected $signature = 'test:predictions';
    protected $description = 'Test prediction accuracy';

    public function handle()
    {
        // Get all records with predictions
        $predictions = HistoricalPrice::whereNotNull('predicted_high')
            ->orderBy('timestamp')
            ->get();

        if ($predictions->isEmpty()) {
            $this->error("No predictions found in database!");
            return;
        }

        // Calculate accuracy
        $total = $predictions->count();
        $correct = $predictions->filter(function($item) {
            return $item->binance >= $item->predicted_low && 
                   $item->binance <= $item->predicted_high;
        })->count();

        $accuracy = ($total > 0) ? ($correct / $total) * 100 : 0;

        // Action-specific accuracy
        $actions = ['BUY' => 0, 'SELL' => 0, 'HOLD' => 0];
        $actionTotals = ['BUY' => 0, 'SELL' => 0, 'HOLD' => 0];

        foreach ($predictions as $pred) {
            if (isset($actions[$pred->ai_action])) {
                $actionTotals[$pred->ai_action]++;
                if ($pred->binance >= $pred->predicted_low && 
                    $pred->binance <= $pred->predicted_high) {
                    $actions[$pred->ai_action]++;
                }
            }
        }

        // Display results
        $this->info("\n=== PREDICTION ACCURACY ===");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Predictions', $total],
                ['Correct Predictions', $correct],
                ['Overall Accuracy', round($accuracy, 2).'%']
            ]
        );

        $this->info("\n=== ACTION ACCURACY ===");
        $this->table(
            ['Action', 'Correct', 'Total', 'Accuracy'],
            array_map(function($action) use ($actions, $actionTotals) {
                $acc = ($actionTotals[$action] > 0) 
                    ? round(($actions[$action]/$actionTotals[$action])*100, 2)
                    : 0;
                return [$action, $actions[$action], $actionTotals[$action], $acc.'%'];
            }, array_keys($actions))
        );

        // Show sample predictions
        $this->info("\n=== SAMPLE PREDICTIONS ===");
        $this->table(
            ['Time', 'Price', 'Predicted Range', 'Action', 'Correct?'],
            $predictions->take(5)->map(function($item) {
                return [
                    $item->timestamp->format('H:i'),
                    number_format($item->binance, 2),
                    number_format($item->predicted_low, 2).'-'.number_format($item->predicted_high, 2),
                    $item->ai_action,
                    ($item->binance >= $item->predicted_low && $item->binance <= $item->predicted_high) ? '✅' : '❌'
                ];
            })
        );
    }
}