<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historical_prices', function (Blueprint $table) {
            $table->id();
            $table->datetime('timestamp');
            $table->string('pair', 10);          // BTC/USDT
            $table->decimal('binance', 16, 2);   // Binance price
            $table->decimal('coinbase', 16, 2);  // Coinbase price
            $table->decimal('spread', 5, 2);     // Calculated %
            $table->timestamps();

            $table->index('timestamp'); // TimescaleDB optimization
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_prices');
    }
};
