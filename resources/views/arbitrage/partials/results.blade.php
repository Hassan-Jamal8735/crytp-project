<div class="table-responsive">
    <table class="table align-items-center mb-0">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Binance</th>
                <th>Coinbase</th>
                <th>Spread</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($opportunities as $opp)
            <tr>
                <td>{{ $opp->timestamp }}</td>
                <td>${{ number_format($opp->binance, 2) }}</td>
                <td>${{ number_format($opp->coinbase, 2) }}</td>
                <td class="{{ $opp->spread > 1 ? 'text-success' : '' }}">
                    {{ $opp->spread }}%
                </td>
                <td>
                    <span class="badge bg-gradient-{{ $opp->action === 'BUY' ? 'success' : 'secondary' }}">
                        {{ $opp->action }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>