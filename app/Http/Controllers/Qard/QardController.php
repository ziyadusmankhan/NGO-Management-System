<?php

namespace App\Http\Controllers\Qard;

use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Models\QardDocument;
use App\Domain\Qard\Models\QardMovement;
use App\Domain\Qard\Services\QardWorkflowService;
use App\Domain\Qard\Services\QardPaymentService;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Http\Requests\Qard\QardRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QardController
{
    public function __construct(private QardWorkflowService $workflow, private QardPaymentService $payments) {}

    public function index(Request $request)
    {
        $loans = QardLoan::with(['beneficiary', 'fund', 'reminder'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('number', 'like', '%'.$request->string('search').'%')
                    ->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', '%'.$request->string('search').'%'));
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->boolean('reminders'), fn ($q) => $q->whereHas('reminder', fn ($r) => $r->whereNull('resolved_at')))
            ->latest('id')->paginate(15)->withQueryString();
        return view('qard.index', compact('loans'));
    }

    private function form(?QardLoan $loan = null)
    {
        $beneficiaries = Beneficiary::where('verification_status', 'verified')->where('approval_status', 'approved')->orderBy('full_name')->get();
        $funds = Fund::where('status', 'active')->get()->filter(fn ($fund) => app(FundPurposeService::class)->allowsQard($fund));
        return view('qard.create', compact('loan', 'beneficiaries', 'funds'));
    }

    public function create() { return $this->form(); }
    public function edit(QardLoan $loan) {
        abort_unless(in_array($loan->status, ['draft', 'rejected']), 403);
        return $this->form($loan);
    }
    public function store(QardRequest $request) {
        $loan = $this->workflow->save(null, $request->validated(), $request->user());
        return redirect()->route('qard.show', $loan)->with('success', 'Qard draft created. Review it, then submit for field verification.');
    }
    public function update(QardRequest $request, QardLoan $loan) {
        $this->workflow->save($loan, $request->validated(), $request->user());
        return $this->back($loan, 'Draft saved.');
    }
    public function show(QardLoan $loan) {
        $loan->load(['beneficiary', 'fund', 'creator', 'documents', 'reviews.actor', 'events.actor', 'followups.actor', 'movements.transaction', 'reminder']);
        $installments = $loan->installments()->where('revision', $loan->revision)->orderBy('sequence')->get();
        $recovered = Money::decimal($this->payments->recoveredMinor($loan));
        $outstanding = Money::decimal($this->payments->outstandingMinor($loan));
        $balance = app(FundBalanceService::class)->balance($loan->fund);
        return view('qard.show', compact('loan', 'installments', 'recovered', 'outstanding', 'balance'));
    }
    private function back(QardLoan $loan, string $message) { return redirect()->route('qard.show', $loan)->with('success', $message); }
    public function submit(QardRequest $request, QardLoan $loan) {
        $this->workflow->submit($loan, $request->user());
        return $this->back($loan, 'Submitted for field verification.');
    }
    public function verify(QardRequest $request, QardLoan $loan) {
        $this->workflow->verify($loan, $request->validated(), $request->user());
        return $this->back($loan, 'Verification recorded. Implementation Committee recommendation is next.');
    }
    public function decide(QardRequest $request, QardLoan $loan, string $stage) {
        $this->workflow->decide($loan, $stage, $request->validated(), $request->user());
        return $this->back($loan, 'Decision recorded.');
    }
    public function agreement(QardLoan $loan) {
        abort_unless(in_array($loan->status, ['approved', 'signed', 'active', 'repaid']), 403);
        $loan->load(['beneficiary', 'fund']);
        $schedule = $this->workflow->schedule($loan);
        return view('qard.agreement', compact('loan', 'schedule'));
    }
    public function sign(QardRequest $request, QardLoan $loan) {
        $this->workflow->sign($loan, $request->validated(), $request->user());
        return $this->back($loan, 'Both signed copies recorded. A treasurer can now disburse principal.');
    }
    public function revise(QardRequest $request, QardLoan $loan) {
        $this->workflow->revise($loan, $request->validated(), $request->user());
        return $this->back($loan, 'Returned to draft. New approval and signed copies are required.');
    }
    public function disburse(QardRequest $request, QardLoan $loan) {
        $this->payments->record($loan, 'disbursement', $request->validated(), $request->user());
        return $this->back($loan, 'Principal disbursed and posted to the ledger.');
    }
    public function repayment(QardLoan $loan) {
        abort_unless($loan->status === 'active', 403);
        $remaining = Money::decimal($this->payments->outstandingMinor($loan));
        return view('qard.repayment', compact('loan', 'remaining'));
    }
    public function repay(QardRequest $request, QardLoan $loan) {
        $this->payments->record($loan, 'repayment', $request->validated(), $request->user());
        return $this->back($loan, 'Repayment recorded and returned to the original fund.');
    }
    public function void(QardRequest $request, QardLoan $loan, QardMovement $movement) {
        abort_unless($movement->qard_loan_id === $loan->id, 404);
        $this->payments->void($loan, $movement, $request->validated(), $request->user());
        return $this->back($loan, 'Movement voided; loan and fund balances updated.');
    }
    public function followup(QardRequest $request, QardLoan $loan) {
        $this->workflow->followup($loan, $request->validated(), $request->user());
        return $this->back($loan, 'Follow-up recorded.');
    }
    public function document(QardLoan $loan, QardDocument $document) {
        abort_unless($document->qard_loan_id === $loan->id, 404);
        abort_unless(Storage::disk('private')->exists($document->path), 404);
        return Storage::disk('private')->download($document->path, 'Qard-'.$loan->number.'-'.$document->kind.'.'.pathinfo($document->path, PATHINFO_EXTENSION),
            ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
