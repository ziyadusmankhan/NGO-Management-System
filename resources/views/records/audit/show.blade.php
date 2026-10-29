@extends('layouts.app')
@section('title', 'Audit event')
@push('styles')@include('records.audit.style')@endpush
@section('content')
    <div class="audit-page">@if(auth()->user()?->hasPermission('audit.view'))<a href="{{ route('records.audit.index') }}">Back to Audit Trail</a>@endif
        <h1>{{ str($event->action)->headline() }}</h1>
        <p>{{ $event->summary }}</p>
        <div class="audit-card audit-grid">
            <div><strong>Actor</strong>
                <p>{{ $event->actor_name ?? ucfirst($event->actor_kind) }} {{ $event->actor_id ? '(#' . $event->actor_id . ')' : '' }}
                </p>
            </div>
            <div><strong>Time (Asia/Karachi)</strong>
                <p>{{ $event->occurred_at?->timezone('Asia/Karachi')->format('d M Y H:i:s') ?? 'Unavailable' }}</p>
            </div>
            <div><strong>Record</strong>
                <p>{{ $event->subject_reference ?? 'Unavailable' }}</p>@if($sourceLink)<a href="{{ $sourceLink }}">Open source
                record</a>@endif
            </div>
            <div><strong>Outcome</strong>
                <p>{{ ucfirst($event->outcome) }}</p>
            </div>
        </div>
        @if($event->historical)
            <div class="audit-card">Imported from retained history. Missing actors, prior values and request details were not
        reconstructed. Actor names reflect the identity available at import.</div>@endif
        <div class="audit-card">
            <h2>Recorded reason</h2>
            <p>{{ $event->reason ?? 'No separate reason is available in this event.' }}</p>
        </div>
        <div class="audit-grid">@foreach(['before_values' => 'Before', 'after_values' => 'After'] as $key => $label)
            <div class="audit-card">
                <h2>{{ $label }}</h2>@forelse($event->$key ?? [] as $field => $value)
                    <p><strong>{{ str($field)->headline() }}</strong></p>@if(is_array($value))
                    <pre>{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>@else<p>
                {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? 'Unavailable') }}</p>@endif @empty<p>No retained values.
                    </p>@endforelse
        </div>@endforeach
        </div>
        <div class="audit-card">
            <h2>Related references and request context</h2>
            <pre>{{ json_encode($event->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            <p class="audit-muted">Request address and browser information are informational and do not establish identity.
                Source documents retain their own access controls.</p><small>Event {{ $event->event_uuid }} - Captured
                {{ $event->captured_at->timezone('Asia/Karachi')->format('d M Y H:i:s') }}</small>
        </div>
    </div>
@endsection