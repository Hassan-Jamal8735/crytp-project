<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\HistoricalPrice;
use Illuminate\Support\Facades\Http;

class FetchPrices extends Command
{
    protected $signature = 'fetch:prices';
    protected $description = 'Fetch BTC/USD prices from exchanges';

  public function handle()
{
    try {
        // 1. Fetch prices using the new macros
        $binancePrice = Http::binance()
            ->get('/ticker/price?symbol=BTCUSDT')
            ->json()['price'];
            
        $coinbasePrice = Http::coinbase()
            ->get('/prices/BTC-USD/spot')
            ->json()['data']['amount'];
            
        // 2. Get OHLC data
        $ohlcResponse = Http::binance()
            ->get('/klines?symbol=BTCUSDT&interval=1m&limit=1')
            ->json();
            
        $ohlc = $ohlcResponse[0] ?? [
            0 => 0, 1 => $binancePrice, 2 => $binancePrice, 
            3 => $binancePrice, 4 => $binancePrice, 5 => 0
        ];
        
        // 3. Store data (keep your existing code)
        HistoricalPrice::create([
            'timestamp' => now(),
            'pair' => 'BTC/USD',
            'binance' => (float)$binancePrice,
            'coinbase' => (float)$coinbasePrice,
            'high' => (float)$ohlc[2],
            'low' => (float)$ohlc[3],
            'spread' => abs((float)$binancePrice - (float)$coinbasePrice),
            'volume' => (float)$ohlc[5]
        ]);

        // 4. Periodic analysis (keep your existing code)
        if (now()->minute % 15 === 0) {
            $this->analyzeMarket();
        }

        $this->info('Successfully updated prices at '.now());

    } catch (\Exception $e) {
        $this->error('Error: '.$e->getMessage());
        $this->createFallbackRecord();
    }
}
// In your FetchPrices command
private function analyzeMarket()
{
    $last30 = HistoricalPrice::where('timestamp', '>=', now()->subMinutes(30))
                ->orderBy('timestamp')
                ->get();

    $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
        'model' => 'llama3',
        'prompt' => $this->buildPrompt($last30),
        'format' => 'json',
        'options' => ['temperature' => 0.7]
    ]);

    $prediction = $response->json();
    
    HistoricalPrice::latest()->first()->update([
        'predicted_high' => $prediction['high'],
        'predicted_low' => $prediction['low'],
        'ai_action' => $prediction['action'],
        'confidence' => $prediction['confidence']
    ]);
}
// Remove these methods since we're using the macros:
// protected function fetchBinancePrice()
// protected function fetchCoinbasePrice() 
// protected function fetchBinanceOHLC()

    protected function configureHttpClient()
    {
        // Solution 1: Disable SSL verification (not recommended for production)
        // Http::withOptions(['verify' => false]);
        
        /* 
        // Better Solution: Download cacert.pem from https://curl.haxx.se/ca/cacert.pem
        // Save it in your project root (E:\crytp-project\cacert.pem)
        */
        if (file_exists(base_path('cacert.pem'))) {
            Http::withOptions(['verify' => base_path('cacert.pem')]);
        } else {
            $this->warn('CA certificates not found. SSL verification disabled.');
            Http::withOptions(['verify' => false]);
        }
        
    }

    protected function fetchBinancePrice(): float
    {
        $response = Http::timeout(10)
            ->get('https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT');
            
        if (!$response->successful()) {
            throw new \Exception('Binance API failed');
        }
        
        return (float)$response->json()['price'];
    }

    protected function fetchCoinbasePrice(): float
    {
        $response = Http::timeout(10)
            ->get('https://api.coinbase.com/v2/prices/BTC-USD/spot');
            
        if (!$response->successful()) {
            throw new \Exception('Coinbase API failed');
        }
        
        return (float)$response->json()['data']['amount'];
    }

    protected function fetchBinanceOHLC(): array
    {
        $response = Http::timeout(10)
            ->get('https://api.binance.com/api/v3/klines?symbol=BTCUSDT&interval=1m&limit=1');
            
        if (!$response->successful()) {
            throw new \Exception('Binance OHLC API failed');
        }
        
        $data = $response->json()[0] ?? null;
        
        if (!$data) {
            throw new \Exception('No OHLC data received');
        }
        
        return [
            'high' => (float)$data[2],
            'low' => (float)$data[3],
            'volume' => (float)$data[5]
        ];
    }

    protected function createFallbackRecord()
    {
        try {
            HistoricalPrice::create([
                'timestamp' => now(),
                'pair' => 'BTC/USD',
                'binance' => 0,
                'coinbase' => 0,
                'high' => 0,
                'low' => 0,
                'spread' => 0,
                'volume' => 0
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to create fallback record: '.$e->getMessage());
        }
    }
    // private function analyzeMarket()
    // {
    //     try {
    //         $last30 = HistoricalPrice::where('timestamp', '>=', now()->subMinutes(30))
    //                     ->orderBy('timestamp')
    //                     ->get();
            
    //         if ($last30->count() < 10) { // Minimum data check
    //             throw new \Exception('Insufficient data for analysis');
    //         }
            
    //         $response = Http::timeout(30)->post('http://localhost:11434/api/generate', [
    //             'model' => 'llama3',
    //             'prompt' => $this->buildPrompt($last30),
    //             'format' => 'json'
    //         ]);
            
    //         if (!$response->successful()) {
    //             throw new \Exception('AI service unavailable');
    //         }
            
    //         $prediction = json_decode($response->body());
            
    //         if (!isset($prediction->high, $prediction->low, $prediction->action)) {
    //             throw new \Exception('Invalid AI response format');
    //         }
            
    //         HistoricalPrice::latest()->first()->update([
    //             'predicted_high' => (float)$prediction->high,
    //             'predicted_low' => (float)$prediction->low,
    //             'ai_action' => $prediction->action,
    //             'confidence' => (int)$prediction->confidence
    //         ]);
            
    //     } catch (\Exception $e) {
    //         $this->error('Analysis failed: '.$e->getMessage());
    //     }
    // }

    private function buildPrompt($data)
    {
        $priceData = $data->map(function($item) {
            return sprintf("%s | O:%.2f | H:%.2f | L:%.2f | C:%.2f | V:%.4f",
                $item->timestamp->format('H:i'),
                $item->binance,
                $item->high,
                $item->low,
                $item->binance,
                $item->volume
            );
        })->join("\n");
        
        return <<<PROMPT
        Analyze BTC/USD price movements (last 30 minutes):
        Time | Open | High | Low | Close | Volume
        {$priceData}
        
        Predict next 5 minutes:
        1. Exact high price (number only)
        2. Exact low price (number only)
        3. Trading action (ONLY: BUY, SELL, or HOLD)
        4. Confidence percentage (1-100)
        
        Respond in strict JSON format:
        {
            "high": number,
            "low": number,
            "action": "BUY|SELL|HOLD",
            "confidence": number
        }
        PROMPT;
    }
}