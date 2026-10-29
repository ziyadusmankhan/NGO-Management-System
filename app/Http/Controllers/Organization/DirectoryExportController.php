<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Organization\Services\DirectoryExport;
use App\Domain\Organization\Services\DirectoryQuery;
use App\Domain\Organization\Services\DirectoryReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DirectoryExportController
{
    public function __invoke(Request $request, DirectoryQuery $queries, DirectoryReport $reports, DirectoryExport $exporter, AuditRecorder $audit)
    {
        $kind = $request->route('kind');
        $permission = ['members' => 'members.view', 'committees' => 'committees.view', 'users' => 'users.view', 'roles' => 'roles.manage'][$kind] ?? null;
        abort_unless($permission && $request->user()?->hasPermission($permission), 403);
        $format = $request->validate(['format' => ['required', Rule::in(['pdf', 'xlsx'])]])['format'];
        $report = $reports->build($kind, $queries->filters($request, $kind), $request->user());
        $bytes = $format === 'pdf' ? $exporter->pdf($report) : $exporter->xlsx($report);
        $audit->record('organization', $kind.'_exported', ['after' => ['format' => $format, 'export_count' => $report['count']], 'summary' => $report['title'].' exported']);

        return response($bytes, 200, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="foundation-'.$kind.'-'.$report['generated_at']->format('Y-m-d-His').'.'.$format.'"',
            'Cache-Control' => 'private, no-store, max-age=0', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
