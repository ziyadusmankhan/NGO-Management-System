<?php

namespace App\Domain\Documents\Services;

use App\Domain\Business\Models\BusinessApplication;
use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Documents\Models\LibraryDocument;
use App\Domain\Procurement\Models\Procurement;
use App\Domain\Projects\Models\Project;
use App\Domain\Projects\Models\ProjectDocument;
use App\Domain\Qard\Models\QardLoan;
use App\Domain\Users\Models\User;
use App\Domain\Welfare\Applications\Models\WelfareApplication;
use Illuminate\Support\Facades\DB;

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
        return $this->visible($u)->whereKey($d->id)->exists();
    }

    private function procurementScope($query, array $permissions): void
    {
        $query->where(function ($q) use ($permissions) {
            $q->where('p.parent_type', 'office');
            if (isset($permissions['*']) || isset($permissions['projects.view'])) {
                $q->orWhere('p.parent_type', 'project');
            }
            if (isset($permissions['*']) || isset($permissions['business.view'])) {
                $q->orWhere('p.parent_type', 'business');
            }
        });
    }

    public function visible(User $u)
    {
        $query = LibraryDocument::query();
        if (! $u->isActive() || $u->trashed()) {
            return $query->whereRaw('1 = 0');
        }
        $roles = $u->roles()->with('permissions')->get();
        $administrator = $roles->contains('slug', 'administrator');
        $permissions = array_fill_keys($roles->flatMap(fn ($role) => $role->permissions->pluck('slug'))->all(), true);
        if ($administrator) {
            $permissions['*'] = true;
        }

        // No document hydration or unbounded ID lists: filters, counts and limits
        // remain in SQL, with the same policy used by individual downloads.
        return $query->where(function ($visible) use ($u, $permissions, $administrator) {
            $visible->where(function ($standalone) use ($u, $administrator) {
                $standalone->whereNull('parent_type');
                if (! $administrator) {
                    $standalone->where(fn ($access) => $access->where('owner_id', $u->id)
                        ->orWhereHas('roles', fn ($roles) => $roles->where('roles.status', 'active')
                            ->whereIn('roles.id', $u->roles()->select('roles.id'))));
                }
            });
            foreach (['welfare_agreement:' => 'welfare.agreements.view', 'verification:' => 'welfare.view'] as $prefix => $permission) {
                if (isset($permissions['*']) || isset($permissions[$permission])) {
                    $visible->orWhere(fn ($special) => $special->whereNotNull('parent_type')
                        ->whereRaw("source_key LIKE ? ESCAPE '!'", [str_replace('_', '!_', $prefix).'%']));
                }
            }
            foreach (self::PARENTS as $type => [$class, $permission]) {
                if (! isset($permissions['*']) && ! isset($permissions[$permission])) {
                    continue;
                }
                $visible->orWhere(function ($linked) use ($permissions, $type, $class) {
                    $linked->where('parent_type', $type)
                        ->where(fn ($key) => $key->whereNull('source_key')->orWhere(fn ($key) => $key
                            ->whereRaw("source_key NOT LIKE ? ESCAPE '!'", ['welfare!_agreement:%'])->where('source_key', 'not like', 'verification:%')))
                        ->whereExists(function ($parent) use ($permissions, $type, $class) {
                            $parent->selectRaw('1')->from((new $class)->getTable().' as p')->whereColumn('p.id', 'library_documents.parent_id');
                            if ($type === 'procurement') {
                                $this->procurementScope($parent, $permissions);
                            }
                        });
                    if (in_array($type, ['projects', 'business'])) {
                        $sourceClass = $type === 'projects' ? ProjectDocument::class : BusinessDocument::class;
                        $concat = DB::connection()->getDriverName() === 'mysql'
                            ? 'concat(?, s.id)' : '(? || s.id)';
                        $linked->where(function ($evidence) use ($sourceClass, $type, $concat, $permissions) {
                            $evidence->where('authoritative', false)->orWhereExists(function ($source) use ($sourceClass, $type, $concat, $permissions) {
                                $source->selectRaw('1')->from((new $sourceClass)->getTable().' as s')
                                    ->whereRaw('library_documents.source_key = '.$concat, [$type.':'])
                                    ->where(function ($movement) use ($type, $permissions) {
                                        $movement->whereNull('s.movement_id')->orWhereNotExists(function ($link) use ($type) {
                                            $link->selectRaw('1')->from('procurement_movements as pm')->where('pm.source_type', $type === 'projects' ? 'project' : 'business')->whereColumn('pm.source_id', 's.movement_id');
                                        });
                                        if (isset($permissions['*']) || isset($permissions['procurement.view'])) {
                                            $movement->orWhereExists(function ($link) use ($type, $permissions) {
                                                $link->selectRaw('1')->from('procurement_movements as pm')->join('procurements as p', 'p.id', '=', 'pm.procurement_id')
                                                    ->where('pm.source_type', $type === 'projects' ? 'project' : 'business')->whereColumn('pm.source_id', 's.movement_id');
                                                $this->procurementScope($link, $permissions);
                                            });
                                        }
                                    });
                            });
                        });
                    }
                });
            }
        });
    }
}
