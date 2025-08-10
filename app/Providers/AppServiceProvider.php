<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ArbitrageService;
use App\Services\LocalAIService;
use App\Services\ArbitrageAIService;
use Llama;
use App\Services\LlamaClient;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use App\Http\Livewire\PriceChart;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
     {
        // Core arbitrage service
        $this->app->bind(ArbitrageService::class, function ($app) {
            return new ArbitrageService();
        });

        // Ollama/Llama HTTP client
        $this->app->singleton(LlamaClient::class, function() {
            return new LlamaClient();
        });
        
        // AI analysis service
        $this->app->bind(ArbitrageAIService::class, function($app) {
            return new ArbitrageAIService(
                $app->make(LlamaClient::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
         Livewire::component('price-chart', PriceChart::class);
        
        // Your existing service bindings
        $this->app->bind(ArbitrageService::class, function($app) {
            return new ArbitrageService();
        });
          Http::macro('binance', function() {
            return Http::withOptions([
                'verify' => false,
                'timeout' => 15,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]
            ])->baseUrl('https://api.binance.com/api/v3');
        });
        
        Http::macro('coinbase', function() {
            return Http::withOptions([
                'verify' => false,
                'timeout' => 15
            ])->baseUrl('https://api.coinbase.com/v2');
        });
    }
}
