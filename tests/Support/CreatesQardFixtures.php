<?php

namespace Tests\Support;

use App\Domain\Qard\Models\QardLoan;
use App\Domain\Qard\Services\QardPaymentService;
use App\Domain\Qard\Services\QardWorkflowService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Str;

trait CreatesQardFixtures
{
    use CreatesWelfareFixtures;

    protected function qardActor(): User
    {
        return $this->staff(array_map(fn ($s) => 'qard.'.$s, ['view', 'manage', 'verify', 'recommend', 'review', 'approve', 'agreements', 'disburse', 'repay', 'followups', 'void']));
    }

    protected function draftLoan(User $actor): QardLoan
    {
        $application = $this->signedApplication($actor);
        $application->fund->update(['purpose' => 'qard', 'type' => 'restricted', 'opening_balance' => '1000.00']);

        return app(QardWorkflowService::class)->save(null, [
            'beneficiary_id' => $application->beneficiary_id, 'fund_id' => $application->fund_id, 'purpose' => 'Tools for work',
            'principal' => '500.00', 'frequency' => 'monthly', 'installment_count' => 3, 'first_due_date' => now()->addMonthsNoOverflow(2)->endOfMonth()->toDateString(),
        ], $actor);
    }

    protected function signedLoan(User $actor): QardLoan
    {
        $loan = $this->draftLoan($actor);
        $workflow = app(QardWorkflowService::class);
        $workflow->submit($loan, $actor);
        $workflow->verify($loan, ['notes' => 'Verified need', 'evidence' => ValidUpload::pdf('evidence.pdf', 200)], $actor);
        foreach (['implementation', 'secretary', 'committee'] as $stage) {
            $workflow->decide($loan, $stage, ['decision' => 'accept', 'notes' => 'Approved'], $this->qardActor());
        }
        $workflow->sign($loan, [
            'witnesses' => ['Witness One', 'Witness Two'], 'borrower_name' => 'Borrower', 'foundation_name' => 'Foundation Officer',
            'borrower_file' => ValidUpload::pdf('borrower.pdf', 200),
            'foundation_file' => ValidUpload::pdf('foundation.pdf', 200),
        ], $actor);

        return $loan->fresh();
    }

    protected function qardPayment(string $amount = '500.00'): array
    {
        return ['amount' => $amount, 'idempotency_key' => (string) Str::uuid(), 'payment_date' => now('Asia/Karachi')->toDateString(), 'payment_method' => 'cash'];
    }

    protected function activeLoan(User $actor): QardLoan
    {
        $loan = $this->signedLoan($actor);
        app(QardPaymentService::class)->record($loan, 'disbursement', $this->qardPayment(), $actor);

        return $loan->fresh();
    }
}
