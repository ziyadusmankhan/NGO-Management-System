@extends('layouts.app')
@section('title','Notifications')
@push('styles')@include('records.audit.style')@endpush
@section('content')
<div class="audit-page">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap">
        <div><h1>Notifications</h1><p>Due work collected from the modules your account can access. Notifications never issue payments or change workflow decisions.</p></div>
        @if($unread)<form method="post" action="{{ route('notifications.read-all') }}">@csrf<button class="audit-btn">Mark all read</button></form>@endif
    </div>
    @include('qard.partials.feedback')
    <form class="audit-card audit-grid" method="get">
        <label>Inbox<select name="scope"><option value="all" @selected($scope==='all')>Current</option><option value="unread" @selected($scope==='unread')>Unread</option><option value="dismissed" @selected($scope==='dismissed')>Dismissed</option></select></label>
        <label>Module<select name="module"><option value="">All modules</option>@foreach($modules as $module)<option value="{{ $module }}" @selected(($filters['module']??'')===$module)>{{ str($module)->headline() }}</option>@endforeach</select></label>
        <div><button class="audit-btn">Apply filters</button></div>
    </form>
    <p><strong>{{ $unread }}</strong> unread current {{ Str::plural('notification', $unread) }}.</p>
    @forelse($notifications as $notification)
        <div class="audit-card" style="border-left:5px solid {{ $notification['severity']==='urgent' ? '#a33838' : ($notification['severity']==='due' ? '#c89b3c' : '#508c75') }};opacity:{{ $notification['read_at'] ? '.78' : '1' }}">
            <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap">
                <div>
                    <small>{{ str($notification['module'])->headline() }} · {{ str($notification['severity'])->headline() }} @if($notification['due_at']) · {{ $notification['due_at']->format('d M Y') }}@endif</small>
                    <h3 style="margin:6px 0"><a href="{{ $notification['url'] }}">{{ $notification['title'] }}</a></h3>
                    <p>{{ $notification['message'] }}</p>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <form method="post" action="{{ route('notifications.change') }}">@csrf<input type="hidden" name="key" value="{{ $notification['key'] }}"><input type="hidden" name="action" value="{{ $notification['dismissed_at'] ? 'restore' : ($notification['read_at'] ? 'unread' : 'read') }}"><button class="audit-btn">{{ $notification['dismissed_at'] ? 'Restore' : ($notification['read_at'] ? 'Mark unread' : 'Mark read') }}</button></form>
                    @if(!$notification['dismissed_at'])<form method="post" action="{{ route('notifications.change') }}">@csrf<input type="hidden" name="key" value="{{ $notification['key'] }}"><input type="hidden" name="action" value="dismiss"><button class="audit-btn" style="background:var(--legacy-surface-68756f, #68756f)">Dismiss</button></form>@endif
                </div>
            </div>
        </div>
    @empty
        <div class="audit-card"><h3>Nothing needs attention</h3><p>No notifications match these filters.</p></div>
    @endforelse
    {{ $notifications->links() }}
</div>
@endsection
