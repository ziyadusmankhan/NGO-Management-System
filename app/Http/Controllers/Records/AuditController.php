<?php

namespace App\Http\Controllers\Records;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Audit\Services\AuditQuery;
use App\Domain\Audit\Services\AuditRecorder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AuditController
{
    private function filters(Request $r): array
    {
        return $r->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($r->filled('from') ? ['after_or_equal:from'] : [])], 'actor_id' => ['nullable', 'integer'], 'module' => ['nullable', 'string', 'max:40'], 'action' => ['nullable', 'string', 'max:100'], 'outcome' => ['nullable', 'in:success,blocked'], 'historical' => ['nullable', 'in:0,1'], 'reference' => ['nullable', 'string', 'max:100'], 'format' => ['nullable', 'in:print,xlsx']]);
    }

    public function index(Request $r, AuditQuery $service)
    {
        $filters = $this->filters($r);
        $query = $service->query($filters);
        $counts = ['successful' => (clone $query)->where('outcome', 'success')->count(), 'blocked' => (clone $query)->where('outcome', 'blocked')->count(), 'security' => (clone $query)->where('module', 'security')->count()];
        $events = $service->ordered($query)->paginate(30)->withQueryString();
        $actors = AuditEvent::whereNotNull('actor_id')->select('actor_id', 'actor_name')->distinct()->orderBy('actor_name')->get();
        $modules = AuditEvent::distinct()->orderBy('module')->pluck('module');
        $actions = AuditEvent::distinct()->orderBy('action')->pluck('action');

        return response()->view('records.audit.index', compact('events', 'filters', 'counts', 'actors', 'modules', 'actions'))->header('Cache-Control', 'private, no-store');
    }

    public function show(AuditEvent $event, AuditQuery $service)
    {
        $sourceLink = $service->sourceLink($event, request()->user());

        return response()->view('records.audit.show', compact('event', 'sourceLink'))->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $r, AuditQuery $service, AuditRecorder $recorder)
    {
        $filters = $this->filters($r);
        $snapshot = (int) AuditEvent::max('id');
        $query = $service->query($filters, $snapshot);
        if ((clone $query)->count() > 10000) {
            throw ValidationException::withMessages(['export' => 'Choose narrower filters: an export may contain at most 10,000 events.']);
        }
        $events = $service->ordered($query)->get();
        $format = $filters['format'] ?? 'print';
        $columns = $service->columns();
        if ($format === 'xlsx') {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Audit trail');
            $sheet->fromArray(array_values($columns), null, 'A1');
            $row = 2;
            foreach ($events as $event) {
                $values = $service->row($event);
                foreach (array_keys($columns) as $col => $key) {
                    $sheet->setCellValueExplicit([$col + 1, $row], $values[$key], DataType::TYPE_STRING);
                } $row++;
            }
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:M'.max(1, $row - 1));
            $sheet->getStyle('A1:M1')->getFont()->setBold(true);
            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setWidth(in_array($col, ['H', 'K', 'L', 'M']) ? 60 : 24);
            }
            $sheet->getStyle('A1:M'.max(1, $row - 1))->getAlignment()->setWrapText(true);
            ob_start();
            try {
                (new Xlsx($book))->save('php://output');
                $body = ob_get_contents();
            } finally {
                ob_end_clean();
                $book->disconnectWorksheets();
            }
        } else {
            $body = view('records.audit.print', compact('events', 'filters', 'snapshot'))->render();
        }
        $recorder->record('audit', 'export_generated', ['after' => ['export_count' => $events->count(), 'snapshot_id' => $snapshot, 'format' => $format, 'filters' => $filters]]);

        return response($body, 200, $format === 'xlsx' ? ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="audit-trail.xlsx"', 'Cache-Control' => 'private, no-store'] : ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
