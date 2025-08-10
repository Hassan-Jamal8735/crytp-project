<div class="card crypto-chart">
    <div class="card-header pb-0">
        <div class="d-flex justify-content-between align-items-center">
            <h6>{{ $pair }} Price Chart</h6> <!-- Changed from symbol to pair -->
            <div class="btn-group">
                <button wire:click="$set('timeframe', '5m')" class="btn btn-sm btn-outline-{{ $timeframe === '5m' ? 'primary' : 'secondary' }}">5m</button>
                <button wire:click="$set('timeframe', '15m')" class="btn btn-sm btn-outline-{{ $timeframe === '15m' ? 'primary' : 'secondary' }}">15m</button>
                <button wire:click="$set('timeframe', '1h')" class="btn btn-sm btn-outline-{{ $timeframe === '1h' ? 'primary' : 'secondary' }}">1h</button>
                <button wire:click="$set('timeframe', '4h')" class="btn btn-sm btn-outline-{{ $timeframe === '4h' ? 'primary' : 'secondary' }}">4h</button>
                <button wire:click="$set('timeframe', '1d')" class="btn btn-sm btn-outline-{{ $timeframe === '1d' ? 'primary' : 'secondary' }}">1d</button>
            </div>
        </div>
    </div>
    
    <div class="card-body p-2">
        <div wire:ignore id="crypto-chart-container" style="height: 500px;"></div>
        
        @if($latestPrediction)
        <div class="mt-3 alert alert-{{ $latestPrediction->ai_action === 'BUY' ? 'success' : 'danger' }}">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>AI Recommendation:</strong> {{ $latestPrediction->ai_action }}
                    <span class="badge bg-dark ms-2">{{ $latestPrediction->confidence }}% confidence</span>
                </div>
                <div>
                    <small>Range: ${{ number_format($latestPrediction->predicted_low, 2) }} - ${{ number_format($latestPrediction->predicted_high, 2) }}</small>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>

<script>
let chart = null;
let candleSeries = null;

function initializeCryptoChart() {
    const container = document.getElementById('crypto-chart-container');
    if (!container) return;

    if (chart) {
        container.innerHTML = '';
    }

    chart = LightweightCharts.createChart(container, {
        layout: {
            textColor: '#333',
            background: { type: 'solid', color: '#ffffff' },
        },
        grid: {
            vertLines: { color: 'rgba(0, 0, 0, 0.05)' },
            horzLines: { color: 'rgba(0, 0, 0, 0.05)' },
        },
        width: container.clientWidth,
        height: 500,
        timeScale: {
            timeVisible: true,
            secondsVisible: false,
        },
    });

    candleSeries = chart.addCandlestickSeries({
        upColor: '#26a69a',
        downColor: '#ef5350',
        borderUpColor: '#26a69a',
        borderDownColor: '#ef5350',
        wickUpColor: '#26a69a',
        wickDownColor: '#ef5350',
    });

    const chartData = @json($chartData);
    candleSeries.setData(chartData);
    chart.timeScale().fitContent();

    new ResizeObserver(entries => {
        if (entries.length === 0 || !chart) return;
        const { width, height } = entries[0].contentRect;
        chart.applyOptions({ width, height });
    }).observe(container);
}

document.addEventListener('DOMContentLoaded', function() {
    initializeCryptoChart();
    
    Livewire.on('chartUpdated', () => {
        initializeCryptoChart();
    });
});
</script>
@endpush