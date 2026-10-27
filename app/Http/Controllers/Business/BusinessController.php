<?php

namespace App\Http\Controllers\Business;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessCategory;
use App\Domain\Business\Models\BusinessCategoryEvent;
use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Business\Models\BusinessMovement;
use App\Domain\Business\Services\BusinessMonitoringService;
use App\Domain\Business\Services\BusinessPaymentService;
use App\Domain\Business\Services\BusinessReportService;
use App\Domain\Business\Services\BusinessWorkflowService;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Funds\Services\FundBalanceService;
use App\Domain\Finance\Funds\Services\FundPurposeService;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Welfare\Beneficiaries\Models\Beneficiary;
use App\Http\Requests\Business\BusinessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BusinessController
{
    public function __construct(private BusinessWorkflowService $workflow, private BusinessPaymentService $payments) {}

    public function index(Request $request)
    {
        $applications = BusinessApplication::with(['beneficiary', 'fund', 'reminder'])->when($request->boolean('archived'), fn ($q) => $q->onlyTrashed())
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('number', 'like', '%'.$request->string('search').'%')->orWhere('name', 'like', '%'.$request->string('search').'%')
                    ->orWhereHas('beneficiary', fn ($b) => $b->where('full_name', 'like', '%'.$request->string('search').'%'));
            }))->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('support_type'), fn ($q) => $q->where('support_type', $request->string('support_type')->toString()))
            ->when($request->boolean('reminders'), fn ($q) => $q->whereHas('reminder', fn ($r) => $r->whereNull('resolved_at')))
            ->latest('id')->paginate(15)->withQueryString();

        return view('business.applications.index', compact('applications'));
    }

    private function form(?BusinessApplication $application = null)
    {
        $beneficiaries = Beneficiary::where('verification_status', 'verified')->where('approval_status', 'approved')->orderBy('full_name')->get();
        $funds = Fund::where('status', 'active')->get()->filter(fn ($f) => app(FundPurposeService::class)->allowsBusiness($f, 'repayable'));
        $categories = BusinessCategory::where('active', true)->orderBy('name')->get();

        return view('business.applications.create', compact('application', 'beneficiaries', 'funds', 'categories'));
    }

    public function create()
    {
        return $this->form();
    }

    public function edit(BusinessApplication $application)
    {
        abort_unless(in_array($application->status, ['draft', 'rejected']), 403);

        return $this->form($application);
    }

    public function store(BusinessRequest $request)
    {
        return $this->back($this->workflow->save(null, $request->validated(), $request->user()), 'Draft saved. Review the budget and submit for verification.');
    }

    public function update(BusinessRequest $request, BusinessApplication $application)
    {
        return $this->back($this->workflow->save($application, $request->validated(), $request->user()), 'Draft saved.');
    }

    private function back(BusinessApplication $application, string $message)
    {
        return redirect()->route('business.applications.show', $application)->with('success', $message);
    }

    public function show(BusinessApplication $application)
    {
        $application->load(['beneficiary', 'fund', 'category', 'creator', 'reviews.actor', 'documents', 'agreements', 'movements.transaction', 'movements.budgetItem', 'movements.handover', 'monitorings.actor', 'events.actor', 'reminder']);
        $budget = $application->currentBudget()->get();
        $committees = Committee::where('type', 'decision')->where('status', 'active')->with(['members' => fn ($q) => $q->where('members.status', 'active')->wherePivot('status', 'active')->whereNull('committee_members.ended_at')])->get();
        $termsHash = $this->workflow->hash($this->workflow->terms($application));
        $finalTerms = $this->payments->finalTerms($application);
        $finalHash = $this->workflow->hash($finalTerms);
        $balance = app(FundBalanceService::class)->balance($application->fund);
        $installments = $application->installments()->where('funding_version', $application->funding_version)->orderBy('sequence')->get();

        return view('business.applications.show', compact('application', 'budget', 'committees', 'termsHash', 'finalHash', 'finalTerms', 'balance', 'installments'));
    }

    public function action(BusinessRequest $request, BusinessApplication $application, string $action)
    {
        $data = $request->validated();
        $actor = $request->user();
        match ($action) {
            'submit' => $this->workflow->submit($application, $data, $actor),
            'verify' => $this->workflow->verify($application, $data, $actor),
            'sign' => $this->workflow->sign($application, $data, $actor),
            'revise', 'cancel', 'close', 'archive' => $this->workflow->transition($application, $action, $data, $actor),
            'disburse' => $this->payments->record($application, 'disbursement', $data, $actor),
            'repay' => $this->payments->record($application, 'repayment', $data, $actor),
            'finalize' => $this->payments->finalize($application, $data, $actor),
            'handover', 'start', 'monitor' => app(BusinessMonitoringService::class)->record($application, $action, $data, $actor),
            default => abort(404),
        };
        if ($action === 'archive') {
            return redirect()->route('business.applications.index', ['archived' => 1])->with('success', 'Case archived. Its history is retained.');
        }

        return $this->back($application, match ($action) {
            'disburse' => 'Payment recorded once in the ledger.', 'repay' => 'Principal returned to the original fund.',
            'finalize' => 'Funding finalized. Unused approval released.', 'sign' => 'Both signed copies recorded. Authorized funding can begin.',
            default => str($action)->headline().' recorded successfully.',
        });
    }

    public function decide(BusinessRequest $request, BusinessApplication $application, string $stage)
    {
        $this->workflow->decide($application, $stage, $request->validated(), $request->user());

        return $this->back($application, 'Decision recorded.');
    }

    public function void(BusinessRequest $request, BusinessApplication $application, BusinessMovement $movement)
    {
        abort_unless($movement->business_application_id === $application->id, 404);
        $this->payments->void($application, $movement, $request->validated(), $request->user());

        return $this->back($application, 'Correction recorded. Review the updated funding and principal balances.');
    }

    public function document(BusinessApplication $application, BusinessDocument $document)
    {
        abort_unless($document->business_application_id === $application->id && Storage::disk('private')->exists($document->path), 404);
        app(ProcurementGuard::class)->authorizeEvidence('business', $document->movement_id, request()->user());

        return Storage::disk('private')->download($document->path, $application->number.'-'.$document->kind.'.'.pathinfo($document->path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function agreement(Request $request, BusinessApplication $application)
    {
        $record = $request->filled('record') ? $application->agreements()->findOrFail($request->integer('record')) : null;
        abort_unless($record || in_array($application->status, ['approved', 'signed', 'closed']), 403);
        $final = $record ? $record->kind === 'final' : $request->boolean('final');
        $terms = $record?->terms ?? ($final ? $this->payments->finalTerms($application) : $this->workflow->terms($application));

        return view('business.applications.agreement', compact('application', 'record', 'final', 'terms'));
    }

    public function categories()
    {
        return view('business.categories', ['categories' => BusinessCategory::orderBy('name')->get()]);
    }

    public function saveCategory(Request $request, ?BusinessCategory $category = null)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('business_categories')->ignore($category?->id)], 'active' => ['required', 'boolean']]);
        DB::transaction(function () use ($category, $data, $request) {
            if ($category) {
                $category = BusinessCategory::whereKey($category->id)->lockForUpdate()->firstOrFail();
            }
            $before = $category?->only(['name', 'active']) ?? [];
            $category ??= new BusinessCategory;
            $category->fill($data);
            $category->save();
            if ($before !== $category->only(['name', 'active'])) {
                BusinessCategoryEvent::create(['category_id' => $category->id, 'actor_id' => $request->user()->id, 'before_values' => $before, 'after_values' => $category->only(['name', 'active'])]);
            }
        }, 5);

        return redirect()->route('business.categories')->with('success', 'Category saved. Existing case snapshots are retained.');
    }

    public function report(Request $request, BusinessReportService $reports)
    {
        $filters = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'fund_id' => ['nullable', 'integer', 'exists:funds,id'], 'support_type' => ['nullable', 'in:grant,repayable'], 'format' => ['nullable', 'in:print,xlsx']]);
        $report = $reports->report(array_filter($filters, fn ($v) => $v !== null));
        if (($filters['format'] ?? null) === 'xlsx') {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Small Business');
            $sheet->fromArray(['Business', 'Name', 'Beneficiary', 'Type', 'Original fund', 'Current approval', 'Released to end date', 'Recovered to end date', 'Outstanding to end date', 'Released in period', 'Recovered in period', 'Current status'], null, 'A1');
            $row = 2;
            foreach ($report['rows'] as $record) {
                $a = $record['application'];
                $values = [$a->number, $a->name, $a->beneficiary?->full_name, $a->support_type, $a->fund?->name, $a->approved_amount,
                    Money::decimal($record['released']), Money::decimal($record['recovered']), Money::decimal($record['outstanding']), Money::decimal($record['period_released']), Money::decimal($record['period_recovered']), $a->status];
                foreach ($values as $i => $value) {
                    $sheet->setCellValueExplicit([$i + 1, $row], (string) $value, DataType::TYPE_STRING);
                }
                $row++;
            }
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:L'.max(1, $row - 1));
            $sheet->getStyle('A1:L1')->getFont()->setBold(true);
            foreach (range('A', 'L') as $column) {
                $sheet->getColumnDimension($column)->setWidth(24);
            }
            $capital = $book->createSheet()->setTitle('Current pooled capital');
            $capital->fromArray(['Fund', 'Purpose', 'Ledger balance', 'All current commitments', 'Available after commitments', 'Principal recovered to fund'], null, 'A1');
            $capitalRow = 2;
            foreach ($report['capital'] as $entry) {
                $values = [$entry['fund']->name, $entry['fund']->purpose, $entry['summary']['balance'], $entry['summary']['allocated'], $entry['summary']['available'], $entry['summary']['recovered']];
                foreach ($values as $i => $value) {
                    $capital->setCellValueExplicit([$i + 1, $capitalRow], (string) $value, DataType::TYPE_STRING);
                }
                $capitalRow++;
            }
            $capital->getStyle('A1:F1')->getFont()->setBold(true);
            $capital->freezePane('A2');
            foreach (range('A', 'F') as $column) {
                $capital->getColumnDimension($column)->setWidth(30);
            }
            $notes = $book->createSheet()->setTitle('Report notes');
            $notes->setCellValueExplicit('A1', 'Period: '.$report['from'].' to '.$report['to'].'. PKR. Money is exported as exact decimal text. Balances exclude currently void movements; status and approval show current records. Fund history is pooled, not donor-specific allocation.', DataType::TYPE_STRING);

            return response()->streamDownload(function () use ($book) {
                (new Xlsx($book))->save('php://output');
                $book->disconnectWorksheets();
            }, 'small-business-'.$report['to'].'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
        }

        return view('business.report', $report + ['funds' => Fund::withTrashed()->orderBy('name')->get(), 'print' => ($filters['format'] ?? null) === 'print']);
    }
}
