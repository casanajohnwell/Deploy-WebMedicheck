<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MediCheck Monthly Report - {{ $monthTitle }}</title>
    <style>
        @page { size: letter; margin: 0.6in; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; margin: 0; padding: 24px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin-bottom: 16px; }
        .toolbar button { background: #dc2626; color: #fff; border: 0; border-radius: 6px; padding: 8px 14px; font-size: 13px; cursor: pointer; }
        .toolbar button.secondary { background: #fff; color: #111; border: 1px solid #ddd; }
        header { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #dc2626; padding-bottom: 10px; margin-bottom: 14px; }
        header img { width: 44px; height: 44px; }
        h1 { font-size: 18px; margin: 0; }
        .muted { color: #555; }
        .meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px 24px; margin-bottom: 14px; }
        h2 { font-size: 13px; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: .04em; color: #b91c1c; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #fef2f2; }
        .summary td:last-child { text-align: right; width: 80px; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 48px; }
        .signatures div { border-top: 1px solid #111; padding-top: 4px; text-align: center; }
        @media print { .toolbar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="secondary" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <header>
        <img src="{{ asset('images/medicheck-logo.svg') }}" alt="MediCheck" onerror="this.remove()">
        <div>
            <h1>MediCheck Monthly Report</h1>
            <div class="muted">{{ $healthCenter }}</div>
        </div>
    </header>

    <div class="meta">
        <div><strong>Month:</strong> {{ $monthTitle }}</div>
        <div><strong>Generated:</strong> {{ $generatedAt }}</div>
        <div><strong>Prepared by:</strong> {{ $preparedBy }}</div>
        <div>
            <strong>Most taken medicine:</strong>
            @if (! empty($summary['most_taken']))
                {{ $summary['most_taken']['medicine'] }} ({{ $summary['most_taken']['quantity'] }} pcs)
            @else
                None
            @endif
        </div>
    </div>

    <h2>Summary</h2>
    <table class="summary">
        @foreach (\App\Http\Controllers\MonthlyReportController::summaryRows($summary) as $label => $value)
            <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    <h2>Booking records</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Seeker</th><th>Medicine</th><th>Qty</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($summary['lines'] ?? [] as $line)
                <tr>
                    <td>{{ $line['date'] }}</td>
                    <td>{{ $line['seeker'] }}</td>
                    <td>{{ $line['medicine'] }}</td>
                    <td>{{ $line['quantity'] }}</td>
                    <td>{{ ucfirst((string) $line['status']) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">No bookings were recorded for this month.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div>Prepared by (Nurse)</div>
        <div>Noted by (Municipal Health Officer)</div>
    </div>
</body>
</html>
