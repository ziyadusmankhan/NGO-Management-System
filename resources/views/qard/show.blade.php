@extends('layouts.app')
@section('title', 'Qard-e-Hasana')
@push('styles') @include('qard.partials.style') @endpush
@section('content')
@php
$money = fn($value) => \App\Domain\Finance\Support\Money::format($value);
$can = fn($slug) => auth()->user()->hasPermission('qard.'.$slug);
@endphp
<div class="qard-page"><h1>{{ $loan->number }} · {{ $loan->beneficiary?->full_name }}</h1>
<p class="qmuted">Created by {{ $loan->creator?->name }} · Revision {{ $loan->revision }} · <span class="qbadge">{{ str($loan->status)->headline() }}</span></p>
<div class="qactions"><a class="qbtn secondary" href="{{ route('qard.index') }}">Back to Qard</a>
@if(in_array($loan->status,['draft','rejected']) && $can('manage'))<a class="qbtn" href="{{ route('qard.edit',$loan) }}">Edit draft</a>@endif
@if(in_array($loan->status,['approved','signed','active','repaid']))<a class="qbtn secondary" href="{{ route('qard.agreement',$loan) }}">Print agreement</a>@endif
@if($loan->status==='active' && $can('repay'))<a class="qbtn" href="{{ route('qard.repayment',$loan) }}">Record repayment</a>@endif</div>
@include('qard.partials.feedback')
@if($loan->reminder && !$loan->reminder->resolved_at)<div class="qalert">{{ $loan->reminder->message }}</div>@endif
<div class="qcard"><div class="qgrid">
<div><small>{{ in_array($loan->status,['approved','signed','active','repaid']) ? 'Approved' : 'Requested' }} principal</small><div class="qvalue">PKR {{ $money($loan->principal) }}</div></div>
<div><small>Principal recovered</small><div class="qvalue">PKR {{ $money($recovered) }}</div></div>
<div><small>Outstanding disbursed principal</small><div class="qvalue">PKR {{ $money($outstanding) }}</div></div>
<div><small>Original fund</small><strong>{{ $loan->fund->name }}</strong><small>Ledger balance: PKR {{ $money($balance) }}</small></div></div>
<hr><p class="qnote">{{ $loan->purpose }}</p><p>{{ ucfirst($loan->frequency) }} · {{ $loan->installment_count }} installments · First due {{ $loan->first_due_date->format('d M Y') }}</p>
<p class="qmuted">Principal only. No interest or penalties. Repayments return to the original fund once; fund recycling requires no additional transfer.</p></div>

