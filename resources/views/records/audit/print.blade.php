<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Audit Trail report</title>
    <link rel="stylesheet" href="{{ asset('css/views/records-audit-print-1.css') }}">
<link rel="stylesheet" href="{{ asset('css/foundation-print.css') }}"><script defer src="{{ asset('js/foundation-print.js') }}?v=1"></script></head>

<body><button onclick="window.print()">Print / Save as PDF</button>
    <h1>Audit Trail</h1>
    <p>{{ $events->count() }} events - Snapshot #{{ $snapshot }} - All times Asia/Karachi</p>
    <p>From {{ $filters['from'] ?? 'beginning' }} through {{ $filters['to'] ?? 'all dates' }}. Only available, sanitized
        history is included.</p>
    @forelse($events as $event)
        <article>
            <h2>{{ str($event->action)->headline() }} - {{ $event->subject_reference ?? 'No record reference' }}</h2>
            <p>{{ $event->occurred_at?->timezone('Asia/Karachi')->format('Y-m-d H:i:s') ?? 'Time unavailable' }} -
                {{ $event->actor_name ?? ucfirst($event->actor_kind) }} - {{ $event->module }} - {{ $event->outcome }} -
                {{ $event->historical ? 'Imported history' : 'New capture' }}</p>
            <p>{{ $event->summary }}</p>
            <p>Reason: {{ $event->reason ?? 'Unavailable' }}</p>
            <table>
                <tr>
                    <th>Before</th>
                    <th>After</th>
                </tr>
                <tr>
                    <td>
                        <pre>{{ json_encode($event->before_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </td>
                    <td>
                        <pre>{{ json_encode($event->after_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </td>
                </tr>
            </table>
            <pre>{{ json_encode($event->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            <small>{{ $event->event_uuid }}</small>
    </article>@empty<p>No matching events.</p>@endforelse
</body>

</html>