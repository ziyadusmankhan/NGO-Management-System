@props(['funds'])
@php
 $money=\App\Domain\Finance\Support\Money::class;
 $scale=max(1,$funds->map(fn($fund)=>abs($money::minor($fund['balance'])))->max()??0);
 $width=max(540,$funds->count()*90+70);
 $slotWidth=($width-60)/max(1,$funds->count());
@endphp
@if($funds->isEmpty())<x-ui.empty message="No active funds found."/>
@else
<div class="fund-graph" tabindex="0" role="region" aria-label="Fund ledger balance graph, scroll horizontally to see all funds">
<svg viewBox="0 0 {{ $width }} 260" role="img" aria-labelledby="fund-chart-title fund-chart-description" style="min-width:{{ $width }}px">
 <title id="fund-chart-title">Current fund balances (PKR)</title><desc id="fund-chart-description">Bars compare current ledger balances. Fund names and exact amounts are listed below. This is not a time trend or commitment graph.</desc>
 @foreach([30,75,120,165,210] as $y)<line class="graph-grid" x1="40" x2="{{ $width-10 }}" y1="{{ $y }}" y2="{{ $y }}"/>@endforeach
 <text x="0" y="18">PKR</text><text x="15" y="124">0</text>
 @foreach($funds as $index=>$fund)
 @php($minor=$money::minor($fund['balance']))
 @php($height=abs($minor)/$scale*90)
 @php($x=50+$index*$slotWidth)
 <a href="{{ route('finance.funds.show',$fund['id']) }}" aria-label="{{ $fund['name'] }}: PKR {{ $money::format($fund['balance']) }}">
 <rect class="{{ $minor<0?'graph-bar-negative':'graph-bar' }}" x="{{ $x }}" y="{{ $minor<0?120:120-$height }}" width="{{ min(48,$slotWidth-16) }}" height="{{ $height }}" rx="4"><title>{{ $fund['name'] }} — PKR {{ $money::format($fund['balance']) }}{{ $minor<0?' (negative balance)':'' }}</title></rect>
 <text x="{{ $x+20 }}" y="242" text-anchor="middle">{{ $index+1 }}</text></a>
 @endforeach
</svg></div>
<div class="fund-legend">@foreach($funds as $index=>$fund)<a href="{{ route('finance.funds.show',$fund['id']) }}"><span class="fund-index">{{ $index+1 }}.</span><span>{{ $fund['name'] }}</span><strong>PKR {{ $money::format($fund['balance']) }}</strong></a>@endforeach</div>
@endif
