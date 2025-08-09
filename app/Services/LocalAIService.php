<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalAIService
{
    public function analyze(float $spread, string $pair = 'BTC/USDT'): array
    {
        try {
            $response = Http::timeout(60)
                ->post('http://localhost:11434/api/generate', [
                    'model' => 'llama3:8b-instruct-q4_0',
                    'prompt' => $this->buildPrompt($spread, $pair),
                    'format' => 'json',
                    'stream' => false
                ]);

            return $this->parseResponse($response->json());

        } catch (\Exception $e) {
            Log::error("AI Analysis Failed: " . $e->getMessage());
            return $this->defaultResponse();
        }
    }

    private function buildPrompt(float $spread, string $pair): string
    {
        return <<<PROMPT
[INST] Analyze crypto arbitrage opportunity:
- Pair: $pair
- Spread: $spread%
- Current Market: Historical backtest
- Required Output Format:
{
  "action": "BUY/SELL/HOLD",
  "confidence": 0-100,
  "reason": "technical justification"
}
[/INST]
PROMPT;
    }

    private function parseResponse(?array $response): array
    {
        if (!isset($response['response'])) {
            return $this->defaultResponse();
        }

        $data = json_decode($response['response'], true) ?? [];
        
         return [
            'action' => strtoupper($data['action'] ?? 'HOLD'),
            'confidence' => min(100, max(0, (int)($data['confidence'] ?? 0))),
            'reason' => $data['reason'] ?? 'No analysis provided'
        ];
    }

    private function defaultResponse(): array
    {
        return [
            'action' => 'HOLD',
            'confidence' => 0,
            'reason' => 'System error - default response'
        ];
    }
}