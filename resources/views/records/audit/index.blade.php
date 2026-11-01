@extends('layouts.app')
@section('title', 'Audit Trail')
@push('styles')@include('records.audit.style')@endpush
@section('content')
<div class="no-print" style="margin-bottom:16px">@include('shared.section-export',['exportKind'=>'audit'])</div>

    <div class="audit-page">
        <h1>Audit Trail</h1>
        <p>Recorded changes and security events across the foundation.</p>
        @if($errors->any())
            <div class="audit-card audit-error">@foreach($errors->all() as $error)
            <p>{{ $error }}</p>@endforeach
        </div>@endif
        <div class="audit-grid">@foreach($counts as $label => $count)
            <div class="audit-card"><span>{{ ucfirst($label) }}</span>
                <h2>{{ number_format($count) }}</h2><small>Within the selected filters</small>
        </div>@endforeach
        </div>
        <form class="audit-card" method="get">
            <div class="audit-grid">
                @foreach(['from' => 'From date', 'to' => 'Through date'] as $key => $label)<label>{{ $label }}<input type="date"
                name="{{ $key }}" value="{{ $filters[$key] ?? '' }}"></label>@endforeach
                <label>Actor<select name="actor_id">
                        <option value="">All actors</option>@foreach($actors as $actor)
                            <option value="{{ $actor->actor_id }}" @selected(($filters['actor_id'] ?? '') == $actor->actor_id)>
                        {{ $actor->actor_name }} (#{{ $actor->actor_id }})</option>@endforeach
                    </select></label>
                @foreach(['module' => $modules, 'action' => $actions, 'outcome' => collect(['success', 'blocked'])] as $key => $values)<label>{{ ucfirst($key) }}<select
                    name="{{ $key }}">
                    <option value="">All</option>@foreach($values as $value)
                        <option value="{{ $value }}" @selected(($filters[$key] ?? '') === $value)>{{ str($value)->headline() }}
                    </option>@endforeach
                </select></label>@endforeach
                <label>Record reference<input name="reference" maxlength="100" value="{{ $filters['reference'] ?? '' }}"
                        placeholder="For example PRC-00001"></label><label>History<select name="historical">
                        <option value="">All events</option>
                        <option value="1" @selected(($filters['historical'] ?? '') === '1')>Imported history</option>
                        <option value="0" @selected(($filters['historical'] ?? '') === '0')>Captured since Phase 10</option>
                    </select></label>
            </div><button class="audit-btn">Apply filters</button>@if(auth()->user()?->hasPermission('audit.view'))<a class="audit-btn"
                href="{{ route('records.audit.index') }}">Reset filters</a>@endif
            @can('audit.export')<button class="audit-btn" formaction="{{ route('records.audit.export') }}" name="format"
                value="print">Print / Save as PDF</button><button class="audit-btn"
            formaction="{{ route('sections.export',['kind'=>'audit']) }}" name="format" value="xlsx">Download XLSX</button>@endcan
            <p class="audit-muted">Dates use Asia/Karachi. History with an unknown occurrence time is excluded when date
                filters are applied. Export limit: 10,000 events.</p>
        </form>
        <div class="audit-card audit-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Module / action</th>
                        <th>Record</th>
                        <th>Outcome</th>
                        <th>Summary</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td>@if(auth()->user()?->hasPermission('audit.view'))<a
                                    href="{{ route('records.audit.show', $event) }}">{{ $event->occurred_at?->timezone('Asia/Karachi')->format('d M Y H:i:s') ?? 'Time unavailable' }}</a>@endif
@if($event->historical)<small>Imported
                                    history</small>@endif</td>
                            <td>{{ $event->actor_name ?? ucfirst($event->actor_kind) }}</td>
                            <td>{{ ucfirst($event->module) }}<small>{{ str($event->action)->headline() }}</small></td>
                            <td>{{ $event->subject_reference ?? '—' }}</td>
                            <td><span class="audit-tag">{{ ucfirst($event->outcome) }}</span></td>
                            <td>{{ $event->summary }}</td>
                    </tr>@empty<tr>
                        <td colspan="6">No audit events match these filters.</td>
                    </tr>@endforelse
                </tbody>
            </table>{{ $events->links() }}
        </div>
    </div>
@endsection