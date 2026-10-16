<?php

namespace App\Http\Controllers\Finance;

use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Finance\Transactions\Services\TransactionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreDonationRequest;
use App\Http\Requests\Finance\UpdateDonationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;


class DonationController
{
    public function index(      
        FundBalanceService $balanceService
    ): View {
        $query = Donation::query()
            ->with([
                'fund',
                'transaction',
            ]);

        if (request()->filled('search')) {
            $search = trim(request('search'));

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
            $query->where('fund_id', request('fund'));
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

        /*
        * Financial summaries come from posted donation credits.
        * This keeps the Donation module aligned with the ledger.
        */
        $postedDonations = Transaction::query()
            ->where('type', 'donation')
            ->where('direction', 'credit')
            ->where('status', 'posted')
            ->whereHasMorph(
                'source',
                [Donation::class]
            );

        $donationsThisMonth = (clone $postedDonations)
            ->whereBetween('transaction_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->sum('amount');

        $totalDonations = (clone $postedDonations)
            ->whereBetween('transaction_date', [
                now()->startOfYear()->toDateString(),
                now()->endOfYear()->toDateString(),
            ])
            ->count();

        $restrictedFunds = (clone $postedDonations)
            ->whereHasMorph(
                'source',
                [Donation::class],
                function ($query) {
                    $query->where('designation', 'restricted');
                }
            )
            ->sum('amount');

        $unrestrictedFunds = (clone $postedDonations)
            ->whereHasMorph(
                'source',
                [Donation::class],
                function ($query) {
                    $query->where('designation', 'unrestricted');
                }
            )
            ->sum('amount');

        $pendingDonations = Donation::query()
            ->where('status', 'pending')
            ->count();

        /*
        * Dynamic funds for the filter.
        */
        $funds = Fund::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'finance.donations.index',
            compact(
                'donations',
                'donationsThisMonth',
                'totalDonations',
                'restrictedFunds',
                'unrestrictedFunds',
                'pendingDonations',
                'funds'
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
                        'donation_number' =>
                            $donationNumber,

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
                            'received',

                        'recorded_by' =>
                            auth()->id(),
                    ]);
                }
            );

            /*
            * Only donations linked to a fund can create
            * a fund transaction.
            */
            if ($donation->fund_id) {

                app(TransactionService::class)
                    ->createForSource(
                        $donation,
                        [
                            'fund_id' =>
                                $donation->fund_id,

                            'type' =>
                                'donation',

                            'reference' =>
                                $donation->donation_number,

                            'description' =>
                                'Donation from ' .
                                $donation->donor_name,

                            'amount' =>
                                $donation->amount,

                            'direction' =>
                                'credit',

                            'transaction_date' =>
                                $donation->donation_date,

                            'status' =>
                                'posted',
                        ]
                    );
            }

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

            DB::transaction(function () use ($validated, $donation) {
                $donation->update([
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
                    'fund_reference' => $validated['fund_reference'] ?? null,
                    'fund_id' => $validated['fund_id'] ?? null,
                    'purpose_notes' => $validated['purpose_notes'] ?? null,
                    'internal_notes' => $validated['internal_notes'] ?? null,
                    'status' => $validated['status'],
                ]);

                $eligible =
                    ! $donation->trashed()
                    && $donation->fund_id !== null
                    && in_array($donation->status, ['received', 'restricted'], true);

                if ($eligible) {
                    app(TransactionService::class)->syncForSource($donation, [
                        'fund_id' => $donation->fund_id,
                        'type' => 'donation',
                        'reference' => $donation->donation_number,
                        'description' => 'Donation from ' . $donation->donor_name,
                        'amount' => $donation->amount,
                        'direction' => 'credit',
                        'transaction_date' => $donation->donation_date,
                        'status' => 'posted',
                        'recorded_by' => $donation->recorded_by,
                    ]);
                } else {
                    app(TransactionService::class)->voidForSource($donation);
                }
            });

            return redirect()
                ->route('finance.donations.show', $donation)
                ->with('success', 'Donation updated successfully.');
        }


        public function destroy(Donation $donation): RedirectResponse
        {
            DB::transaction(function () use ($donation) {
                app(TransactionService::class)->voidForSource($donation);

                $donation->delete();
            });

            return redirect()
                ->route('finance.donations.index')
                ->with('success', 'Donation deleted successfully.');
        }


    public function show(
        Donation $donation,
        FundBalanceService $balanceService
    ): View {
        $donation->load([
            'recordedBy',
            'fund',
            'transaction',
        ]);

        $fundBalance = null;

        if ($donation->fund) {
            $fundBalance = $balanceService->balance(
                $donation->fund
            );
        }

        return view(
            'finance.donations.show',
            compact(
                'donation',
                'fundBalance'
            )
        );
    }
}