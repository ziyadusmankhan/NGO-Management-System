<style>
body{font-family:dejavusans;color:#22372e;font-size:8.5pt;line-height:1.45}
h1{font-size:22pt;line-height:1.15;color:#173f35;margin:2mm 0 3mm}
h2{font-size:12pt;color:#173f35;margin:7mm 0 2mm}
.context{color:#586b62;font-size:9pt;margin-bottom:5mm}
.totals{width:100%;border-collapse:collapse;margin:3mm 0 6mm}
.totals td{background:#edf5f0;padding:3mm 5mm;width:25%;border-right:2mm solid white}
.label{font-size:8pt;color:#586b62}.value{font-size:18pt;font-weight:bold;color:#173f35}
.register{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:3mm}
.register th{background:#173f35;color:white;text-align:left;font-weight:bold;font-size:8pt;padding:2.5mm;border-right:0.2mm solid #42665a}
.register td{padding:2.5mm;vertical-align:top;border-bottom:0.2mm solid #dee6e1;word-wrap:break-word;line-height:1.5}
.stripe td{background:#f1f7f3}.empty{padding:9mm;text-align:center;color:#586b62}.muted{color:#586b62;font-size:8pt}
thead{display:table-header-group}.section-title{page-break-before:always;page-break-after:avoid}
</style>
<h1>{{ $report['title'] }}</h1>
<div class="context">{{ $report['scope'] }}<br>Prepared by {{ $report['prepared_by'] }}</div>
<table class="totals"><tr>
    @foreach(['Records'=>$report['count'],'Active'=>$report['active'],'Inactive'=>$report['inactive'],'Other statuses'=>$report['count']-$report['active']-$report['inactive']] as $label=>$value)
    <td><div class="label">{{ $label }}</div><div class="value">{{ number_format($value) }}</div></td>
    @endforeach
</tr></table>
@if($report['kind']==='roles')<p class="muted">Administrator has full application access. Other roles use their assigned permissions listed below.</p>@endif
@foreach($report['sections'] as $section)
    @if(!$loop->first)<h2 class="section-title">{{ $section['title'] }} <span class="muted">({{ number_format(count($section['rows'])) }})</span></h2>@endif
    @foreach(collect($section['rows'])->chunk(40)->whenEmpty(fn($c)=>$c->push(collect())) as $chunk)
    <table class="register" autosize="1">
        <thead><tr>@foreach($section['headers'] as $i=>$label)<th width="{{ round($section['widths'][$i]/array_sum($section['widths'])*100,2) }}%">{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($chunk as $row)
            <tr class="{{ $loop->index%2?'stripe':'' }}">@foreach($row as $i=>$value)<td>@if($value instanceof \DateTimeInterface){{ $value->format($section['types'][$i]==='datetime'?'d M Y H:i':'d M Y') }}@else{!! nl2br(e($value === null || $value === '' ? '-' : $value)) !!}@endif</td>@endforeach</tr>
        @empty<tr><td class="empty" colspan="{{ count($section['headers']) }}">No matching records.</td></tr>@endforelse
        </tbody>
    </table>
    @endforeach
@endforeach
