<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Welfare\Assistance\Models\WelfareAssistance;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\StoreWelfareAssistanceRequest;
use App\Http\Requests\Welfare\UpdateWelfareAssistanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WelfareAssistanceController extends Controller
{
    public function index(): View
    {
        $query = WelfareAssistance::query()
            ->with([
                'beneficiary',
                'fund',
                'approvedBy',
            ])
            ->latest('id');

        if ($search = trim((string) request('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('assistance_number', 'like', "%{$search}%")
                    ->orWhere('assistance_type', 'like', "%{$search}%")
                    ->orWhereHas('beneficiary', function ($beneficiaryQuery) use ($search) {
                        $beneficiaryQuery
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('beneficiary_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($type = request('assistance_type')) {
            $query->where('assistance_type', $type);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($fundId = request('fund_id')) {
            $query->where('fund_id', $fundId);
        }

        $assistances = $query->paginate(15)->withQueryString();

        $totalAssistance = WelfareAssistance::count();

        $pendingAssistance = WelfareAssistance::where(
            'status',
            'pending'
        )->count();

        $approvedAssistance = WelfareAssistance::where(
            'status',
            'approved'
        )->count();

        $thisMonthAmount = WelfareAssistance::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid', 'completed'])
            ->sum('amount');

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('welfare.assistance.index', compact(
            'assistances',
            'totalAssistance',
            'pendingAssistance',
            'approvedAssistance',
            'thisMonthAmount',
            'funds'
        ));
    }

    public function create(): View
    {
        $beneficiaries = Beneficiary::query()
            ->where('verification_status', 'verified')
            ->where('approval_status', 'approved')
            ->orderBy('full_name')
            ->get([
                'id',
                'beneficiary_number',
                'full_name',
                'category',
                'location',
            ]);

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('welfare.assistance.create', compact(
            'beneficiaries',
            'funds'
        ));
    }

    public function store(
        StoreWelfareAssistanceRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $beneficiary = Beneficiary::findOrFail(
            $data['beneficiary_id']
        );

        abort_unless(
            $beneficiary->verification_status === 'verified'
            && $beneficiary->approval_status === 'approved',
            422,
            'Only verified and approved beneficiaries can receive welfare assistance.'
        );

        $data['status'] = 'pending';
        $data['requested_at'] = now();
        $data['has_supporting_document'] =
            $request->boolean('has_supporting_document');

        $assistance = DB::transaction(function () use ($data) {
            $data['assistance_number'] = 'TEMP-' . Str::uuid();

            $assistance = WelfareAssistance::create($data);

            $assistance->update([
                'assistance_number' => sprintf(
                    'WFA-%05d',
                    $assistance->id
                ),
            ]);

            return $assistance;
        });

        return redirect()
            ->route('welfare.assistance.show', $assistance)
            ->with('success', 'Welfare assistance request created successfully.');
    }

    public function show(WelfareAssistance $assistance): View
    {
        $assistance->load([
        'beneficiary',
        'fund',
        'approvedBy',
        'payments.fund',
        'payments.recordedBy',
        'payments.transaction',
        'reviews.reviewedBy',
    ]);

        return view(
            'welfare.assistance.show',
            compact('assistance')
        );
    }

    public function edit(WelfareAssistance $assistance): View
    {
        abort_unless(
            in_array($assistance->status, ['pending', 'rejected'], true),
            403,
            'Only pending or rejected assistance requests can be edited.'
        );

        $beneficiaries = Beneficiary::query()
            ->where(function ($query) use ($assistance) {
                $query
                    ->where(function ($q) {
                        $q->where('verification_status', 'verified')
                            ->where('approval_status', 'approved');
                    })
                    ->orWhere('id', $assistance->beneficiary_id);
            })
            ->orderBy('full_name')
            ->get([
                'id',
                'beneficiary_number',
                'full_name',
                'category',
                'location',
            ]);

        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'welfare.assistance.edit',
            compact('assistance', 'beneficiaries', 'funds')
        );
    }

    public function update(
        UpdateWelfareAssistanceRequest $request,
        WelfareAssistance $assistance
    ): RedirectResponse {
        abort_unless(
            in_array($assistance->status, ['pending', 'rejected'], true),
            403,
            'Only pending or rejected assistance requests can be edited.'
        );

        $data = $request->validated();

        $beneficiary = Beneficiary::findOrFail(
            $data['beneficiary_id']
        );

        abort_unless(
            $beneficiary->verification_status === 'verified'
            && $beneficiary->approval_status === 'approved',
            422,
            'Only verified and approved beneficiaries can receive welfare assistance.'
        );

        $data['has_supporting_document'] =
            $request->boolean('has_supporting_document');

        $assistance->update($data);

        return redirect()
            ->route('welfare.assistance.show', $assistance)
            ->with('success', 'Welfare assistance request updated successfully.');
    }

    public function approve(
        WelfareAssistance $assistance
    ): RedirectResponse {
        abort_unless(
            $assistance->status === 'pending',
            422,
            'Only pending assistance requests can be approved.'
        );

        abort_unless(
            $assistance->beneficiary->verification_status === 'verified'
            && $assistance->beneficiary->approval_status === 'approved',
            422,
            'The beneficiary must be verified and approved first.'
        );

        $user = auth()->user();

        $assistance->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('welfare.assistance.show', $assistance)
            ->with('success', 'Welfare assistance approved successfully.');
    }

    public function reject(
        WelfareAssistance $assistance
    ): RedirectResponse {
        abort_unless(
            $assistance->status === 'pending',
            422,
            'Only pending assistance requests can be rejected.'
        );

        $assistance->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('welfare.assistance.show', $assistance)
            ->with('success', 'Welfare assistance request rejected.');
    }
}