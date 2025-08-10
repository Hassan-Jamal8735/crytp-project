<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up() {
    Schema::table('historical_prices', function (Blueprint $table) {
        $table->decimal('high', 16, 2)->after('coinbase');
        $table->decimal('low', 16, 2)->after('high');
        $table->decimal('predicted_high', 16, 2)->nullable()->after('spread');
        $table->decimal('predicted_low', 16, 2)->nullable()->after('predicted_high');
        $table->string('ai_action', 5)->nullable()->after('predicted_low');
        $table->unsignedTinyInteger('confidence')->nullable()->after('ai_action');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historical_prices', function (Blueprint $table) {
            //
        });
    }
};
