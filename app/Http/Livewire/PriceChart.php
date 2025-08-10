<?php
namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\HistoricalPrice;

class PriceChart extends Component
{
    public $timeframe = '15m';
    public $pair = 'BTC/USDT'; // Changed from symbol to pair
    
    protected $listeners = ['refreshChart' => '$refresh'];

    public function updatedTimeframe()
    {
        $this->emit('chartUpdated');
    }

    public function getChartDataProperty()
    {
        return HistoricalPrice::query()
            ->where('pair', $this->pair) // Changed to use 'pair' column
            ->orderBy('timestamp', 'asc')
            ->limit(500)
            ->get()
            ->map(function ($item) {
                return [
                    'time' => $item->timestamp->getTimestamp(),
                    'open' => (float)$item->binance, // Using binance price as open
                    'high' => (float)$item->high,
                    'low' => (float)$item->low,
                    'close' => (float)$item->coinbase // Using coinbase price as close
                ];
            })
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.price-chart', [
            'chartData' => $this->chartData,
            'latestPrediction' => HistoricalPrice::where('pair', $this->pair)
                ->whereNotNull('ai_action')
                ->latest()
                ->first()
        ]);
    }
}