<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HistoricalPrice extends Model
{
    protected $fillable = [
        'timestamp',
        'pair',
        'binance',
        'coinbase',
        'high',
        'low',
        'spread',
        'volume',
        'predicted_high',
        'predicted_low',
        'ai_action',
        'confidence'
    ];
    
    protected $casts = [
        'timestamp' => 'datetime',
        'binance' => 'float',
        'coinbase' => 'float',
        'high' => 'float',
        'low' => 'float',
        'spread' => 'float',
        'volume' => 'float',
        'predicted_high' => 'float',
        'predicted_low' => 'float',
        'confidence' => 'integer'
    ];
}