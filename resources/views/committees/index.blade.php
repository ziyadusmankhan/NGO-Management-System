@extends('layouts.app')

@section('title', 'Committees')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/committees-index-1.css') }}">
@endpush


@section('content')

@include('shared.action-feedback')

<!-- =========================================
     PAGE HEADER
========================================== -->

<div class="committee-header">

    <div>
        <h1>Committees</h1>

        <p>
            Manage committees, members, positions and organizational responsibilities.
        </p>
    </div>

    <div class="committee-actions">

        <button class="committee-btn secondary">
            <i class="bi bi-download"></i>
            Export
        </button>

        @if(auth()->user()?->hasPermission('committees.create'))<a
            href="{{ route('committees.create') }}"
            class="committee-btn primary"
        >
            <i class="bi bi-plus-lg"></i>
            Add Committee
        </a>@endif

    </div>

</div>


<!-- =========================================
     SUMMARY
========================================== -->

<div class="committee-summary">

    <div class="summary-card">

        <div class="summary-label">
            Total Committees
        </div>

        <div class="summary-value">
            {{ number_format($totalCommittees) }}
        </div>

        <div class="summary-meta">
            Configured organizational groups
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Active Committees
        </div>

        <div class="summary-value">
            {{ number_format($activeCommittees) }}
        </div>

        <div class="summary-meta">
            Currently operating
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Total Positions
        </div>

        <div class="summary-value">
            &mdash;
        </div>

        <div class="summary-meta">
            Positions schema not yet configured
        </div>

    </div>


    <div class="summary-card">

        <div class="summary-label">
            Assigned Members
        </div>

        <div class="summary-value">
            {{ number_format($committees->sum('members_count')) }}
        </div>

        <div class="summary-meta">
            Across committees shown below
        </div>

    </div>

</div>


<!-- =========================================
     SEARCH / FILTERS
========================================== -->

<div class="committee-toolbar">

    <div class="search-box">

        <i class="bi bi-search"></i>

        <input
            type="text"
            id="committeeSearch"
            placeholder="Search committees, responsibilities..."
        >

    </div>


    <select class="filter-select" id="committeeStatus">

        <option value="">All Statuses</option>

        <option value="active">
            Active
        </option>

        <option value="inactive">
            Inactive
        </option>

    </select>


    <select class="filter-select">

        <option value="">
            All Types
        </option>

        <option>
            Decision-Making
        </option>

        <option>
            Implementation
        </option>

        <option>
            Administration
        </option>

    </select>

</div>


<!-- =========================================
     COMMITTEE CARDS
========================================== -->

<div class="committee-grid" id="committeeGrid">

    @forelse ($committees as $committee)

        <article
            class="committee-card"
            data-status="{{ $committee->status }}"
        >

            <div class="committee-card-top">

                <div class="committee-identity">

                    <div class="committee-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>

                    <div>

                        <div class="committee-name">
                            {{ $committee->name }}
                        </div>

                        <div class="committee-code">
                            {{ $committee->committee_number }}
                            &middot;
                            {{ ucfirst($committee->type) }}
                        </div>

                    </div>

                </div>

                <span
                    class="committee-status"
                    style="
                        @if ($committee->status !== 'active')
                            background:var(--legacy-surface-f1f3f2, #f1f3f2);
                            color:var(--legacy-text-6a766f, #6a766f);
                        @endif
                    "
                >
                    {{ ucfirst($committee->status) }}
                </span>

            </div>


            <div class="committee-description">
                {{ $committee->description ?: 'No description has been recorded for this committee.' }}
            </div>


            <div class="committee-divider"></div>


            <div class="committee-meta">

                <div class="meta-item">
                    <label>Type</label>
                    <strong>{{ ucfirst($committee->type) }}</strong>
                </div>

                <div class="meta-item">
                    <label>Members</label>
                    <strong>{{ $committee->members_count }}</strong>
                </div>

                <div class="meta-item">
                    <label>Established</label>
                    <strong>
                        {{
                            $committee->established_at
                                ? $committee->established_at->format('d M Y')
                                : '—'
                        }}
                    </strong>
                </div>

            </div>


            <div class="committee-card-footer">

                <div class="member-stack">
                    <span class="member-count">
                        {{ $committee->members_count }}
                        member(s)
                    </span>
                </div>

                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    @include('shared.archive-committee', ['buttonClass' => 'committee-btn secondary'])
                @if(auth()->user()?->hasPermission('committees.view'))<a
                    href="{{ route('committees.show', $committee) }}"
                    class="view-committee"
                >
                    View committee
                    <i class="bi bi-arrow-right"></i>
                </a>@endif
                </div>

            </div>

        </article>

    @empty

        <div
            style="
                grid-column:1/-1;
                padding:50px;
                text-align:center;
                background:var(--legacy-surface-fff, #fff);
                border:1px solid var(--border);
                border-radius:14px;
                color:var(--legacy-text-89948f, #89948f);
            "
        >

            <i
                class="bi bi-diagram-3"
                style="font-size:28px;"
            ></i>

            <div style="margin-top:10px;">
                No committees have been created yet.
            </div>

        </div>

    @endforelse

</div>


<div style="
    margin-top:18px;
    display:flex;
    justify-content:flex-end;
">
    {{ $committees->links() }}
</div>


<!-- =========================================
     CONFIGURATION BANNER
========================================== -->

<div class="config-banner">

    <i class="bi bi-sliders"></i>

    <div>

        <strong>
            Configurable organizational structure
        </strong>

        <p>
            Committees, positions and responsibilities are designed to be
            configurable so the organization can change its structure
            without rebuilding the application.
        </p>

    </div>

</div>

@endsection


@push('scripts')

<script>

    const committeeSearch =
        document.getElementById('committeeSearch');

    const committeeStatus =
        document.getElementById('committeeStatus');

    const committeeCards =
        document.querySelectorAll('.committee-card');


    function filterCommittees() {

        const search =
            committeeSearch.value.toLowerCase().trim();

        const status =
            committeeStatus.value;


        committeeCards.forEach(card => {

            const text =
                card.textContent.toLowerCase();

            const cardStatus =
                card.dataset.status;

            const matchesSearch =
                !search || text.includes(search);

            const matchesStatus =
                !status || cardStatus === status;

            card.style.display =
                matchesSearch && matchesStatus
                    ? ''
                    : 'none';

        });

    }


    committeeSearch.addEventListener(
        'input',
        filterCommittees
    );

    committeeStatus.addEventListener(
        'change',
        filterCommittees
    );

</script>

@endpush