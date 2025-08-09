<div>
    <div>
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            Arbitrage Dashboard - {{ $pair }}
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <canvas id="priceChart" height="300"></canvas>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Key Metrics</h5>
                            <p>Max Spread: <span class="badge bg-danger">{{ $maxSpread }}%</span></p>
                            <p>Avg Spread: <span class="badge bg-warning">{{ $avgSpread }}%</span></p>
                            <p>Opportunities: <span class="badge bg-success">{{ $opportunities }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:load', function() {
            const ctx = document.getElementById('priceChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($timestamps),
                    datasets: [
                        {
                            label: 'Binance',
                            data: @json($binancePrices),
                            borderColor: '#3490dc',
                            tension: 0.1
                        },
                        {
                            label: 'Coinbase',
                            data: @json($coinbasePrices),
                            borderColor: '#e3342f',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Price (USD)'
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</div>
</div>
