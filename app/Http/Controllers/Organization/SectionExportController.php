<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Organization\Services\DirectoryExport;
use App\Domain\Organization\Services\SectionRegister;
use Illuminate\Http\Request;

class SectionExportController
{
    public function __invoke(Request $request, string $kind, SectionRegister $register, DirectoryExport $renderer, AuditRecorder $audit)
    {
        $format = $request->validate(['format' => 'required|in:pdf,xlsx'])['format'];
        $report = $register->build($kind, $request);
        $body = $renderer->{$format}($report);
        $audit->record($kind === 'audit' ? 'audit' : 'reports', 'register_exported', ['after' => ['format' => $format, 'export_count' => $report['count'], 'snapshot_id' => $report['snapshot_id']]]);

        return response($body, 200, ['Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="foundation-'.$kind.'.'.$format.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
