<?php

namespace App\Domain\Organization\Services;

use App\Domain\Committees\Models\Committee;
use App\Domain\Members\Models\Member;
use App\Domain\Security\Services\FieldEncryption;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DirectoryQuery
{
    public function filters(Request $request, string $kind): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in($kind === 'members' ? ['active', 'inactive', 'suspended', 'resigned'] : ['active', 'inactive'])],
            'type' => ['nullable', 'string', 'max:50'],
            'role' => $kind === 'roles' ? ['nullable', 'integer', 'exists:roles,id'] : ['nullable', 'string', 'max:100'],
        ]);
    }

    public function query(string $kind, array $filters): Builder
    {
        $search = trim($filters['search'] ?? '');
        $query = match ($kind) {
            'members' => Member::with(['committees' => fn ($q) => $q->wherePivot('status', 'active')->orderBy('name')]),
            'committees' => Committee::withCount('members'),
            'users' => User::with(['roles', 'member.committees' => fn ($q) => $q->wherePivot('status', 'active')]),
            'roles' => Role::with(['permissions' => fn ($q) => $q->where('slug', '!=', 'documents.delete')->orderBy('module')->orderBy('name')])->withCount('users')->where('status', 'active'),
            default => abort(404),
        };
        if ($search !== '') {
            $query->where(function (Builder $q) use ($kind, $search) {
                if ($kind === 'members') {
                    $q->where('member_number', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('phone_lookup', app(FieldEncryption::class)->lookup('phone', $search) ?? 'no-match')
                        ->orWhere('cnic_lookup', app(FieldEncryption::class)->lookup('cnic', $search) ?? 'no-match');
                    $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);
                    if (count($tokens) > 1) {
                        $q->orWhere(function (Builder $names) use ($tokens) {
                            foreach ($tokens as $token) {
                                $names->where(fn ($part) => $part->where('first_name', 'like', '%'.$token.'%')->orWhere('last_name', 'like', '%'.$token.'%'));
                            }
                        });
                    }
                } else {
                    $q->where('name', 'like', '%'.$search.'%');
                    if ($kind === 'users') {
                        $q->orWhere('email', 'like', '%'.$search.'%')->orWhereHas('roles', fn ($r) => $r->where('name', 'like', '%'.$search.'%'));
                    }
                    if ($kind === 'committees') {
                        $q->orWhere('committee_number', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%');
                    }
                    if ($kind === 'roles') {
                        $q->orWhere('slug', 'like', '%'.$search.'%');
                    }
                }
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if ($kind === 'committees' && ! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if ($kind === 'users' && ! empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $filters['role']));
        }
        if ($kind === 'roles' && ! empty($filters['role'])) {
            $query->whereKey((int) $filters['role']);
        }

        return $kind === 'roles' ? $query->orderBy('name')->orderBy('id') : $query->orderByDesc('id');
    }
}
