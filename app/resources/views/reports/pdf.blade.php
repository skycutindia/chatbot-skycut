<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Analytics Report — {{ $organization->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 20px; }
        .totals { margin-bottom: 20px; }
        .totals span { display: inline-block; margin-right: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $organization->name }}</h1>
    <p class="meta">Analytics report — last {{ $days }} days · Generated {{ $generatedAt->format('M j, Y g:i A') }}</p>

    <div class="totals">
        <span><strong>Conversations:</strong> {{ $totals['conversations'] }}</span>
        <span><strong>Leads:</strong> {{ $totals['leads'] }}</span>
        <span><strong>Handoffs:</strong> {{ $totals['handoffs'] }}</span>
        <span><strong>Widget opens:</strong> {{ $totals['widget_opens'] }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Website</th>
                <th>Chats</th>
                <th>Leads</th>
                <th>Handoffs</th>
                <th>Handoff %</th>
                <th>AI %</th>
                <th>Opens</th>
                <th>Messages</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['website'] }}</td>
                    <td>{{ $row['conversations'] }}</td>
                    <td>{{ $row['leads'] }}</td>
                    <td>{{ $row['handoffs'] }}</td>
                    <td>{{ $row['handoff_rate'] }}%</td>
                    <td>{{ $row['ai_resolution_rate'] }}%</td>
                    <td>{{ $row['widget_opens'] }}</td>
                    <td>{{ $row['messages'] }}</td>
                    <td>{{ $row['avg_satisfaction'] ? $row['avg_satisfaction'].'/5' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
