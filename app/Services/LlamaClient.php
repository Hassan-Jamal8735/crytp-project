<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlamaClient
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.llama.endpoint', 'http://localhost:11434/api/generate');
    }

   public function generate(string $prompt): string
{
    try {
        $response = Http::timeout(120)->post($this->endpoint, [
            'model' => 'llama3',
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                 'num_ctx' => 4096
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception("API Error: ".$response->status());
        }

        return $response->json()['response'] ?? 'No response from AI';

    } catch (\Exception $e) {
        \Log::error("Llama API Failed", [
            'error' => $e->getMessage(),
            'endpoint' => $this->endpoint
        ]);
        return 'AI Error: '.$e->getMessage(); // More detailed error
    }
}
}