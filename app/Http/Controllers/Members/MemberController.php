<?php

namespace App\Http\Controllers\Members;

use App\Domain\Audit\Services\AuditedMemberships;
use App\Domain\Committees\Models\Committee;
use App\Domain\Members\Models\Member;
use App\Domain\Security\Services\FieldEncryption;
use App\Http\Controllers\Controller;
use App\Http\Requests\Members\StoreMemberRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        $query = Member::query()
            ->with([
                'committees' => function ($query) {
                    $query->wherePivot('status', 'active');
                },
            ]);

        if (request()->filled('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where('member_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_lookup', app(FieldEncryption::class)->lookup('phone', $search) ?? 'no-match')
                    ->orWhere('cnic_lookup', app(FieldEncryption::class)->lookup('cnic', $search) ?? 'no-match');
            });
        }

        if (request()->filled('status')) {
            $query->where(
                'status',
                request('status')
            );
        }

        $members = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalMembers = Member::count();

        $activeMembers = Member::where(
            'status',
            'active'
        )->count();

        $suspendedMembers = Member::where(
            'status',
            'suspended'
        )->count();

        $totalCommittees = Committee::where(
            'status',
            'active'
        )->count();

        return view(
            'members.index',
            compact(
                'members',
                'totalMembers',
                'activeMembers',
                'suspendedMembers',
                'totalCommittees'
            )
        );
    }

    public function create(): View
    {
        $committees = Committee::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view(
            'members.create',
            compact('committees')
        );
    }

    public function store(
        StoreMemberRequest $request
    ): RedirectResponse {

        $validated = $request->validated();

        $nameParts = preg_split(
            '/\s+/',
            trim($validated['full_name']),
            2
        );

        $firstName = $nameParts[0];

        $lastName = $nameParts[1] ?? null;

        $member = DB::transaction(function () use (
            $validated,
            $firstName,
            $lastName
        ) {

            $nextId =
                (Member::withTrashed()->max('id') ?? 0) + 1;

            $memberNumber =
                'MEM-'.
                str_pad(
                    $nextId,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            $member = Member::create([
                'member_number' => $memberNumber,

                'first_name' => $firstName,

                'last_name' => $lastName,

                'father_name' => $validated['father_name'] ?? null,

                'cnic' => $validated['cnic'] ?? null,

                'phone' => $validated['phone'],

                'email' => $validated['email'] ?? null,

                'address' => $validated['address'] ?? null,

                'joining_date' => $validated['joining_date'] ?? null,

                'status' => $validated['status'],

                'notes' => $validated['notes'] ?? null,
            ]);

            /*
            * Optional committee assignment, made from the
            * same "add member" form instead of requiring a
            * separate trip to the committee's members screen.
            */
            if (! empty($validated['committee_id'])) {

                $committeeExists = Committee::query()
                    ->where('id', $validated['committee_id'])
                    ->where('status', 'active')
                    ->exists();

                if (! $committeeExists) {
                    throw new \RuntimeException(
                        'The selected committee is not active.'
                    );
                }

                AuditedMemberships::query()->insert([
                    'committee_id' => $validated['committee_id'],

                    'member_id' => $member->id,

                    'position' => $validated['committee_position']
                        ?? 'Member',

                    'joined_at' => $validated['committee_joined_at']
                        ?? now()->toDateString(),

                    'ended_at' => null,

                    'status' => 'active',

                    'notes' => null,

                    'created_at' => now(),

                    'updated_at' => now(),
                ]);
            }

            return $member;
        });

        return redirect()
            ->route(
                'members.show',
                ['member' => $member->id]
            )
            ->with(
                'success',
                "Member {$member->member_number} was created successfully."
            );
    }

    public function show(Member $member): View
    {
        $member->load([
            'committees' => function ($query) {
                $query->wherePivot('status', 'active');
            },
        ]);

        return view(
            'members.show',
            compact('member')
        );
    }

    public function edit(Member $member): View
    {
        return view(
            'members.edit',
            compact('member')
        );
    }

    public function update(
        StoreMemberRequest $request,
        Member $member
    ): RedirectResponse {

        $validated = $request->validated();

        $nameParts = preg_split(
            '/\s+/',
            trim($validated['full_name']),
            2
        );

        $firstName = $nameParts[0];

        $lastName = $nameParts[1] ?? null;

        $member->update([
            'first_name' => $firstName,

            'last_name' => $lastName,

            'father_name' => $validated['father_name'] ?? null,

            'cnic' => $validated['cnic'] ?? null,

            'phone' => $validated['phone'],

            'email' => $validated['email'] ?? null,

            'address' => $validated['address'] ?? null,

            'joining_date' => $validated['joining_date'] ?? null,

            'status' => $validated['status'],

            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route(
                'members.show',
                ['member' => $member->id]
            )
            ->with(
                'success',
                "Member {$member->member_number} was updated successfully."
            );
    }

    public function destroy(
        Member $member
    ): RedirectResponse {

        $memberNumber =
            $member->member_number;

        $member->delete();

        return redirect()
            ->route('members.index')
            ->with(
                'success',
                "Member {$memberNumber} was archived successfully."
            );
    }
}
