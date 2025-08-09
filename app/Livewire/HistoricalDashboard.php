<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class HistoricalDashboard extends Component
{
    public $pair = 'BTC/USDT';
    public $days = 1;

    public function render()
    {
        $data = DB::table('historical_prices')
            ->where('pair', $this->pair)
            ->where('timestamp', '>=', now()->subDays($this->days))
            ->orderBy('timestamp')
            ->get();

        return view('livewire.historical-dashboard', [
            'timestamps' => $data->pluck('timestamp')->map(fn($t) => date('H:i', strtotime($t))),
            'binancePrices' => $data->pluck('binance'),
            'coinbasePrices' => $data->pluck('coinbase'),
            'maxSpread' => round($data->max('spread'), 2),
            'avgSpread' => round($data->avg('spread'), 2),
            'opportunities' => $data->where('spread', '>', 0.15)->count()
        ]);
    }
}