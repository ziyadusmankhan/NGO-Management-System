@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php($money = \App\Domain\Finance\Support\Money::class)
<div class="page-heading"><div><h1>Dashboard</h1><p>People, funds and work that needs your attention.</p></div><div class="header-actions"><span class="date">{{ now('Asia/Karachi')->format('l, d F Y') }}</span><a class="btn btn-secondary" href="{{ route('notifications.index') }}">View reminders</a></div></div>
<div class="stats-grid">
@can('members.view')<x-ui.metric label="Total Members" :value="number_format($totalMembers)" :note="number_format($activeMembers).' currently active'" :href="route('members.index')"/>@endcan
@can('projects.view')<x-ui.metric label="Active Projects" :value="number_format($activeProjects)" note="Projects currently In Progress" :href="route('projects.index',['status'=>'in_progress'])"/>@endcan
@can('finance.view')
<x-ui.metric label="Donations This Month" :value="'PKR '.$money::format($donationsThisMonth)" :note="number_format($donationsCountThisMonth).' posted donations'" :href="route('finance.donations.index')"/>
<x-ui.metric label="Total Fund Balance" :value="'PKR '.$money::format($totalFundBalance)" note="Ledger balance across active funds" :href="route('finance.funds.index')"/>
@endcan
</div>
<div class="ui-dashboard-grid">
@can('finance.view')
<x-ui.panel title="Fund Overview" :href="route('finance.funds.index')" link="View funds">
<p class="qmuted">Current ledger balances. Commitments are shown separately in each fund.</p>
@php($maximum = max(1, $fundOverview->map(fn($fund) => max(0,$money::minor($fund['balance'])))->max() ?? 0))
@forelse($fundOverview as $fund)
<div class="ui-list-row"><div style="flex:1;min-width:0">@if(auth()->user()?->hasPermission('finance.view'))<a href="{{ route('finance.funds.show',$fund['id']) }}"><strong>{{ $fund['name'] }}</strong></a>@endif<small>{{ str($fund['type'])->headline() }}</small><div class="ui-balance-bar" aria-hidden="true"><span style="width:{{ max(0,$money::minor($fund['balance']))/$maximum*100 }}%"></span></div></div><strong class="ui-money">PKR {{ $money::format($fund['balance']) }}</strong></div>
@empty<x-ui.empty message="No active funds found."/>@endforelse
</x-ui.panel>
@endcan
@can('projects.view')
<x-ui.panel title="Project Status" :href="route('projects.index')" link="View projects">
@if(array_sum($projectStatusCounts) === 0)<x-ui.empty message="No projects have been recorded yet."/>
@else
@foreach($projectStatusCounts as $status=>$count)@if(auth()->user()?->hasPermission('projects.view'))<a class="ui-list-row" href="{{ route('projects.index',['status'=>$status]) }}"><span>{{ str($status)->headline() }}</span><strong>{{ number_format($count) }} →</strong></a>@endif
@endforeach
@endif
</x-ui.panel>
@endcan
</div>
@can('finance.view')
<div class="ui-dashboard-section"><x-ui.panel title="Recent Transactions" :href="route('finance.transactions.index')">
<div class="table-wrap"><table><thead><tr><th>Date</th><th>Transaction</th><th>Fund</th><th class="ui-number">Amount</th><th>Status</th></tr></thead><tbody>
@forelse($recentTransactions as $transaction)
<tr><td>{{ $transaction->transaction_date->format('d M Y') }}</td><td>@if(auth()->user()?->hasPermission('finance.view'))<a href="{{ route('finance.transactions.show',$transaction) }}"><strong>{{ $transaction->transaction_number }}</strong></a>@endif<small style="display:block;color:var(--muted)">{{ str($transaction->type)->headline() }}</small></td><td>{{ $transaction->fund?->name ?? '—' }}</td><td class="ui-number {{ $transaction->direction==='credit'?'ui-positive':'ui-negative' }}">{{ $transaction->direction==='credit'?'+':'−' }} PKR {{ $money::format($transaction->amount) }}</td><td><span class="status-badge">{{ ucfirst($transaction->status) }}</span></td></tr>
@empty<tr><td colspan="5">No posted transactions found.</td></tr>@endforelse
</tbody></table></div>
</x-ui.panel></div>
@endcan
<div class="ui-dashboard-grid">
@can('tasks.view')
<x-ui.panel title="Upcoming Tasks" :href="route('operations.tasks.index')">
@forelse($upcomingTasks as $task)<div class="ui-list-row"><div>@if(auth()->user()?->hasPermission('tasks.view'))<a href="{{ route('operations.tasks.show',$task) }}"><strong>{{ $task->title }}</strong></a>@endif<small>{{ $task->number }} · {{ $task->assignee?->name ?? 'Unassigned' }}</small><small>{{ str($task->priority)->headline() }} priority · {{ str($task->status)->headline() }}</small></div><div><strong>{{ $task->due_on->format('d M Y') }}</strong>@if($task->overdue)<small class="ui-negative">Overdue</small>@endif</div></div>
@empty<x-ui.empty message="No open tasks are due in the next 14 days."/>@endforelse
</x-ui.panel>
@endcan
<x-ui.panel title="Recent Activity" class="ui-activity" :href="auth()->user()->hasPermission('audit.view') ? route('records.audit.index') : null" link="Open Audit Trail">
@forelse($recentActivity as $activity)
@php($event=$activity['event'])
<div class="ui-list-row"><div>@if($activity['url'])<a href="{{ $activity['url'] }}"><strong>{{ $event->summary }}</strong></a>@else<strong>{{ $event->summary }}</strong>@endif<small>{{ $event->actor_name ?? ucfirst($event->actor_kind) }} · {{ str($event->module)->headline() }} @if($event->subject_reference)· {{ $event->subject_reference }}@endif</small><small>{{ $event->occurred_at?->timezone('Asia/Karachi')->diffForHumans() ?? 'Time unavailable' }}</small></div></div>
@empty<x-ui.empty message="No authorized activity has been recorded yet."/>@endforelse
</x-ui.panel>
</div>
@if($businessSummary !== null)
<section class="ui-dashboard-section"><header><h2>Small Business assistance</h2>@if(auth()->user()?->hasPermission('business.view'))<a href="{{ route('business.applications.index') }}">View cases →</a>@endif</header><div class="stats-grid">
@foreach(['released'=>'Total released','recovered'=>'Principal recovered','outstanding'=>'Outstanding principal'] as $key=>$label)<x-ui.metric :label="$label" :value="'PKR '.$money::format($money::decimal($businessSummary[$key]))" note="Posted financial movements"/>@endforeach
<x-ui.metric label="Businesses started" :value="$businessSummary['started']"/>
</div></section>
@endif
@if($welfareSummary !== null)
<section class="ui-dashboard-section"><header><h2>Welfare overview</h2>@if(auth()->user()?->hasPermission('welfare.assistance.view'))<a href="{{ route('welfare.assistance.index') }}">View assistance →</a>@endif</header><div class="stats-grid">
<x-ui.metric label="Open assistance" :value="$welfareSummary['open']"/>
<x-ui.metric label="Reviews due" :value="$welfareSummary['review_due']"/>
<x-ui.metric label="Posted assistance" :value="'PKR '.$money::format($welfareSummary['paid'])"/>
<x-ui.metric label="Student beneficiaries" :value="$welfareSummary['students']"/>
</div></section>
@endif
@if($qardSummary !== null)
<section class="ui-dashboard-section"><header><h2>Qard-e-Hasana overview</h2>@if(auth()->user()?->hasPermission('qard.view'))<a href="{{ route('qard.index') }}">View loans →</a>@endif</header><div class="stats-grid">
<x-ui.metric label="Active loans" :value="$qardSummary['active']"/>
<x-ui.metric label="Outstanding principal" :value="'PKR '.$money::format($qardSummary['outstanding'])"/>
<x-ui.metric label="Follow-ups due" :value="$qardSummary['followups']"/>
</div></section>
@endif
@endsection
