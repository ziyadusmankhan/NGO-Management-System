<?php

namespace App\Domain\Organization\Services;

use App\Domain\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DirectoryReport
{
    public const LIMIT = 2500;

    public function build(string $kind, array $filters, User $actor): array
    {
        $query = app(DirectoryQuery::class)->query($kind, $filters);
        if ((clone $query)->count() > self::LIMIT) {
            throw ValidationException::withMessages(['export' => 'Choose narrower filters. An export may contain up to '.number_format(self::LIMIT).' records.']);
        }
        if ($kind === 'committees') {
            $query->with(['members' => fn ($q) => $q->orderBy('first_name')->orderBy('id')]);
        }
        $records = $query->get();
        $title = ['members' => 'Members Register', 'committees' => 'Committees Register', 'users' => 'Users Register', 'roles' => 'Roles and Permissions'][$kind];
        $sections = match ($kind) {
            'members' => [$this->members($records)],
            'committees' => $this->committees($records),
            'users' => [$this->users($records)],
            'roles' => $this->roles($records),
        };
        if (array_sum(array_map(fn ($s) => count($s['rows']), $sections)) > 10000) {
            throw ValidationException::withMessages(['export' => 'Choose narrower filters. This export includes more than 10,000 detail rows.']);
        }
        $scope = [];
        if (filled($filters['search'] ?? null)) {
            $scope[] = 'Search applied';
        }
        if (filled($filters['status'] ?? null)) {
            $scope[] = 'Status: '.str($filters['status'])->headline();
        }
        if ($kind === 'committees' && filled($filters['type'] ?? null)) {
            $scope[] = 'Type: '.str($filters['type'])->headline();
        }
        if (filled($filters['role'] ?? null)) {
            $scope[] = 'Selected role';
        }

        return ['kind' => $kind, 'title' => $title, 'generated_at' => now('Asia/Karachi'), 'prepared_by' => $actor->name,
            'scope' => $scope ? implode(' / ', $scope) : 'All current records', 'count' => $records->count(),
            'active' => $records->where('status', 'active')->count(), 'inactive' => $records->where('status', 'inactive')->count(),
            'sections' => $sections];
    }

    private function section(string $title, array $headers, array $types, array $widths, array $rows): array
    {
        return compact('title', 'headers', 'types', 'widths', 'rows');
    }

    private function members(Collection $records): array
    {
        return $this->section('Members', ['Member ID', 'Full name', 'Phone', 'Email', 'Address', 'Committee / position', 'Status', 'Joined'],
            ['text', 'text', 'text', 'text', 'text', 'text', 'text', 'date'], [17, 27, 18, 31, 32, 41, 14, 17],
            $records->map(fn ($m) => [$m->member_number, $m->full_name, $m->phone, $m->email, $m->address,
                $m->committees->map(fn ($c) => $c->name.($c->pivot->position ? ' - '.$c->pivot->position : ''))->implode("\n"),
                str($m->status)->headline()->toString(), $m->joining_date])->all());
    }

    private function committees(Collection $records): array
    {
        $register = $this->section('Committees', ['Committee ID', 'Name', 'Type', 'Status', 'Assigned members', 'Active assignments', 'Established', 'Description'],
            ['text', 'text', 'text', 'text', 'number', 'number', 'date', 'text'], [18, 35, 21, 14, 18, 20, 18, 58],
            $records->map(fn ($c) => [$c->committee_number, $c->name, str($c->type)->headline()->toString(), ucfirst($c->status),
                $c->members_count, $c->members->where('pivot.status', 'active')->count(), $c->established_at, $c->description])->all());
        $roster = $this->section('Committee Members', ['Committee ID', 'Committee', 'Member ID', 'Full name', 'Position', 'Assignment status', 'Joined', 'Ended'],
            ['text', 'text', 'text', 'text', 'text', 'text', 'date', 'date'], [18, 38, 18, 29, 23, 22, 18, 18],
            $records->flatMap(fn ($c) => $c->members->map(fn ($m) => [$c->committee_number, $c->name, $m->member_number, $m->full_name,
                $m->pivot->position, ucfirst($m->pivot->status ?? ''), $m->pivot->joined_at ? CarbonImmutable::parse($m->pivot->joined_at) : null,
                $m->pivot->ended_at ? CarbonImmutable::parse($m->pivot->ended_at) : null]))->all());

        return [$register, $roster];
    }

    private function users(Collection $records): array
    {
        return $this->section('Users', ['User ID', 'Full name', 'Email', 'Roles', 'Committees', 'Status', 'Last active (PKT)', 'Joined'],
            ['text', 'text', 'text', 'text', 'text', 'text', 'datetime', 'date'], [17, 29, 37, 48, 35, 14, 27, 17],
            $records->map(fn ($u) => [sprintf('USR-%05d', $u->id), $u->name, $u->email, $u->roles->pluck('name')->implode("\n"),
                $u->member?->committees->pluck('name')->implode("\n") ?? '', ucfirst($u->status), $u->last_active_at?->timezone('Asia/Karachi'),
                $u->created_at?->timezone('Asia/Karachi')])->all());
    }

    private function roles(Collection $records): array
    {
        $summary = $this->section('Roles', ['Role ID', 'Role', 'Key', 'Status', 'Users', 'Assigned permissions', 'Access', 'Description'],
            ['text', 'text', 'text', 'text', 'number', 'number', 'text', 'text'], [26, 38, 33, 14, 14, 24, 25, 60],
            $records->map(fn ($r) => [sprintf('ROL-%05d', $r->id), $r->name, $r->slug, ucfirst($r->status), $r->users_count, $r->permissions->count(),
                $r->slug === 'administrator' ? 'Full system access' : 'Assigned permissions', $r->description])->all());
        $permissions = $this->section('Assigned Permissions', ['Role', 'Module', 'Permission', 'Permission key'],
            ['text', 'text', 'text', 'text'], [43, 28, 62, 52],
            $records->flatMap(fn ($r) => $r->permissions->map(fn ($p) => [$r->name, str($p->module)->headline()->toString(), $p->name, $p->slug]))->all());

        return [$summary, $permissions];
    }
}
