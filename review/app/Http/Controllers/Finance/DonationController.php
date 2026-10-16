<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Donations\Models\Donation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreDonationRequest;
use App\Domain\Finance\Funds\Models\Fund;
use App\Http\Requests\Finance\UpdateDonationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DonationController
{
    public function index(): View
    {
        $query = Donation::query()->with('fund');

        if (request()->filled('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where(
                    'donation_number',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'donor_name',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'fund_reference',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'purpose',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (request()->filled('fund')) {
            $query->where(
                'fund_reference',
                request('fund')
            );
        }

        if (request()->filled('payment_method')) {
            $query->where(
                'payment_method',
                request('payment_method')
            );
        }

        if (request()->filled('status')) {
            $query->where(
                'status',
                request('status')
            );
        }


        $donations = $query
            ->latest('donation_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();


        $monthStart = now()
            ->startOfMonth();

        $monthEnd = now()
            ->endOfMonth();


        $donationsThisMonth = Donation::query()
            ->whereBetween(
                'donation_date',
                [
                    $monthStart->toDateString(),
                    $monthEnd->toDateString(),
                ]
            )
            ->sum('amount');


        $yearStart = now()
            ->startOfYear();

        $yearEnd = now()
            ->endOfYear();


        $totalDonations = Donation::query()
            ->whereBetween(
                'donation_date',
                [
                    $yearStart->toDateString(),
                    $yearEnd->toDateString(),
                ]
            )
            ->count();


        $restrictedFunds = Donation::query()
            ->where('designation', 'restricted')
            ->sum('amount');


        $unrestrictedFunds = Donation::query()
            ->where('designation', 'unrestricted')
            ->sum('amount');


        $pendingDonations = Donation::query()
            ->where('status', 'pending')
            ->count();


        return view(
            'finance.donations.index',
            compact(
                'donations',
                'donationsThisMonth',
                'totalDonations',
                'restrictedFunds',
                'unrestrictedFunds',
                'pendingDonations'
            )
        );
    }


    public function create(): View
    {
        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'finance.donations.create',
            compact('funds')
        );
    }


    public function store(
        StoreDonationRequest $request
    ): RedirectResponse {

        $validated = $request->validated();


        $donation = DB::transaction(
            function () use ($validated) {

                $nextId =
                    (
                        Donation::withTrashed()
                            ->max('id')
                        ?? 0
                    ) + 1;


                $donationNumber =
                    'DON-' .
                    str_pad(
                        $nextId,
                        5,
                        '0',
                        STR_PAD_LEFT
                    );


                return Donation::create([
                    'donation_number' => $donationNumber,
                    'donor_type' => $validated['donor_type'],
                    'donor_name' => $validated['donor_name'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'amount' => $validated['amount'],
                    'donation_date' => $validated['donation_date'],
                    'payment_method' => $validated['payment_method'],
                    'transaction_reference' => $validated['transaction_reference'] ?? null,
                    'receipt_number' => $validated['receipt_number'] ?? null,
                    'designation' => $validated['designation'],
                    'purpose' => $validated['purpose'],
                    'fund_id' => $validated['fund_id'] ?? null,
                    'purpose_notes' => $validated['purpose_notes'] ?? null,
                    'internal_notes' => $validated['internal_notes'] ?? null,
                    'status' => 'received',
                    'recorded_by' => auth()->id(),
                ]);
            }
        );


        return redirect()
            ->route(
                'finance.donations.show',
                $donation
            )
            ->with(
                'success',
                "Donation {$donation->donation_number} was recorded successfully."
            );
    }
    public function edit(
            Donation $donation
        ): View {
            $funds = Fund::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            return view(
                'finance.donations.edit',
                compact('donation', 'funds')
            );
        }


        public function update(
            UpdateDonationRequest $request,
            Donation $donation
        ): RedirectResponse {

            $validated = $request->validated();

            $donation->update([
                'donor_type' =>
                    $validated['donor_type'],

                'donor_name' =>
                    $validated['donor_name'],

                'phone' =>
                    $validated['phone']
                    ?? null,

                'email' =>
                    $validated['email']
                    ?? null,

                'amount' =>
                    $validated['amount'],

                'donation_date' =>
                    $validated['donation_date'],

                'payment_method' =>
                    $validated['payment_method'],

                'transaction_reference' =>
                    $validated['transaction_reference']
                    ?? null,

                'receipt_number' =>
                    $validated['receipt_number']
                    ?? null,

                'designation' =>
                    $validated['designation'],

                'purpose' =>
                    $validated['purpose'],

                'fund_id' =>
                    $validated['fund_id']
                    ?? null,

                'purpose_notes' =>
                    $validated['purpose_notes']
                    ?? null,

                'internal_notes' =>
                    $validated['internal_notes']
                    ?? null,

                'status' =>
                    $validated['status'],
            ]);

            return redirect()
                ->route(
                    'finance.donations.show',
                    $donation
                )
                ->with(
                    'success',
                    'Donation updated successfully.'
                );
        }


        public function destroy(
            Donation $donation
        ): RedirectResponse {

            $donation->delete();

            return redirect()
                ->route('finance.donations.index')
                ->with(
                    'success',
                    "{$donation->donation_number} was archived successfully."
                );
        }


    public function show(
        Donation $donation
    ): View {

        $donation->load('recordedBy', 'fund');

        return view(
            'finance.donations.show',
            compact('donation')
        );
    }
}