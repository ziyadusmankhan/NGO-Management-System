<?php

namespace App\Http\Controllers\Welfare;

use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Welfare\StoreBeneficiaryRequest;
use App\Http\Requests\Welfare\UpdateBeneficiaryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BeneficiaryController extends Controller
{
    public function index(): View
    {
        $query = Beneficiary::query()
            ->with([
                'verifiedBy',
                'approvedBy',
            ]);

        if (request()->filled('search')) {
            $search = trim(request('search'));

            $query->where(function ($q) use ($search) {
                $q->where(
                    'beneficiary_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'full_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'father_husband_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'cnic',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'phone',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'location',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (request()->filled('category')) {
            $query->where(
                'category',
                request('category')
            );
        }

        if (request()->filled('verification_status')) {
            $query->where(
                'verification_status',
                request('verification_status')
            );
        }

        if (request()->filled('approval_status')) {
            $query->where(
                'approval_status',
                request('approval_status')
            );
        }

        $beneficiaries = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalBeneficiaries = Beneficiary::query()->count();

        $verifiedBeneficiaries = Beneficiary::query()
            ->where('verification_status', 'verified')
            ->count();

        $pendingVerification = Beneficiary::query()
            ->where('verification_status', 'pending')
            ->count();

        $pendingApproval = Beneficiary::query()
            ->where('approval_status', 'pending')
            ->count();

        return view(
            'welfare.beneficiaries.index',
            compact(
                'beneficiaries',
                'totalBeneficiaries',
                'verifiedBeneficiaries',
                'pendingVerification',
                'pendingApproval'
            )
        );
    }

    public function create(): View
    {
        return view(
            'welfare.beneficiaries.create'
        );
    }

    public function store(
        StoreBeneficiaryRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        $lastBeneficiary = Beneficiary::withTrashed()
            ->orderByDesc('id')
            ->first();

        $nextNumber = ($lastBeneficiary?->id ?? 0) + 1;

        $data['beneficiary_number'] =
            'BEN-' . str_pad(
                $nextNumber,
                5,
                '0',
                STR_PAD_LEFT
            );

        /*
         * New beneficiaries always enter the
         * verification and approval workflow.
         */
        $data['verification_status'] = 'pending';
        $data['approval_status'] = 'pending';

        Beneficiary::create($data);

        return redirect()
            ->route('welfare.beneficiaries.index')
            ->with(
                'success',
                'Beneficiary recorded successfully and is awaiting verification.'
            );
    }

    public function show(Beneficiary $beneficiary): View
    {
        $beneficiary->load([
            'verifiedBy',
            'approvedBy',
            'assistance.fund',
            'verifications.verifiedBy',
        ]); 

        return view('welfare.beneficiaries.show', compact('beneficiary'));
    }

    public function edit(
        Beneficiary $beneficiary
    ): View {
        return view(
            'welfare.beneficiaries.edit',
            compact('beneficiary')
        );
    }

    public function update(
        UpdateBeneficiaryRequest $request,
        Beneficiary $beneficiary
    ): RedirectResponse {
        $beneficiary->update(
            $request->validated()
        );

        return redirect()
            ->route(
                'welfare.beneficiaries.show',
                $beneficiary
            )
            ->with(
                'success',
                'Beneficiary record updated successfully.'
            );
    }

    public function destroy(
        Beneficiary $beneficiary
    ): RedirectResponse {
        $beneficiary->delete();

        return redirect()
            ->route('welfare.beneficiaries.index')
            ->with(
                'success',
                "{$beneficiary->beneficiary_number} was archived successfully."
            );
    }

    public function verify(Beneficiary $beneficiary): RedirectResponse
    {
        $user = auth()->user();

        $beneficiary->update([
            'verification_status' => 'verified',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'approval_status' => 'pending',
        ]);

        BeneficiaryVerification::create([
            'beneficiary_id' => $beneficiary->id,
            'verified_by' => $user->id,
            'verification_date' => now()->toDateString(),
            'status' => 'verified',
            'method' => 'system_review',
            'findings' => 'Beneficiary verification completed.',
        ]);

        return redirect()
            ->route('welfare.beneficiaries.show', $beneficiary)
            ->with('success', 'Beneficiary verified successfully.');
    }

    public function rejectVerification(Beneficiary $beneficiary): RedirectResponse
    {
        $user = auth()->user();

        $beneficiary->update([
            'verification_status' => 'rejected',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'approval_status' => 'pending',
        ]);

        BeneficiaryVerification::create([
            'beneficiary_id' => $beneficiary->id,
            'verified_by' => $user->id,
            'verification_date' => now()->toDateString(),
            'status' => 'rejected',
            'method' => 'system_review',
            'findings' => 'Beneficiary verification rejected.',
        ]);

        return redirect()
            ->route('welfare.beneficiaries.show', $beneficiary)
            ->with('success', 'Beneficiary verification rejected.');
    }

    public function approve(
        Beneficiary $beneficiary
    ): RedirectResponse {
        if ($beneficiary->verification_status !== 'verified') {
            return back()->withErrors([
                'verification' =>
                    'A beneficiary must be verified before approval.',
            ]);
        }

        $beneficiary->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Beneficiary approved successfully.'
        );
    }

    public function rejectApproval(
        Beneficiary $beneficiary
    ): RedirectResponse {
        $beneficiary->update([
            'approval_status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with(
            'success',
            'Beneficiary approval rejected.'
        );
    }
}