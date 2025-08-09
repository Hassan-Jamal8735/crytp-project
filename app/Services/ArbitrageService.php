<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArbitrageService
{
    /**
     * Analyze arbitrage opportunity using direct Ollama API
     *
     * @param float $spread Price spread percentage
     * @param string|null $pair Currency pair (e.g., BTC/USDT)
     * @param string|null $timestamp Historical timestamp
     * @return array
     */
    public function analyze(
        float $spread,
        ?string $pair = 'BTC/USDT',
        ?string $timestamp = null
    ): array {
        try {
            $prompt = $this->buildPrompt($spread, $pair, $timestamp);
            
            $response = Http::timeout(60)
                ->post('http://localhost:11434/api/generate', [
                    'model' => 'llama3:8b-instruct-q4_0',
                    'prompt' => $prompt,
                    'format' => 'json',
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.7,
                        'seed' => 42
                    ]
                ]);

            return $this->parseResponse($response->json());

        } catch (\Exception $e) {
            Log::error("Arbitrage analysis failed: " . $e->getMessage());
            return $this->defaultResponse();
        }
    }

    protected function buildPrompt(
        float $spread,
        ?string $pair,
        ?string $timestamp
    ): string {
        $context = "[INST] As a crypto arbitrage analyst, evaluate this opportunity:\n";
        
        if ($timestamp) {
            $context .= "Historical Date: {$timestamp}\n";
        }
        
        $context .= <<<PROMPT
Currency Pair: {$pair}
Spread Percentage: {$spread}%
---
Analysis Considerations:
1. Typical spread range for {$pair}
2. Market volatility at time of trade
3. Historical price patterns
---
Required JSON Response Format:
{
  "action": "BUY/SELL/HOLD",
  "confidence": 0-100,
  "reason": "technical justification"
}
[/INST]
PROMPT;

        return $context;
    }

    protected function parseResponse(?array $response): array
    {
        if (!isset($response['response'])) {
            return $this->defaultResponse();
        }

        $data = json_decode($response['response'], true) ?? [];
        
        return [
            'action' => strtoupper($data['action'] ?? 'HOLD'),
            'confidence' => min(100, max(0, (int)($data['confidence'] ?? 0))),
            'reason' => $data['reason'] ?? 'No analysis provided',
            'raw' => $response // For debugging
        ];
    }

    protected function defaultResponse(): array
    {
        return [
            'action' => 'HOLD',
            'confidence' => 0,
            'reason' => 'System error - default response'
        ];
    }
}