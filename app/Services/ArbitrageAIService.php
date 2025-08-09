<?php

namespace App\Services;

use Llama;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ArbitrageAIService
{
    // protected $llama; // Removed to avoid redeclaration error

     public function __construct(
        protected LlamaClient $llama
    ) {}

    public function analyzeOpportunity(array $marketData): array
    {
        $prompt = $this->buildPrompt($marketData);
        $response = $this->llama->generate($prompt);
        
        return [
            'decision' => $this->parseDecision($response),
            'confidence' => $this->parseConfidence($response),
            'reasoning' => $response
        ];
    }

    protected function buildPrompt(array $data): string
    {
        return sprintf(
            "As of %s, analyze this crypto arbitrage opportunity:\n\n".
            "Market Conditions:\n".
            "- Binance Price: $%.2f\n".
            "- Coinbase Price: $%.2f\n".
            "- Spread: %.2f%%\n".
            "- Volume: %.4f BTC\n\n".
            "Historical Context:\n".
            "- 1h Change: %.2f%%\n".
            "- 24h Volatility: %.2f%%\n\n".
            "Recommend either: [BUY, SELL, HOLD] with confidence score (1-100) and reasoning.",
            Carbon::parse($data['timestamp'])->toDateTimeString(),
            $data['binance'],
            $data['coinbase'],
            $data['spread'],
            $data['volume'] ?? 0,
            $this->getRecentPriceChange($data),
            $this->getDailyVolatility($data)
        );
    }

    protected function getRecentPriceChange(array $data): float
    {
        // Get 1-hour price change
        $previous = DB::table('historical_prices')
            ->where('timestamp', '<', $data['timestamp'])
            ->orderBy('timestamp', 'desc')
            ->first();
        
        return $previous ? (($data['binance'] - $previous->binance) / $previous->binance * 100) : 0;
    }

    protected function getDailyVolatility(array $data): float
    {
        // Calculate 24h volatility (high-low range)
        $prices = DB::table('historical_prices')
            ->whereBetween('timestamp', [
                Carbon::parse($data['timestamp'])->subDay(),
                $data['timestamp']
            ])
            ->pluck('binance');
        
        return $prices->max() - $prices->min();
    }

    protected function parseDecision(string $response): string
    {
        if (preg_match('/\[(BUY|SELL|HOLD)\]/i', $response, $matches)) {
            return strtoupper($matches[1]);
        }
        return 'HOLD';
    }

    protected function parseConfidence(string $response): int
    {
        if (preg_match('/confidence:?\s*(\d{1,3})/i', $response, $matches)) {
            return min(100, max(0, (int)$matches[1]));
        }
        return 50;
    }

    protected function parseReasoning(string $response): string
    {
        if (preg_match('/reasoning:(.*?)(?=\n\n|$)/is', $response, $matches)) {
            return trim($matches[1]);
        }
        return 'No reasoning provided by AI';
    }
}