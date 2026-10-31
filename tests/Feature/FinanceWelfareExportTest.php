<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Finance\Donations\Models\Donation;
use App\Domain\Finance\Expenses\Models\Expense;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Transactions\Models\Transaction;
use App\Domain\Organization\Services\FinanceWelfareQuery;
use App\Domain\Welfare\Assistance\Models\WelfareAssistancePayment;
use App\Domain\Welfare\Verification\Models\BeneficiaryVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class FinanceWelfareExportTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    public static function resources(): array
    {
        $cases = [];
        foreach (FinanceWelfareQuery::DEFINITIONS as $kind => [$url,$name,$permissions]) {
            $cases[$kind] = [$kind, $name, $permissions];
        }

        return $cases;
    }

    public static function files(): array
    {
        $cases = [];
        foreach (self::resources() as $kind => [$key,$name,$permissions]) {
            foreach (['pdf', 'xlsx'] as $format) {
                $cases[$kind.' '.$format] = [$key, $name, $permissions, $format];
            }
        }

        return $cases;
    }

    private function workbook($response)
    {
        $path = tempnam(sys_get_temp_dir(), 'fw-export-');
        file_put_contents($path, $response->getContent());
        try {
            return IOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    private function fixtures($actor): array
    {
        $application = $this->signedApplication($actor, [], '500.00');
        $fund = $application->fund;
        $fund->update(['type' => 'restricted']);
        $application->beneficiary->update(['phone' => '03001234567', 'cnic' => '99999-0000000-1', 'household_condition' => 'PRIVATE HOUSEHOLD NARRATIVE']);
        $assistance = $this->legacyAssistance($application, 'approved');
        $assistance->update(['amount' => '500.00', 'next_review_date' => now('Asia/Karachi')->subDay()->toDateString()]);
        $donation = Donation::create(['donation_number' => 'DON-EXPORT', 'donor_name' => 'Export donor', 'donor_type' => 'individual', 'fund_id' => $fund->id, 'amount' => '500.00', 'donation_date' => '2026-10-01', 'payment_method' => 'cash', 'purpose' => 'education', 'status' => 'received']);
        $tx = Transaction::create(['description' => 'Export test fixture', 'transaction_number' => 'TX-EXPORT-C', 'fund_id' => $fund->id, 'type' => 'donation', 'direction' => 'credit', 'amount' => '500.00', 'status' => 'posted', 'transaction_date' => '2026-10-01', 'source_type' => $donation->getMorphClass(), 'source_id' => $donation->id]);
        $expense = Expense::create(['expense_number' => 'EXP-EXPORT', 'fund_id' => $fund->id, 'category' => 'admin', 'description' => 'Office supplies', 'payee_name' => 'Export supplier', 'amount' => '10.25', 'expense_date' => '2026-10-02', 'status' => 'paid']);
        Transaction::create(['description' => 'Export test fixture', 'transaction_number' => 'TX-EXPORT-D', 'fund_id' => $fund->id, 'type' => 'expense', 'direction' => 'debit', 'amount' => '10.25', 'status' => 'posted', 'transaction_date' => '2026-10-02', 'source_type' => $expense->getMorphClass(), 'source_id' => $expense->id]);
        $payment = WelfareAssistancePayment::create(['payment_number' => 'PAY-EXPORT', 'idempotency_key' => (string) Str::uuid(), 'assistance_id' => $assistance->id, 'fund_id' => $fund->id, 'amount' => '10.25', 'payment_date' => '2026-10-03', 'payment_method' => 'cash', 'paid_to' => 'Applicant', 'status' => 'posted', 'recorded_by' => $actor->id]);
        Transaction::create(['description' => 'Export test fixture', 'transaction_number' => 'TX-EXPORT-W', 'fund_id' => $fund->id, 'type' => 'expense', 'direction' => 'debit', 'amount' => '10.25', 'status' => 'posted', 'transaction_date' => '2026-10-03', 'source_type' => $payment->getMorphClass(), 'source_id' => $payment->id]);
        BeneficiaryVerification::create(['beneficiary_id' => $application->beneficiary_id, 'application_id' => $application->id, 'verification_number' => 'VER-EXPORT', 'assigned_to' => $actor->id, 'assigned_by' => $actor->id, 'workflow_status' => 'assigned', 'visit_date' => '2026-10-02', 'priority' => 'high', 'findings' => 'PRIVATE VERIFICATION NARRATIVE']);

        return compact('application', 'fund', 'assistance', 'donation', 'tx', 'expense', 'payment');
    }

    #[DataProvider('resources')]
    public function test_exports_require_all_owning_permissions(string $kind, string $route, array $permissions): void
    {
        $this->get(route($route, ['format' => 'xlsx']))->assertRedirect(route('login'));
        $this->actingAs($this->staff([]))->get(route($route, ['format' => 'pdf']))->assertForbidden();
        if (count($permissions) > 1) {
            $this->actingAs($this->staff([$permissions[0]]))->get(route($route, ['format' => 'xlsx']))->assertForbidden();
        }
        $this->actingAs($this->staff($permissions))->getJson(route($route, ['format' => 'exe']))->assertUnprocessable();
    }

    #[DataProvider('files')]
    public function test_valid_branded_files_are_read_only_private_and_audited(string $kind, string $route, array $permissions, string $format): void
    {
        $actor = $this->staff($permissions);
        $fixture = $this->fixtures($actor);
        $before = Transaction::count();
        $donationCount = Donation::count();
        $this->actingAs($actor);
        $response = $this->get(route($route, ['format' => $format]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        if ($format === 'pdf') {
            $response->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $reader = new PdfReader(new PdfParser(StreamReader::createByString($response->getContent())));
            $this->assertGreaterThan(0, $reader->getPageCount());
        } else {
            $book = $this->workbook($response);
            $sheet = $book->getSheet(0);
            $this->assertSame(config('organization.name'), $sheet->getCell('D2')->getValue());
            $this->assertSame('C16', $sheet->getFreezePane());
            $this->assertNotEmpty($sheet->getDrawingCollection());
            $this->assertGreaterThan(0, (int) $sheet->getCell('B6')->getCalculatedValue());
            foreach ($book->getWorksheetIterator() as $tab) {
                $values = json_encode($tab->toArray());
                $this->assertStringNotContainsString('99999-0000000-1', $values);
                $this->assertStringNotContainsString('PRIVATE HOUSEHOLD NARRATIVE', $values);
                $this->assertStringNotContainsString('PRIVATE VERIFICATION NARRATIVE', $values);
                foreach ($tab->getCellCollection()->getCoordinates() as $coordinate) {
                    if ($tab->getCell($coordinate)->isFormula()) {
                        $this->assertNotContains($tab->getCell($coordinate)->getCalculatedValue(), ['#REF!', '#DIV/0!', '#VALUE!', '#NAME?', '#NUM!', '#N/A']);
                    }
                }
            }
            if ($kind === 'assistance') {
                $this->assertSame(489.75, $sheet->getCell('G16')->getValue());
                $this->assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('G16')->getDataType());
                $this->assertSame(2, $book->getSheetCount());
            }
            if ($kind === 'transactions') {
                $this->assertSame(479.5, round((float) $sheet->getCell('H6')->getCalculatedValue(), 2));
            }
            if ($kind === 'beneficiaries') {
                $this->assertSame('03001234567', $sheet->getCell('D16')->getValue());
                $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('D16')->getDataType());
            }
            $book->disconnectWorksheets();
        }
        $this->assertSame($before, Transaction::count());
        $this->assertSame($donationCount, Donation::count());
        $event = AuditEvent::where('action', str_replace('-', '_', $kind).'_exported')->latest('id')->firstOrFail();
        $this->assertSame($actor->id, $event->actor_id);
        $this->assertSame($format, $event->after_values['format']);
    }

    public function test_all_pages_filters_date_boundaries_and_source_balances_match(): void
    {
        $actor = $this->staff(['finance.view', 'finance.reports.view']);
        $fixture = $this->fixtures($actor);
        for ($i = 1; $i <= 22; $i++) {
            Transaction::create(['description' => 'Export test fixture', 'transaction_number' => 'TX-MANY-'.$i, 'fund_id' => $fixture['fund']->id, 'type' => 'donation', 'direction' => 'credit', 'amount' => '0.10', 'status' => 'posted', 'transaction_date' => '2026-10-04']);
        }
        $book = $this->workbook($this->actingAs($actor)->get(route('finance.reports.export', ['format' => 'xlsx', 'fund_id' => $fixture['fund']->id, 'date_from' => '2026-10-04', 'date_to' => '2026-10-04', 'page' => 2]))->assertOk());
        $sheet = $book->getSheet(0);
        $this->assertSame(22, $sheet->getCell('B6')->getCalculatedValue());
        $this->assertEquals(2.20, $sheet->getCell('D6')->getCalculatedValue());
        $this->assertSame(37, $sheet->getHighestDataRow());
        $this->assertStringContainsString('2026-10-04', $sheet->getCell('G10')->getValue());
        $book->disconnectWorksheets();
        $this->getJson(route('finance.reports.export', ['format' => 'pdf', 'date_from' => '2026-10-05', 'date_to' => '2026-10-04']))->assertUnprocessable();
    }

    public function test_archive_filters_empty_exports_and_safe_spreadsheet_text(): void
    {
        $actor = $this->staff(['finance.view']);
        $fund = Fund::create(['purpose' => 'general_welfare', 'name' => 'Archived donor fund', 'fund_reference' => 'FND-OLD', 'opening_balance' => '10.25', 'status' => 'inactive']);
        $fund->delete();
        $book = $this->workbook($this->actingAs($actor)->get(route('finance.funds.export', ['format' => 'xlsx', 'status' => 'archived']))->assertOk());
        $this->assertSame(1, $book->getSheet(0)->getCell('B6')->getCalculatedValue());
        $book->disconnectWorksheets();
        $book = $this->workbook($this->get(route('finance.funds.export', ['format' => 'xlsx']))->assertOk());
        $this->assertSame(0, $book->getSheet(0)->getCell('B6')->getCalculatedValue());
        $book->disconnectWorksheets();
        $fund = Fund::create(['purpose' => 'general_welfare', 'name' => '=HYPERLINK("https://example.test","Sample")', 'fund_reference' => 'FND-FORMULA', 'opening_balance' => '0.00', 'status' => 'active']);
        $book = $this->workbook($this->get(route('finance.funds.export', ['format' => 'xlsx']))->assertOk());
        $this->assertSame(DataType::TYPE_STRING, $book->getSheet(0)->getCell('B16')->getDataType());
        $book->disconnectWorksheets();
    }

    public function test_agreement_scope_and_review_permissions_do_not_grant_finance_or_evidence_access(): void
    {
        $actor = $this->staff(['welfare.agreements.view']);
        $first = $this->signedApplication($actor);
        $second = $this->signedApplication($actor);
        $this->actingAs($actor)->get(route('welfare.applications.agreement.show', $first))->assertOk()->assertSee('application_id='.$first->id, false);
        $book = $this->workbook($this->get(route('welfare.agreements.export', ['format' => 'xlsx', 'application_id' => $first->id]))->assertOk());
        $this->assertSame(1, $book->getSheet(0)->getCell('B6')->getCalculatedValue());
        $this->assertStringNotContainsString($second->application_number, json_encode($book->getSheet(0)->toArray()));
        $book->disconnectWorksheets();
        $this->get(route('finance.donations.export', ['format' => 'xlsx']))->assertForbidden();
        $this->actingAs($this->staff(['welfare.assistance.view']))->get(route('welfare.agreements.export', ['format' => 'xlsx']))->assertForbidden();
    }
}
