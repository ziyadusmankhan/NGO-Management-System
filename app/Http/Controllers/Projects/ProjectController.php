<?php

namespace App\Http\Controllers\Projects;

use App\Domain\Committees\Models\Committee;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Finance\Funds\Models\Fund;
use App\Domain\Procurement\Services\ProcurementGuard;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectCategory;
use App\Domain\Projects\Models\ProjectDocument;
use App\Domain\Projects\Models\ProjectEvent;
use App\Domain\Projects\Models\ProjectMovement;
use App\Domain\Projects\Models\ProjectRevision;
use App\Domain\Projects\Services\ProjectMonitoringService;
use App\Domain\Projects\Services\ProjectPaymentService;
use App\Domain\Projects\Services\ProjectReportService;
use App\Domain\Projects\Services\ProjectWorkflowService;
use App\Domain\Users\Models\User;
use App\Http\Requests\Projects\ProjectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProjectController
{
    public function __construct(private ProjectWorkflowService $workflow, private ProjectPaymentService $payments) {}

    public function index(Request $request)
    {
        $projects = Project::with(['fund', 'reminder'])->when($request->boolean('archived'), fn ($q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')->orWhere('number', 'like', '%'.$request->string('search').'%')))
            ->when($request->boolean('reminders'), fn ($q) => $q->whereHas('reminder', fn ($q) => $q->whereNull('resolved_at')))->latest('id')->paginate(15)->withQueryString();

        return view('projects.index', compact('projects'));
    }

    private function options(): array
    {
        return ['categories' => ProjectCategory::orderBy('name')->get(), 'funds' => Fund::where('status', 'active')->orderBy('name')->get(), 'users' => User::where('status', 'active')->orderBy('name')->get(),
            'committees' => Committee::where('status', 'active')->with(['members' => fn ($q) => $q->where('members.status', 'active')->wherePivot('status', 'active')->whereNull('committee_members.ended_at')])->get()];
    }

    public function create()
    {
        return view('projects.form', ['project' => null, 'revision' => null, 'budget' => collect()] + $this->options());
    }

    public function edit(Project $project)
    {
        $revision = $project->proposal();
        abort_unless($revision->stage === 'draft', 403);
        $this->workflow->authorize(request()->user(), $project->approved_revision ? 'amend' : 'manage');

        return view('projects.form', ['project' => $project, 'revision' => $revision, 'budget' => $project->budgets()->get()] + $this->options());
    }

    public function store(ProjectRequest $request)
    {
        return $this->back($this->workflow->save(null, $request->validated(), $request->user()), 'Project draft saved. Review and submit it when ready.');
    }

    public function update(ProjectRequest $request, Project $project)
    {
        return $this->back($this->workflow->save($project, $request->validated(), $request->user()), 'Draft revision saved.');
    }

    private function back(Project $p, string $message)
    {
        return redirect()->route('projects.show', $p)->with('success', $message);
    }

    public function show(Project $project)
    {
        $project->load(['fund', 'creator', 'revisions', 'reviews.actor', 'documents', 'progress.actor', 'events.actor', 'movements.transaction', 'reminder']);
        $revision = $project->proposal();
        $approved = $project->approved();
        $budget = $project->budgets()->with('item')->get();
        $approvedBudget = $project->approved_revision ? $project->budgets($project->approved_revision)->with('item')->get() : collect();

        return view('projects.show', compact('project', 'revision', 'approved', 'budget', 'approvedBudget') + $this->options());
    }

    public function action(ProjectRequest $request, Project $project, string $action)
    {
        $d = $request->validated();
        $actor = $request->user();
        match ($action) {
            'submit' => $this->workflow->submit($project, $d, $actor),'verify' => $this->workflow->verify($project, $d, $actor),
            'amend','reopen','withdraw','resume','close','cancel','archive' => $this->workflow->transition($project, $action, $d, $actor),
            'start','monitor','complete' => app(ProjectMonitoringService::class)->record($project, $action, $d, $actor),
            'pay','refund' => $this->payments->record($project, $action === 'pay' ? 'payment' : 'refund', $d, $actor),
            'dedicated-fund' => $this->workflow->dedicatedFund($project, $d, $actor),default => abort(404),
        };
        app(ProjectMonitoringService::class)->refresh($project->fresh());

        return $this->back($project, str($action)->headline().' recorded successfully.');
    }

    public function decide(ProjectRequest $request, Project $project, string $stage)
    {
        $this->workflow->decide($project, $stage, $request->validated(), $request->user());

        return $this->back($project, 'Decision recorded. Approval creates no payment.');
    }

    public function void(ProjectRequest $request, Project $project, ProjectMovement $movement)
    {
        $this->payments->void($project, $movement, $request->validated(), $request->user());

        return $this->back($project, 'Movement voided with retained history.');
    }

    public function document(Project $project, ProjectDocument $document)
    {
        abort_unless((int) $document->project_id === (int) $project->id, 404);
        app(ProcurementGuard::class)->authorizeEvidence('project', $document->movement_id, request()->user());
        abort_unless(Storage::disk('private')->exists($document->path), 404);

        return app(DocumentService::class)->sourceDownload(auth()->user(), 'projects:'.$document->id);
    }

    public function categories()
    {
        return view('projects.categories', ['categories' => ProjectCategory::orderBy('name')->get()]);
    }

    public function saveCategory(Request $request, ?ProjectCategory $category = null)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique('project_categories')->ignore($category?->id)], 'purpose' => ['required', Rule::in(config('projects.purposes'))], 'active' => ['required', 'boolean']]);
        DB::transaction(function () use ($category, $data, $request) {
            if ($category) {
                $category = ProjectCategory::lockForUpdate()->findOrFail($category->id);
                if ($category->purpose !== $data['purpose'] && ProjectRevision::where('category_id', $category->id)->exists()) {
                    $this->workflow->fail('purpose', 'A used category keeps its funding classification. Create a new category for a different purpose.');
                }
            }
            $before = $category?->toArray() ?? [];
            $category ??= new ProjectCategory;
            $category->fill($data)->save();
            ProjectEvent::create(['actor_id' => $request->user()->id, 'action' => 'category_saved', 'before_values' => $before, 'after_values' => $category->toArray()]);
        });

        return redirect()->route('projects.categories')->with('success', 'Category saved.');
    }

    public function reports(Request $request)
    {
        $filters = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'status' => ['nullable', 'in:draft,approved,in_progress,completed,closed,cancelled'], 'category_id' => ['nullable', 'integer'], 'responsible_id' => ['nullable', 'integer'], 'fund_id' => ['nullable', 'integer'], 'format' => ['nullable', 'in:print,xlsx']]);
        $service = app(ProjectReportService::class);
        $rows = $service->rows($filters);
        $totals = $service->totals($rows);
        if (($filters['format'] ?? null) === 'xlsx') {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Projects');
            $keys = ['number', 'name', 'status', 'category', 'responsible', 'fund', 'original_budget', 'current_budget', 'gross', 'refunds', 'net', 'remaining', 'commitment', 'progress', 'review_due', 'completion_overdue'];
            $sheet->fromArray(array_map(fn ($k) => str($k)->headline()->toString(), $keys), null, 'A1');
            $n = 2;
            foreach ($rows as $row) {
                foreach ($keys as $i => $key) {
                    $sheet->setCellValueExplicit([$i + 1, $n], $row[$key] ?? '', array_key_exists($key, $totals) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                } $n++;
            }
            $sheet->setCellValue('A'.$n, 'TOTAL');
            foreach ($keys as $i => $key) {
                if (isset($totals[$key])) {
                    $sheet->setCellValueExplicit([$i + 1, $n], $totals[$key], DataType::TYPE_NUMERIC);
                }
            }
            $sheet->getStyle('G2:M'.$n)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A1:P1')->getFont()->setBold(true);
            $sheet->getStyle('A'.$n.':P'.$n)->getFont()->setBold(true);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:P'.max(1, $n - 1));
            foreach (range('A', 'P') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            $sheet->setCellValue('A'.($n + 2), 'Payments/refunds use the selected date range and current posted status. Budgets, remaining authority and commitments are current. Remaining = approved minus lifetime net spending; closed commitments are zero.');

            return response()->streamDownload(function () use ($book) {
                (new Xlsx($book))->save('php://output');
                $book->disconnectWorksheets();
            }, 'projects.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
        }

        return view('projects.reports', compact('rows', 'totals', 'filters') + $this->options());
    }
}
