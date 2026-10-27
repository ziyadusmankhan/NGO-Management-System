<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Audit Trail report</title>
    <style>
        body {
            font: 12px Arial, sans-serif;
            color: #183f34;
            margin: 25px
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 18px
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #dce4df;
            text-align: left;
            vertical-align: top
        }

        pre {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            font-size: 10px
        }

        article {
            border-bottom: 2px solid #184435;
            padding: 12px 0;
            break-inside: avoid
        }

        small {
            color: #586d63
        }

        @media print {
            button {
                display: none
            }

            @page {
                size: A4 landscape;
                margin: 12mm
            }
        }
    </style>
</head>

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