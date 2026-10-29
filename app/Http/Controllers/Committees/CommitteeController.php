<?php

namespace App\Http\Controllers\Committees;

use App\Domain\Audit\Services\AuditedMemberships;
use App\Domain\Committees\Models\Committee;
use App\Domain\Members\Models\Member;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommitteeController extends Controller
{
    public function index(): View
    {
        $directory = app(\App\Domain\Organization\Services\DirectoryQuery::class);
        $committees = $directory->query('committees', $directory->filters(request(), 'committees'))->paginate(15)->withQueryString();
        $committeeTypes = Committee::query()->distinct()->orderBy('type')->pluck('type');

        $totalCommittees = Committee::count();

        $activeCommittees = Committee::where(
            'status',
            'active'
        )->count();

        return view(
            'committees.index',
            compact(
                'committees',
                'totalCommittees',
                'activeCommittees',
                'committeeTypes'
            )
        );
    }

    public function create(): View
    {
        return view('committees.create');
    }

    public function store(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'type' => [
                'required',
                'string',
                'max:50',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],

            'established_at' => [
                'nullable',
                'date',
            ],
        ]);

        $committee = DB::transaction(function () use ($validated) {

            $nextId =
                (Committee::withTrashed()->max('id') ?? 0) + 1;

            $committeeNumber =
                'COM-'.
                str_pad(
                    $nextId,
                    5,
                    '0',
                    STR_PAD_LEFT
                );

            return Committee::create([
                'committee_number' => $committeeNumber,

                'name' => $validated['name'],

                'description' => $validated['description'] ?? null,

                'type' => $validated['type'],

                'status' => $validated['status'],

                'established_at' => $validated['established_at'] ?? null,
            ]);
        });

        return redirect()
            ->route(
                'committees.show',
                ['committee' => $committee->id]
            )
            ->with(
                'success',
                "Committee {$committee->committee_number} was created successfully."
            );
    }

    public function show(
        Committee $committee
    ): View {

        $committee->load([
            'members' => function ($query) {
                $query
                    ->wherePivot(
                        'status',
                        'active'
                    )
                    ->orderBy('first_name');
            },
        ]);

        $availableMembers = Member::query()
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        return view(
            'committees.show',
            compact(
                'committee',
                'availableMembers'
            )
        );
    }

    public function edit(
        Committee $committee
    ): View {

        return view(
            'committees.edit',
            compact('committee')
        );
    }

    public function update(
        Request $request,
        Committee $committee
    ): RedirectResponse {

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'type' => [
                'required',
                'string',
                'max:50',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],

            'established_at' => [
                'nullable',
                'date',
            ],
        ]);

        $committee->update($validated);

        return redirect()
            ->route(
                'committees.show',
                $committee
            )
            ->with(
                'success',
                'Committee updated successfully.'
            );
    }

    public function destroy(
        Committee $committee
    ): RedirectResponse {

        $committee->delete();

        return redirect()
            ->route('committees.index')
            ->with(
                'success',
                'Committee archived successfully.'
            );
    }

    public function assignMember(
        Request $request,
        Committee $committee
    ): RedirectResponse {

        $validated = $request->validate([
            'member_id' => [
                'required',
                'exists:members,id',
                function ($attribute, $value, $fail) {
                    $member = Member::find($value);

                    if (! $member || $member->status !== 'active') {
                        $fail(
                            'Only active members can be assigned to a committee.'
                        );
                    }
                },
            ],

            'position' => [
                'required',
                'string',
                'max:100',
            ],

            'joined_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        $existingMembership =
            AuditedMemberships::query()
                ->where(
                    'committee_id',
                    $committee->id
                )
                ->where(
                    'member_id',
                    $validated['member_id']
                )
                ->where(
                    'status',
                    'active'
                )
                ->exists();

        if ($existingMembership) {

            return back()
                ->withErrors([
                    'member_id' => 'This member is already active in this committee.',
                ])
                ->withInput();

        }

        AuditedMemberships::query()
            ->insert([
                'committee_id' => $committee->id,

                'member_id' => $validated['member_id'],

                'position' => $validated['position'],

                'joined_at' => $validated['joined_at']
                        ?? now()->toDateString(),

                'status' => 'active',

                'notes' => $validated['notes']
                        ?? null,

                'created_at' => now(),

                'updated_at' => now(),
            ]);

        return back()
            ->with(
                'success',
                'Member assigned to committee successfully.'
            );
    }

    public function updateMember(
        Request $request,
        Committee $committee,
        Member $member
    ): RedirectResponse {
        $validated = $request->validate([
            'position' => [
                'required',
                'string',
                'max:100',
            ],

            'joined_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ]);

        AuditedMemberships::query()
            ->where('committee_id', $committee->id)
            ->where('member_id', $member->id)
            ->where('status', 'active')
            ->update([
                'position' => $validated['position'],

                'joined_at' => $validated['joined_at']
                    ?? now()->toDateString(),

                'notes' => $validated['notes']
                    ?? null,

                'updated_at' => now(),
            ]);

        return back()->with(
            'success',
            'Committee membership updated successfully.'
        );
    }

    public function removeMember(
        Committee $committee,
        Member $member
    ): RedirectResponse {

        $updated = AuditedMemberships::query()
            ->where(
                'committee_id',
                $committee->id
            )
            ->where(
                'member_id',
                $member->id
            )
            ->where(
                'status',
                'active'
            )
            ->update([
                'status' => 'ended',

                'ended_at' => now()->toDateString(),

                'updated_at' => now(),
            ]);

        if (! $updated) {
            return back()->with(
                'error',
                'No active committee membership was found.'
            );
        }

        return back()->with(
            'success',
            'Member assignment ended successfully.'
        );
    }
}
