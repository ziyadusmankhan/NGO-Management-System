<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Finance\Support\Money;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementDocument;
use App\Domain\Procurement\Models\ProcurementPolicy;
use App\Domain\Procurement\Models\ProcurementSupplier;
use App\Domain\Procurement\Services\ProcurementOperationsService;
use App\Domain\Procurement\Services\ProcurementPaymentService;
use App\Domain\Procurement\Services\ProcurementReportService;
use App\Domain\Procurement\Services\ProcurementWorkflowService;
use App\Domain\Projects\Models\Project;
use App\Http\Requests\Procurement\ProcurementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProcurementController
{
    public function __construct(private ProcurementWorkflowService $w) {}

    private function options(): array
    {
        $a = request()->user();

        return ['suppliers' => ProcurementSupplier::orderBy('name')->get(), 'funds' => Fund::where('status', 'active')->get(), 'projects' => $a->hasPermission('projects.view') ? Project::whereIn('status', ['approved', 'in_progress', 'completed'])->get() : collect(), 'businesses' => $a->hasPermission('business.view') ? BusinessApplication::whereIn('status', ['approved', 'signed'])->where('funding_status', 'open')->get() : collect()];
    }

    public function index(Request $r)
    {
        $query = app(ProcurementReportService::class)->visible($r->user())->with('reminder')->when(! $r->boolean('archived'), fn ($q) => $q->whereNull('deleted_at'))->when($r->filled('status'), fn ($q) => $q->where('status', $r->string('status')))->when($r->filled('search'), fn ($q) => $q->where('number', 'like', '%'.$r->string('search').'%'));

        return view('operations.procurement.index', ['purchases' => $query->latest('id')->paginate(20)->withQueryString()]);
    }

    public function create(Request $r)
    {
        $this->w->authorize($r->user(), 'manage');
        $type = $r->input('parent_type', 'office');
        abort_unless(in_array($type, ['office', 'project', 'business']), 404);
        $parent = null;
        $budgets = collect();
        if ($type !== 'office' && $r->filled('parent_id')) {
            abort_unless($r->user()->hasPermission($type === 'project' ? 'projects.view' : 'business.view'), 403);
            $parent = $type === 'project' ? Project::findOrFail($r->integer('parent_id')) : BusinessApplication::findOrFail($r->integer('parent_id'));
            $budgets = $type === 'project' ? $parent->budgets($parent->approved_revision)->where('kind', 'purchase')->get() : $parent->currentBudget()->where('release_method', '!=', 'cash')->get();
        }

        return view('operations.procurement.form', ['purchase' => null, 'revision' => null, 'type' => $type, 'parent' => $parent, 'budgets' => $budgets] + $this->options());
    }

    public function edit(Procurement $procurement)
    {
        $this->w->authorize(request()->user(), 'manage', $procurement);
        abort_unless($procurement->proposal()->stage === 'draft' && ! $procurement->trashed(), 403);
        $parent = $procurement->parentRecord();
        $budgets = $parent ? ($procurement->parent_type === 'project' ? $parent->budgets($parent->approved_revision)->where('kind', 'purchase')->get() : $parent->currentBudget()->where('release_method', '!=', 'cash')->get()) : collect();

        return view('operations.procurement.form', ['purchase' => $procurement, 'revision' => $procurement->proposal(), 'type' => $procurement->parent_type, 'parent' => $parent, 'budgets' => $budgets] + $this->options());
    }

    public function store(ProcurementRequest $r)
    {
        return $this->back($this->w->save(null, $r->validated(), $r->user()));
    }

    public function update(ProcurementRequest $r, Procurement $procurement)
    {
        return $this->back($this->w->save($procurement, $r->validated(), $r->user()));
    }

    private function back(Procurement $p)
    {
        return redirect()->route('operations.procurement.show', $p)->with('success', 'Action recorded. Review the updated purchase details.');
    }

    public function show(Procurement $procurement)
    {
        $this->w->authorize(request()->user(), 'view', $procurement);
        $procurement->load(['fund', 'revisions', 'quotes', 'reviews.actor', 'receipts.actor', 'invoices.lines', 'returns', 'movements', 'documents', 'events.actor', 'reminder']);
        $committees = Committee::where('type', 'decision')->where('status', 'active')->with('members')->get();

        return view('operations.procurement.show', ['purchase' => $procurement, 'revision' => $procurement->proposal(), 'approved' => $procurement->approved(), 'committees' => $committees] + $this->options());
    }

    public function action(ProcurementRequest $r, Procurement $procurement, string $action)
    {
        $d = $r->validated();
        $a = $r->user();
        $ops = app(ProcurementOperationsService::class);
        match ($action) {
            'quote' => $this->w->quote($procurement, $d, $a),'receive' => $ops->receive($procurement, $d, $a),'invoice' => $ops->invoice($procurement, $d, $a),'return' => $ops->returnGoods($procurement, $d, $a),'pay','refund' => app(ProcurementPaymentService::class)->record($procurement, $action, $d, $a),'void' => $ops->correct($procurement, $d, $a),default => $this->w->transition($procurement, $action, $d, $a)
        };
        app(ProcurementReportService::class)->refresh($procurement);

        return $this->back($procurement);
    }

    public function decide(ProcurementRequest $r, Procurement $procurement, string $stage)
    {
        $this->w->decide($procurement, $stage, $r->validated(), $r->user());

        return $this->back($procurement);
    }

    public function document(Procurement $procurement, ProcurementDocument $document)
    {
        $this->w->authorize(request()->user(), 'view', $procurement);
        abort_unless($document->procurement_id === $procurement->id && Storage::disk('private')->exists($document->path), 404);

        return Storage::disk('private')->download($document->path, $document->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function order(Procurement $procurement)
    {
        $this->w->authorize(request()->user(), 'view', $procurement);
        abort_unless($procurement->approved()?->issued_at, 404);

        return view('operations.procurement.order', ['purchase' => $procurement, 'revision' => $procurement->approved()]);
    }

    public function settings()
    {
        return view('operations.procurement.settings', ['policy' => ProcurementPolicy::latest('id')->first(), 'suppliers' => ProcurementSupplier::orderBy('name')->get()]);
    }

    public function supplier(Request $r, ?ProcurementSupplier $supplier = null)
    {
        $this->w->authorize($r->user(), 'suppliers');
        $d = $r->validate(['name' => ['required', 'string', 'max:255'], 'contact' => ['nullable', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:5000'], 'registration' => ['nullable', 'string', 'max:255'], 'active' => ['required', 'boolean']]);
        $d['name_key'] = mb_strtolower(preg_replace('/\s+/u', ' ', trim($d['name'])));
        $d['name'] = trim($d['name']);
        if (ProcurementSupplier::where('name_key', $d['name_key'])->when($supplier, fn ($q) => $q->whereKeyNot($supplier->id))->exists()) {
            $this->w->fail('name', 'This supplier name already exists.');
        }
        DB::transaction(function () use ($supplier, $d, $r) {
            $before = $supplier?->toArray() ?? [];
            $supplier ??= new ProcurementSupplier;
            $supplier->fill($d)->save();
            $this->w->event(null, $r->user(), 'supplier_saved', $before, $supplier->toArray());
        });

        return back()->with('success', 'Supplier saved. Existing purchase snapshots are retained.');
    }

    public function policy(Request $r)
    {
        $this->w->authorize($r->user(), 'policy');
        $d = $r->validate(['quote_count' => ['required', 'integer', 'min:1', 'max:10']]);
        DB::transaction(function () use ($d, $r) {
            $p = ProcurementPolicy::create($d + ['actor_id' => $r->user()->id]);
            $this->w->event(null, $r->user(), 'policy_saved', [], $p->toArray());
        });

        return back()->with('success', 'New submissions will use this quotation count.');
    }

    public function reports(Request $r)
    {
        $this->w->authorize($r->user(), 'reports');
        $filters = $r->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($r->filled('from') ? ['after_or_equal:from'] : [])], 'supplier_id' => ['nullable', 'integer'], 'fund_id' => ['nullable', 'integer'], 'parent_type' => ['nullable', 'in:office,project,business'], 'parent_id' => ['nullable', 'integer'], 'status' => ['nullable', 'in:draft,approved,ordered,closed,cancelled'], 'format' => ['nullable', 'in:print,xlsx']]);
        $rows = app(ProcurementReportService::class)->rows($r->user(), $filters);
        $keys = ['approved', 'invoiced', 'unpaid', 'paid', 'refunds', 'net', 'reserved', 'refund_due'];
        $totals = [];
        foreach ($keys as $k) {
            $totals[$k] = Money::total($rows->pluck($k));
        }
        if (($filters['format'] ?? null) === 'xlsx') {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $headers = ['number', 'title', 'supplier', 'status', 'parent', 'fund', ...$keys, 'accepted_quantity', 'returned_quantity', 'delivery_status'];
            $sheet->fromArray($headers, null, 'A1');
            $n = 2;
            foreach ($rows as $row) {
                foreach ($headers as $col => $key) {
                    $sheet->setCellValueExplicit([$col + 1, $n], $row[$key], in_array($key, $keys) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                } $n++;
            }
            $sheet->setCellValue('A'.$n, 'TOTAL');
            foreach ($headers as $col => $key) {
                if (isset($totals[$key])) {
                    $sheet->setCellValueExplicit([$col + 1, $n], $totals[$key], DataType::TYPE_NUMERIC);
                }
            }
            $sheet->getStyle('G2:N'.$n)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:Q'.max(1, $n - 1));
            foreach (range('A', 'Q') as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }

            return response()->streamDownload(function () use ($book) {
                (new Xlsx($book))->save('php://output');
                $book->disconnectWorksheets();
            },'procurement.xlsx',['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        return view('operations.procurement.reports',compact('rows','filters','totals') + $this->options());
    }
}