@if(in_array($loan->status,['draft','rejected']) && $can('manage'))
<form class="qcard" method="post" action="{{ route('qard.submit',$loan) }}">@csrf<h2>Submit application</h2><p>Check the borrower, fund, amount, and dates before starting approval.</p><button class="qbtn">Submit for verification</button></form>@endif
@if($loan->status==='submitted' && $can('verify'))
<form class="qcard" method="post" enctype="multipart/form-data" action="{{ route('qard.verify',$loan) }}">@csrf<h2>Field verification</h2>
<label for="verification_notes">Findings</label><textarea id="verification_notes" name="notes" required maxlength="5000">{{ old('notes') }}</textarea>
<label for="evidence">Evidence (PDF, JPG or PNG; maximum 10 MB)</label><input id="evidence" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png" required><div class="qactions"><button class="qbtn">Record verification</button></div></form>@endif
@php $decisionPermission=['implementation'=>'recommend','secretary'=>'review','committee'=>'approve'][$loan->status] ?? null; @endphp
@if($decisionPermission && $can($decisionPermission))
<form class="qcard" method="post" action="{{ route('qard.decide',[$loan,$loan->status]) }}">@csrf<h2>{{ ['implementation'=>'Implementation Committee recommendation','secretary'=>'General Secretary review','committee'=>'Decision Committee approval'][$loan->status] }}</h2>
<p>The creator cannot decide this application. Each decision stage requires a different person.</p><label for="decision">Decision</label><select id="decision" name="decision"><option value="accept">Accept and continue</option><option value="reject">Reject for correction</option></select>
<label for="decision_notes">Reason / notes</label><textarea id="decision_notes" name="notes" required maxlength="5000">{{ old('notes') }}</textarea><div class="qactions"><button class="qbtn">Record decision</button></div></form>@endif
@if($loan->status==='approved' && $can('agreements'))
<form class="qcard" method="post" enctype="multipart/form-data" action="{{ route('qard.sign',$loan) }}">@csrf<h2>Record signed agreement</h2><p>Print the agreement, obtain both signatures, then upload the copies. PDF, JPG or PNG, maximum 10 MB each.</p><div class="qgrid">
<div><label for="borrower_name">Borrower signatory</label><input id="borrower_name" name="borrower_name" value="{{ old('borrower_name',$loan->beneficiary->full_name) }}" required maxlength="255"></div>
<div><label for="foundation_name">Foundation signatory</label><input id="foundation_name" name="foundation_name" value="{{ old('foundation_name') }}" required maxlength="255"></div>
<div><label for="borrower_file">Borrower signed copy</label><input id="borrower_file" type="file" name="borrower_file" accept=".pdf,.jpg,.jpeg,.png" required></div>
<div><label for="foundation_file">Foundation signed copy</label><input id="foundation_file" type="file" name="foundation_file" accept=".pdf,.jpg,.jpeg,.png" required></div></div><div class="qactions"><button class="qbtn">Record both signatures</button></div></form>@endif
@if($loan->status==='signed' && $can('disburse'))
<form class="qcard" method="post" action="{{ route('qard.disburse',$loan) }}">@csrf<h2>Disburse approved principal</h2><p>This records one debit against {{ $loan->fund->name }}.</p>
@include('qard.partials.payment',['maximum'=>$loan->principal,'kind'=>'disbursement'])
<div class="qactions"><button class="qbtn">Record disbursement</button></div></form>@endif
@if(in_array($loan->status,['approved','signed']) && $can('manage'))
<details class="qcard"><summary>Need to change approved terms?</summary><p>This releases the commitment and returns the loan to draft. All approvals and signatures must be obtained again.</p><form method="post" action="{{ route('qard.revise',$loan) }}">@csrf<label for="revision_reason">Reason</label><textarea id="revision_reason" name="reason" required maxlength="2000"></textarea><div class="qactions"><button class="qbtn secondary">Request revision</button></div></form></details>@endif
<div class="qcard qtable"><h2>Repayment schedule</h2><table><thead><tr><th>Installment</th><th>Due</th><th>Scheduled</th><th>Paid</th><th>Remaining</th><th>Status</th></tr></thead><tbody>
@forelse($installments as $row)
@php $paid=$row->paidMinor(); $left=\App\Domain\Finance\Support\Money::minor($row->amount)-$paid; @endphp
<tr><td>{{ $row->sequence }}</td><td>{{ $row->due_date->format('d M Y') }}</td><td>{{ $money($row->amount) }}</td><td>{{ $money(\App\Domain\Finance\Support\Money::decimal($paid)) }}</td><td>{{ $money(\App\Domain\Finance\Support\Money::decimal($left)) }}</td><td>{{ $left===0 ? 'Paid' : ($loan->status==='active' && $row->due_date->toDateString()<now('Asia/Karachi')->toDateString() ? 'Overdue' : 'Pending') }}</td></tr>
@empty<tr><td colspan="6">The schedule is created at final approval.</td></tr>@endforelse</tbody></table></div>
<div class="qcard qtable"><h2>Disbursement and repayment history</h2><table><thead><tr><th>Movement / date</th><th>Amount</th><th>Method / reference</th><th>Ledger</th><th>Status / correction</th></tr></thead><tbody>
@forelse($loan->movements as $movement)<tr><td>{{ ucfirst($movement->kind) }} #{{ $movement->id }}<small>{{ $movement->payment_date->format('d M Y') }}</small></td><td>PKR {{ $money($movement->amount) }}</td><td>{{ str($movement->payment_method)->headline() }}<small>{{ $movement->reference }} {{ $movement->receipt }}</small></td><td>{{ $movement->transaction?->transaction_number ?? 'Missing — contact finance administrator' }}</td><td>{{ ucfirst($movement->status) }}
@if($movement->status==='posted' && $can('void'))<details><summary>Void movement</summary><form method="post" action="{{ route('qard.void',[$loan,$movement]) }}">@csrf<label for="void_reason_{{ $movement->id }}">Correction reason</label><input id="void_reason_{{ $movement->id }}" name="reason" required maxlength="2000"><div class="qactions"><button class="qbtn danger">Void this movement</button></div></form></details>
@else<small>{{ $movement->void_reason }}</small>@endif</td></tr>@empty<tr><td colspan="5">No money has moved.</td></tr>@endforelse</tbody></table></div>
<div class="qcard"><h2>Private documents</h2>@forelse($loan->documents as $document)<p><a href="{{ route('qard.documents.show',[$loan,$document]) }}">{{ ucfirst($document->kind) }} · revision {{ $document->revision }} · {{ $document->original_name }}</a></p>@empty<p>No evidence uploaded yet.</p>@endforelse</div>
<div class="qcard"><h2>Reviews and decisions</h2>@forelse($loan->reviews as $review)<p><strong>{{ str($review->stage)->headline() }}: {{ ucfirst($review->decision) }}</strong> · revision {{ $review->revision }} · {{ $review->actor?->name }} · {{ $review->created_at->timezone('Asia/Karachi')->format('d M Y H:i') }}</p><p class="qnote">{{ $review->notes }}</p>@empty<p>No reviews yet.</p>@endforelse</div>
<div class="qcard"><h2>Borrower follow-ups</h2>@foreach($loan->followups as $followup)<p class="qnote">{{ $followup->notes }}</p><p class="qmuted">{{ $followup->actor?->name }} · {{ $followup->created_at->timezone('Asia/Karachi')->format('d M Y H:i') }} · Next: {{ $followup->next_followup_date?->format('d M Y') ?? 'None scheduled' }}</p>@endforeach
@if(in_array($loan->status,['active','repaid']) && $can('followups'))<form method="post" action="{{ route('qard.followup',$loan) }}">@csrf<label for="followup_notes">Contact notes</label><textarea id="followup_notes" name="notes" required maxlength="5000"></textarea><label for="next_followup_date">Next follow-up date (optional)</label><input id="next_followup_date" name="next_followup_date" type="date" min="{{ now('Asia/Karachi')->toDateString() }}"><div class="qactions"><button class="qbtn">Save follow-up</button></div></form>@endif</div>
<div class="qcard"><h2>Activity history</h2>@foreach($loan->events as $event)<details><summary>{{ str($event->action)->headline() }} · {{ $event->actor?->name }} · {{ $event->created_at->timezone('Asia/Karachi')->format('d M Y H:i') }}</summary><pre style="white-space:pre-wrap">{{ json_encode(['before'=>$event->before,'after'=>$event->after], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>@endforeach</div></div>
@endsection
