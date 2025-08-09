<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ArbitrageService;
use App\Services\LocalAIService;
use App\Services\ArbitrageAIService;
use Llama;
use App\Services\LlamaClient;

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
        //
    }
}
