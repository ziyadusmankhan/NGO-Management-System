<?php

namespace App\Domain\Documents\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Procurement\Models\ProcurementMovement;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectDocument;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;

class DocumentAccess
{
    public const PARENTS = [
        'welfare' => [WelfareApplication::class, 'welfare.assistance.view', 'welfare.assistance.manage'],
        'qard' => [QardLoan::class, 'qard.view', 'qard.manage'],
        'business' => [BusinessApplication::class, 'business.view', 'business.manage'],
        'projects' => [Project::class, 'projects.view', 'projects.manage'],
        'procurement' => [Procurement::class, 'procurement.view', 'procurement.manage'],
    ];

    public function parent(string $type, int $id)
    {
        abort_unless(isset(self::PARENTS[$type]), 422);

        return (new (self::PARENTS[$type][0]))->newQueryWithoutScopes()->findOrFail($id);
    }

    public function parentAllowed(string $type, int $id, User $u, bool $write = false): bool
    {
        $map = self::PARENTS[$type] ?? null;
        if (! $map || ! $u->hasPermission($map[$write ? 2 : 1])) {
            return false;
        }
        $p = (new $map[0])->newQueryWithoutScopes()->find($id);
        if (! $p) {
            return false;
        }
        if ($type === 'procurement' && $p->parent_type !== 'office') {
            return $u->hasPermission($p->parent_type === 'project' ? 'projects.view' : 'business.view');
        }

        return true;
    }

    public function allows(LibraryDocument $d, User $u): bool
    {
        if (! $u->isActive() || $u->trashed()) {
            return false;
        }
        if (! $d->parent_type) {
            return $u->hasRole('administrator') || (int) $d->owner_id === $u->id || $d->roles()->whereIn('roles.id', $u->roles()->where('status', 'active')->select('roles.id'))->exists();
        }
        if (str_starts_with($d->source_key ?? '', 'welfare_agreement:')) {
            return $u->hasPermission('welfare.agreements.view');
        }
        if (str_starts_with($d->source_key ?? '', 'verification:')) {
            return $u->hasPermission('welfare.view');
        }
        if (! $this->parentAllowed($d->parent_type, (int) $d->parent_id, $u)) {
            return false;
        }
        if (in_array($d->parent_type, ['projects', 'business']) && $d->authoritative) {
            $class = $d->parent_type === 'projects' ? ProjectDocument::class : BusinessDocument::class;
            $id = (int) substr($d->source_key, strrpos($d->source_key, ':') + 1);
            $source = $class::find($id);
            if (! $source) {
                return false;
            }
            $link = ProcurementMovement::where('source_type', $d->parent_type === 'projects' ? 'project' : 'business')->where('source_id', $source->movement_id)->first();
            if ($source->movement_id && $link) {
                return $this->parentAllowed('procurement', $link->procurement_id, $u);
            }
        }

        return true;
    }

    public function visible(User $u)
    {
        // Authorization precedes pagination/count/filtering, so restricted metadata never leaks.
        $ids = [];
        LibraryDocument::query()->chunkById(200, function ($rows) use ($u, &$ids) {
            foreach ($rows as $d) {
                if ($this->allows($d, $u)) {
                    $ids[] = $d->id;
                }
            }
        });

        return LibraryDocument::whereIn('id', $ids);
    }
}
