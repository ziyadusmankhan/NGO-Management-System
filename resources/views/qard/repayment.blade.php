@extends('layouts.app')
@section('title', 'Qard-e-Hasana')
@push('styles') @include('qard.partials.style') @endpush
@section('content')
<div class="qard-page"><h1>Record repayment · {{ $loan->number }}</h1><p>Received principal returns to {{ $loan->fund->name }}. Record voluntary donations separately in Donations.</p>
@include('qard.partials.feedback')
<form class="qcard" method="post" action="{{ route('qard.repay',$loan) }}">@csrf
@include('qard.partials.payment',['maximum'=>$remaining,'kind'=>'repayment'])
<div class="qactions"><button class="qbtn">Record repayment</button><a class="qbtn secondary" href="{{ route('qard.show',$loan) }}">Cancel</a></div></form></div>
@endsection
