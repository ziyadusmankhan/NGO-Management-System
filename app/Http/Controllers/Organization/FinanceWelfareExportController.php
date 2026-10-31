<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Organization\Services\DirectoryExport;
use App\Domain\Organization\Services\FinanceWelfareQuery;
use App\Domain\Organization\Services\FinanceWelfareReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceWelfareExportController
{
    public function __invoke(Request $request, FinanceWelfareQuery $queries, FinanceWelfareReport $reports, DirectoryExport $renderer, AuditRecorder $audit)
    {
        $kind = $request->route('kind');
        $definition = FinanceWelfareQuery::DEFINITIONS[$kind] ?? abort(404);
        foreach ($definition[2] as $permission) {
            abort_unless($request->user()?->hasPermission($permission), 403);
        }
        $format = $request->validate(['format' => ['required', Rule::in(['pdf', 'xlsx'])]])['format'];
        $report = $reports->build($kind, $queries->filters($request, $kind), $request->user());
        $bytes = $renderer->{$format}($report);
        $module = str_starts_with($definition[0], 'finance/') ? 'finance' : 'welfare';
        $audit->record($module, str_replace('-', '_', $kind).'_exported', ['summary' => $report['title'].' exported',
            'after' => ['format' => $format, 'export_count' => $report['count']]]);

        return response($bytes, 200, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="foundation-'.$kind.'-'.$report['generated_at']->format('Y-m-d-His').'.'.$format.'"',
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
