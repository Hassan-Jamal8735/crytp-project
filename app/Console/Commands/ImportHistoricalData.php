<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportHistoricalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:historical 
                            {file : Path to CSV file in storage/historical/}
                            {--pair=BTC/USDT : Trading pair}
                            {--simulate-coinbase : Simulate Coinbase prices with 0.2% spread}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import historical cryptocurrency data from CSV for arbitrage backtesting';

    /**
     * Execute the console command.
     */
public function handle()
{
    $filePath = storage_path('historical/'.$this->argument('file'));
    
    if (!file_exists($filePath)) {
        $this->error("File not found: ".$filePath);
        return 1;
    }

    // Read file as comma-separated
    $data = array_map(function($line) {
        return str_getcsv($line, ","); // Changed from "\t" to ","
    }, file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

    $this->info("Importing ".count($data)." records...");
    $progressBar = $this->output->createProgressBar(count($data));
    
    $successCount = 0;

    foreach ($data as $row) {
        try {
            // Skip if not enough columns
            if (count($row) < 6) continue;

            // Convert first 13 digits to seconds
            $seconds = substr($row[0], 0, 10);
            
            DB::table('historical_prices')->insert([
                'timestamp' => date('Y-m-d H:i:s', $seconds),
                'pair' => $this->option('pair'),
                'binance' => (float)$row[1],
                'coinbase' => (float)$row[1] * 1.002,
                'spread' => 0.2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $successCount++;
        } catch (\Exception $e) {
            continue; // Skip errors silently
        }
        
        $progressBar->advance();
    }
    
    $progressBar->finish();
    $this->newLine();
    $this->info("Successfully imported {$successCount} records");
    return 0;
}
}