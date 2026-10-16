@extends('layouts.app')

@section('title', 'Edit Committee')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:24px;
    }

    .page-header h1 {
        margin:0;
        font-size:25px;
        font-weight:700;
    }

    .page-header p {
        margin:7px 0 0;
        color:var(--muted);
        font-size:11px;
    }

    .actions {
        display:flex;
        gap:8px;
    }

    .btn {
        height:40px;
        padding:0 14px;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#637069;
        font-size:10px;
        font-weight:650;
        text-decoration:none;
        cursor:pointer;
    }

    .btn.primary {
        color:#fff;
        background:var(--primary);
        border-color:var(--primary);
    }

    .layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 300px;
        gap:18px;
    }

    .card {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        overflow:hidden;
        margin-bottom:18px;
    }

    .card-head {
        padding:17px 20px;
        border-bottom:1px solid #eef1ef;
    }

    .card-head strong {
        font-size:12px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color:#929d97;
        font-size:9px;
    }

    .card-body {
        padding:20px;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .full {
        grid-column:1/-1;
    }

    .label {
        display:block;
        margin-bottom:7px;
        color:#36433d;
        font-size:10px;
        font-weight:650;
    }

    .control,
    .select {
        width:100%;
        height:42px;
        border:1px solid var(--border);
        border-radius:9px;
        padding:0 12px;
        background:#fff;
        color:var(--text);
        font-size:10px;
        outline:none;
    }

    textarea.control {
        height:auto;
        min-height:110px;
        padding:11px 12px;
        resize:vertical;
    }

    .control:focus,
    .select:focus {
        border-color:#6b7771;
        box-shadow:0 0 0 3px rgba(23,63,53,.05);
    }

    .error {
        margin-top:5px;
        color:#a85f58;
        font-size:8px;
    }

    .status-options {
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }

    .status-options input {
        display:none;
    }

    .status-options label {
        padding:7px 10px;
        border:1px solid var(--border);
        border-radius:20px;
        color:#68756f;
        font-size:9px;
        cursor:pointer;
    }

    .status-options input:checked + label {
        background:var(--primary-soft);
        border-color:#c7d8d0;
        color:var(--primary);
    }

    .side {
        background:#fff;
        border:1px solid var(--border);
        border-radius:14px;
        padding:18px;
        margin-bottom:18px;
    }

    .side h3 {
        margin:0 0 15px;
        font-size:12px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size:9px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color:#98a29d;
    }

    .side-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    .notice {
        display:flex;
        gap:9px;
        padding:12px;
        border-radius:10px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
    }

    .notice i {
        color:var(--primary);
        font-size:14px;
    }

    .notice p {
        margin:0;
        color:#748079;
        font-size:8px;
        line-height:1.6;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:650px) {
        .page-header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .btn {
            flex:1;
        }

        .grid {
            grid-template-columns:1fr;
        }

        .full {
            grid-column:auto;
        }
    }
</style>
@endpush


@section('content')

<div class="page-header">

    <div>

        <h1>Edit Committee</h1>

        <p>
            Update {{ $committee->committee_number }} — {{ $committee->name }}
        </p>

    </div>

    <div class="actions">

        <a
            href="{{ route('committees.show', $committee) }}"
            class="btn"
        >
            <i class="bi bi-arrow-left"></i>
            Cancel
        </a>

        <button
            type="submit"
            form="editCommitteeForm"
            class="btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Changes
        </button>

    </div>

</div>


<form
    id="editCommitteeForm"
    method="POST"
    action="{{ route('committees.update', $committee) }}"
>

    @csrf
    @method('PUT')


    @if ($errors->any())

        <div class="card">

            <div class="card-body">

                <strong style="
                    color:#a85f58;
                    font-size:10px;
                ">
                    Please correct the following:
                </strong>

                <ul style="
                    margin:8px 0 0;
                    padding-left:18px;
                    color:#a85f58;
                    font-size:8px;
                ">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        </div>

    @endif


    <div class="layout">

        <div>

            <div class="card">

                <div class="card-head">

                    <strong>Basic Information</strong>

                    <span>
                        Update the committee's core organizational information.
                    </span>

                </div>

                <div class="card-body">

                    <div class="grid">

                        <div>

                            <label class="label">
                                Committee Number
                            </label>

                            <input
                                class="control"
                                value="{{ $committee->committee_number }}"
                                readonly
                            >

                        </div>


                        <div>

                            <label class="label">
                                Committee Name *
                            </label>

                            <input
                                class="control"
                                name="name"
                                value="{{ old('name', $committee->name) }}"
                                required
                            >

                            @error('name')
                                <div class="error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="full">

                            <label class="label">
                                Description
                            </label>

                            <textarea
                                class="control"
                                name="description"
                            >{{ old('description', $committee->description) }}</textarea>

                        </div>


                        <div>

                            <label class="label">
                                Committee Type
                            </label>

                            <select
                                class="select"
                                name="type"
                            >

                                <option
                                    value="decision"
                                    @selected(
                                        old(
                                            'type',
                                            $committee->type
                                        ) === 'decision'
                                    )
                                >
                                    Decision-Making
                                </option>

                                <option
                                    value="implementation"
                                    @selected(
                                        old(
                                            'type',
                                            $committee->type
                                        ) === 'implementation'
                                    )
                                >
                                    Implementation
                                </option>

                                <option
                                    value="administration"
                                    @selected(
                                        old(
                                            'type',
                                            $committee->type
                                        ) === 'administration'
                                    )
                                >
                                    Administration
                                </option>

                            </select>

                        </div>


                        <div>

                            <label class="label">
                                Established Date
                            </label>

                            <input
                                class="control"
                                type="date"
                                name="established_at"
                                value="{{
                                    old(
                                        'established_at',
                                        optional(
                                            $committee->established_at
                                        )->format('Y-m-d')
                                    )
                                }}"
                            >

                        </div>


                        <div class="full">

                            <label class="label">
                                Status
                            </label>

                            <div class="status-options">

                                <div>

                                    <input
                                        type="radio"
                                        id="statusActive"
                                        name="status"
                                        value="active"
                                        @checked(
                                            old(
                                                'status',
                                                $committee->status
                                            ) === 'active'
                                        )
                                    >

                                    <label for="statusActive">
                                        Active
                                    </label>

                                </div>


                                <div>

                                    <input
                                        type="radio"
                                        id="statusInactive"
                                        name="status"
                                        value="inactive"
                                        @checked(
                                            old(
                                                'status',
                                                $committee->status
                                            ) === 'inactive'
                                        )
                                    >

                                    <label for="statusInactive">
                                        Inactive
                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="card">

                <div class="card-head">

                    <strong>Committee Information</strong>

                    <span>
                        Additional configuration will be connected as those
                        organizational subsystems are implemented.
                    </span>

                </div>

                <div class="card-body">

                    <div class="notice">

                        <i class="bi bi-info-circle"></i>

                        <p>
                            Responsibilities, reporting chains and coordinator
                            configuration are not stored on the current
                            committees table. We'll add their dedicated
                            backend structures separately rather than storing
                            them incorrectly.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        <aside>

            <div class="side">

                <h3>
                    Committee Summary
                </h3>

                <div style="
                    text-align:center;
                    font-size:28px;
                    font-weight:700;
                    color:var(--primary);
                ">
                    {{ $committee->committee_number }}
                </div>

                <div style="
                    margin-top:4px;
                    text-align:center;
                    color:#929d97;
                    font-size:8px;
                ">
                    Committee Reference
                </div>

                <div style="
                    height:1px;
                    background:#eef1ef;
                    margin:16px 0;
                "></div>


                <div class="side-row">

                    <span>
                        Members
                    </span>

                    <span>
                        {{ $committee->members()->count() }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Status
                    </span>

                    <span>
                        {{ ucfirst($committee->status) }}
                    </span>

                </div>


                <div class="side-row">

                    <span>
                        Type
                    </span>

                    <span>
                        {{ ucfirst($committee->type) }}
                    </span>

                </div>

            </div>


            <div class="side">

                <div class="notice">

                    <i class="bi bi-shield-check"></i>

                    <p>
                        Changes to committee identity and status are recorded
                        through the normal application workflow.
                    </p>

                </div>

            </div>

        </aside>

    </div>

</form>

@endsection