@extends('layouts.app')

@section('title', 'Edit Committee')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/committees-edit-1.css') }}">
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

        @if(auth()->user()?->hasPermission('committees.view'))<a
            href="{{ route('committees.show', $committee) }}"
            class="btn"
        >
            <i class="bi bi-arrow-left"></i>
            Cancel
        </a>@endif

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