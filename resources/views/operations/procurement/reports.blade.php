@extends('layouts.app')
@section('title', 'Procurement reports')
@push('styles')@include('operations.procurement.style')@endpush
@section('content')
    <div class="qard-page procurement-page">
        <h1>Procurement report</h1>@include('qard.partials.feedback')
        <form class="qcard no-print" method="get">
            <div class="qgrid"><x-business-field name="from" label="Payments / refunds from" type="date"
                    :value="$filters['from'] ?? ''" :required="false" /><x-business-field name="to" label="Through"
                    type="date" :value="$filters['to'] ?? ''" :required="false" /><label>Status<select name="status">
                        <option value="">All</option>@foreach(['draft', 'approved', 'ordered', 'closed', 'cancelled'] as $s)
                            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select></label><label>Parent type<select name="parent_type">
                        <option value="">All authorized</option>@foreach(['office', 'project', 'business'] as $type)
                            <option value="{{ $type }}" @selected(($filters['parent_type'] ?? '') === $type)>{{ ucfirst($type) }}
                        </option>@endforeach
                    </select></label><x-business-field name="parent_id" label="Parent record ID (optional)"
                    :value="$filters['parent_id'] ?? ''"
                    :required="false" />@foreach(['supplier_id' => ['Supplier', $suppliers], 'fund_id' => ['Fund', $funds]] as $key => [$label, $options])<label>{{ $label }}<select
                        name="{{ $key }}">
                        <option value="">All</option>@foreach($options as $o)
                            <option value="{{ $o->id }}" @selected(($filters[$key] ?? '') == $o->id)>{{ $o->name }}</option>
                        @endforeach
                    </select></label>@endforeach</div>
            <div class="qactions"><button class="qbtn">Apply filters</button><button class="qbtn secondary" name="format"
                    value="xlsx">Download XLSX</button><button class="qbtn secondary" name="format"
                    value="print">Print-friendly report</button><a
                    href="{{ route('operations.procurement.index') }}">Back</a></div>
        </form>
        <p class="no-print">For PDF: Ctrl+P → Landscape → Save as PDF.</p>
        <p>Payments/refunds use the inclusive date range {{ $filters['from'] ?? 'beginning' }} through
            {{ $filters['to'] ?? 'all dates' }}. Approval, invoice, unpaid and reservation values are current lifetime
            amounts. Quantities are shown per order and should not be added across different units.</p>
        <div class="qcard qtable">
            <table>
                <thead>
                    <tr>
                        <th>Order / supplier / fund</th>
                        <th>Status / parent</th>@foreach(array_keys($totals) as $key)
                        <th>{{ str($key)->headline() }}</th>@endforeach<th>Accepted / returned quantity</th>
                    </tr>
                </thead>
                <tbody>@forelse($rows as $r)
                    <tr>
                        <td>{{ $r['number'] }} · {{ $r['title'] }}<small>{{ $r['supplier'] }} · {{ $r['fund'] }}</small>
                        </td>
                        <td>{{ $r['status'] }} · {{ $r['parent'] }}</td>@foreach(array_keys($totals) as $key)
                        <td>{{ \App\Domain\Finance\Support\Money::format($r[$key]) }}</td>@endforeach<td>
                            {{ $r['accepted_quantity'] }} / {{ $r['returned_quantity'] }}</td>
                </tr>@empty<tr>
                        <td colspan="10">No matching records.</td>
                    </tr>@endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Totals (PKR)</th>@foreach($totals as $v)
                        <th>{{ \App\Domain\Finance\Support\Money::format($v) }}</th>@endforeach<th>—</th>
                    </tr>
                </tfoot>
            </table>
        </div>
</div>@endsection