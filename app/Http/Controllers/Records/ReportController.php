<?php

namespace App\Http\Controllers\Records;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Reports\Models\ReportRun;
use App\Domain\Reports\Services\ReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController
{
    public function index(Request $request, ReportBuilder $builder)
    {
        $types = $builder->allowed($request->user());
        $runs = ReportRun::with('generator')->whereIn('type', array_keys($types))->latest('generated_at')->paginate(20);

        return view('records.reports.index', compact('types', 'runs'));
    }

    public function store(Request $request, ReportBuilder $builder)
    {
        $data = $request->validate(['type' => ['required', Rule::in(array_keys(ReportBuilder::TYPES))], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])]]);
        abort_unless(isset($builder->allowed($request->user())[$data['type']]), 403);
        $snapshot = $builder->build($data['type'], $data);
        $run = DB::transaction(function () use ($data, $snapshot, $request) {
            $run = ReportRun::create(['type' => $data['type'], 'title' => $snapshot['title'], 'period_from' => $data['from'] ?? null, 'period_to' => $data['to'] ?? null, 'filters' => $data, 'snapshot' => $snapshot, 'generated_by' => $request->user()->id, 'generated_at' => now()]);
            $run->update(['number' => sprintf('RPT-%05d', $run->id)]);

            return $run;
        });

        return redirect()->route('records.reports.show', $run)->with('success', 'Report generated from live records.');
    }

    private function authorizeRun(Request $request, ReportRun $report, ReportBuilder $builder): void
    {
        abort_unless(isset($builder->allowed($request->user())[$report->type]), 403);
    }

    public function show(Request $request, ReportRun $report, ReportBuilder $builder)
    {
        $this->authorizeRun($request, $report, $builder);
        $report->load('generator');

        return response()->view('records.reports.show', compact('report'))->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request, ReportRun $report, ReportBuilder $builder, AuditRecorder $audit)
    {
        $this->authorizeRun($request, $report, $builder);
        $format = $request->validate(['format' => ['required', Rule::in(['print', 'xlsx'])]])['format'];
        if ($format === 'print') {
            $body = view('records.reports.print', compact('report'))->render();
            $audit->record('reports', 'report_exported', ['subject_type' => ReportRun::class, 'subject_id' => $report->id, 'subject_reference' => $report->number, 'after' => ['format' => $format, 'type' => $report->type]]);

            return response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
        }
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Report');
        $sheet->fromArray([['Report', $report->title], ['Reference', $report->number], ['Generated', $report->generated_at->timezone('Asia/Karachi')->format('d M Y H:i')], ['Period', trim(($report->period_from?->format('d M Y') ?? 'Beginning').' – '.($report->period_to?->format('d M Y') ?? 'Today'))]], null, 'A1');
        $row = 6;
        $sheet->fromArray(['Metric', 'Value'], null, 'A'.$row++);
        foreach ($report->snapshot['metrics'] as $metric) {
            $sheet->setCellValueExplicit('A'.$row, $metric['label'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$row, (string) $metric['value'], DataType::TYPE_STRING);
            $row++;
        }
        $row++;
        $sheet->fromArray(['Breakdown', 'Count'], null, 'A'.$row++);
        foreach ($report->snapshot['rows'] as $item) {
            $sheet->setCellValueExplicit('A'.$row, $item['label'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$row, (string) $item['count'], DataType::TYPE_NUMERIC);
            $row++;
        }
        $sheet->getStyle('A6:B6')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(45);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getStyle('A1:B'.$row)->getAlignment()->setWrapText(true);
        ob_start();
        try {
            (new Xlsx($book))->save('php://output');
            $body = ob_get_contents();
        } finally {
            ob_end_clean();
            $book->disconnectWorksheets();
        }
        $audit->record('reports', 'report_exported', ['subject_type' => ReportRun::class, 'subject_id' => $report->id, 'subject_reference' => $report->number, 'after' => ['format' => $format, 'type' => $report->type]]);

        return response($body, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="'.strtolower($report->number).'.xlsx"', 'Cache-Control' => 'private, no-store']);
    }
}
