<?php

namespace App\Http\Controllers;

use App\Services\ArbitrageService;
use Illuminate\Http\Request;
use App\Models\HistoricalPrice;

class ArbitrageController extends Controller
{
    public function historical(Request $request)
    {
        $start = $request->start_date ?? '2000-01-01';
        $end = $request->end_date ?? now()->toDateString();

        $opportunities = HistoricalPrice::whereBetween('timestamp', [$start, $end])
            ->get()
            ->map(function ($item) {
                $analysis = (new ArbitrageService)->analyze($item->spread);

                // Dynamically attach analysis properties to the Eloquent model
                foreach ($analysis as $key => $value) {
                    $item->$key = $value;
                }

                return $item;
            });

        return view('arbitrage.partials.results', compact('opportunities'));
    }
}
