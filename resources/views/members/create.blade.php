@extends('layouts.app')

@section('title', 'Add Member')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/members-create-1.css') }}">
@endpush


@section('content')

<div class="form-page-header">

    <div>

        <h1>Add Member</h1>

        <p>
            Create a new organization member record.
        </p>

    </div>

    <div class="form-page-actions">

        @if(auth()->user()?->hasPermission('members.view'))<a
            href="{{ route('members.index') }}"
            class="form-btn secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Members
        </a>@endif

        <button
            type="submit"
            form="memberForm"
            class="form-btn primary"
        >
            <i class="bi bi-check-lg"></i>
            Save Member
        </button>

    </div>

</div>


@if ($errors->any())

    <div class="form-card">

        <div class="form-card-body">

            <div style="
                color:var(--legacy-text-a85f58, #a85f58);
                font-size:14px;
                font-weight:650;
                margin-bottom:8px;
            ">
                Please correct the following:
            </div>

            <ul style="
                margin:0;
                padding-left:20px;
                color:var(--legacy-text-a85f58, #a85f58);
                font-size:13px;
            ">

                @foreach ($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    </div>

@endif


<form
    id="memberForm"
    method="POST"
    action="{{ route('members.store') }}"
>

    @csrf


    <div class="form-layout">

        <div>

            <!-- Personal Information -->

            <div class="form-card">

                <div class="form-card-header">

                    <div class="form-card-title">
                        Personal Information
                    </div>

                    <div class="form-card-description">
                        Basic identity and contact information for the member.
                    </div>

                </div>

                <div class="form-card-body">

                    <div class="form-grid">

                        <div class="form-group">

                            <label class="form-label-custom">
                                Member ID
                            </label>

                            <input
                                type="text"
                                class="form-control-custom"
                                value="Automatically generated"
                                readonly
                            >

                            <div class="form-help">
                                The system will generate the member number automatically.
                            </div>

                        </div>


                        <div class="form-group">

                            <label class="form-label-custom">
                                Full Name
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                id="memberName"
                                class="form-control-custom @error('full_name') is-invalid @enderror"
                                value="{{ old('full_name') }}"
                                placeholder="Enter full name"
                                required
                            >

                            @error('full_name')
                                <div class="field-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="form-group">

                            <label class="form-label-custom">
                                Father / Husband Name
                            </label>

                            <input
                                type="text"
                                name="father_name"
                                class="form-control-custom"
                                value="{{ old('father_name') }}"
                                placeholder="Enter father or husband name"
                            >

                        </div>


                        <div class="form-group">

                            <label class="form-label-custom">
                                CNIC
                            </label>

                            <input
                                type="text"
                                name="cnic"
                                class="form-control-custom"
                                value="{{ old('cnic') }}"
                                placeholder="XXXXX-XXXXXXX-X"
                            >

                            @error('cnic')
                                <div class="field-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="form-group">

                            <label class="form-label-custom">
                                Phone Number
                                <span class="required">*</span>
                            </label>

                            <input
                                type="tel"
                                name="phone"
                                class="form-control-custom"
                                value="{{ old('phone') }}"
                                placeholder="+92 3XX XXXXXXX"
                                required
                            >

                            @error('phone')
                                <div class="field-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="form-group">

                            <label class="form-label-custom">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control-custom"
                                value="{{ old('email') }}"
                                placeholder="member@example.com"
                            >

                            @error('email')
                                <div class="field-error">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="form-group full">

                            <label class="form-label-custom">
                                Location
                            </label>

                            <input
                                type="text"
                                name="address"
                                class="form-control-custom"
                                value="{{ old('address') }}"
                                placeholder="Village / area / city"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- Organization Role -->

            <div class="form-card">

    <div class="form-card-header">

        <div class="form-card-title">
            Organization Role
        </div>

        <div class="form-card-description">
            Assign the member to an active committee and organizational position.
        </div>

    </div>


        <div class="form-card-body">

                <div class="form-grid">

                    <div class="form-group">

                        <label class="form-label-custom">
                            Committee
                        </label>

                        <select
                            name="committee_id"
                            class="form-select-custom"
                            id="committeeSelect"
                        >

                            <option value="">
                                No committee assignment
                            </option>

                            @foreach ($committees as $committee)

                                <option
                                    value="{{ $committee->id }}"
                                    @selected(
                                        old('committee_id') == $committee->id
                                    )
                                >
                                    {{ $committee->name }}
                                    —
                                    {{ $committee->committee_number }}
                                </option>

                            @endforeach

                        </select>

                        @error('committee_id')

                            <div class="field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    <div class="form-group">

                        <label class="form-label-custom">
                            Position
                        </label>

                        <select
                            name="committee_position"
                            class="form-select-custom"
                            id="committeePosition"
                        >

                            <option value="Member">
                                Member
                            </option>

                            <option value="Chairman">
                                Chairman
                            </option>

                            <option value="President">
                                President
                            </option>

                            <option value="General Secretary">
                                General Secretary
                            </option>

                            <option value="Treasurer">
                                Treasurer
                            </option>

                            <option value="Coordinator">
                                Coordinator
                            </option>

                            <option value="Chief Coordinator">
                                Chief Coordinator
                            </option>

                        </select>

                        @error('committee_position')

                            <div class="field-error">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    <div class="form-group">

                        <label class="form-label-custom">
                            Committee Join Date
                        </label>

                        <input
                            type="date"
                            name="committee_joined_at"
                            class="form-control-custom"
                            value="{{ old(
                                'committee_joined_at',
                                date('Y-m-d')
                            ) }}"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label-custom">
                            Membership Status
                        </label>

                        <div class="status-options">

                            @foreach ([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                                'resigned' => 'Resigned'
                            ] as $value => $label)

                                <div class="status-option">

                                    <input
                                        type="radio"
                                        name="status"
                                        id="status{{ ucfirst($value) }}"
                                        value="{{ $value }}"
                                        @checked(
                                            old('status', 'active') === $value
                                        )
                                    >

                                    <label
                                        for="status{{ ucfirst($value) }}"
                                    >
                                        {{ $label }}
                                    </label>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            </div>

        </div>


            <!-- Notes -->

            <div class="form-card">

                <div class="form-card-header">

                    <div class="form-card-title">
                        Notes
                    </div>

                    <div class="form-card-description">
                        Additional information relevant to the member record.
                    </div>

                </div>

                <div class="form-card-body">

                    <textarea
                        name="notes"
                        class="form-control-custom"
                        placeholder="Add notes about this member..."
                    >{{ old('notes') }}</textarea>

                </div>

            </div>


            <!-- Documents -->

            <div class="form-card">

                <div class="form-card-header">

                    <div class="form-card-title">
                        Documents
                    </div>

                    <div class="form-card-description">
                        Document uploads will be connected after the document backend is implemented.
                    </div>

                </div>

                <div class="form-card-body">

                    <div
                        style="
                            border:1px dashed var(--legacy-border-cbd6d0, #cbd6d0);
                            border-radius:11px;
                            padding:22px;
                            text-align:center;
                            background:var(--legacy-surface-fbfcfb, #fbfcfb);
                            color:var(--legacy-text-6c7470, #6c7470);
                            font-size:14px;
                        "
                    >
                        <i
                            class="bi bi-cloud-arrow-up"
                            style="
                                font-size:25px;
                                color:var(--primary);
                            "
                        ></i>

                        <div style="margin-top:8px;">
                            Document storage will be connected in the Records backend phase.
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- SIDE PANEL -->

        <aside>

            <div class="info-row">

                <span>
                    Committee
                </span>

                <span id="previewCommittee">
                    Not assigned
                </span>

            </div>


            <div class="info-row">

                <span>
                    Position
                </span>

                <span id="previewPosition">
                    Member
                </span>

            </div>


            <div class="side-card">

                <div class="side-card-title">
                    Record Guidelines
                </div>

                <div class="privacy-note">

                    <i class="bi bi-shield-lock"></i>

                    <p>
                        Member and identity information should only be accessible
                        to authorized users. Sensitive information must be handled
                        according to the organization's access rules.
                    </p>

                </div>

            </div>

        </aside>

    </div>

</form>

@endsection


@push('scripts')
<script>

    const memberNameInput =
        document.getElementById('memberName');

    const previewName =
        document.getElementById('previewName');

    const previewAvatar =
        document.getElementById('previewAvatar');


    memberNameInput.addEventListener(
        'input',
        function () {

            const name =
                this.value.trim();

            previewName.textContent =
                name || 'New Member';


            if (!name) {

                previewAvatar.textContent =
                    '?';

                return;

            }


            const initials =
                name
                    .split(/\s+/)
                    .slice(0, 2)
                    .map(
                        word =>
                            word
                                .charAt(0)
                                .toUpperCase()
                    )
                    .join('');


            previewAvatar.textContent =
                initials;

        }
    );
    const committeeSelect =
    document.getElementById('committeeSelect');

    const committeePosition =
        document.getElementById('committeePosition');

    const previewCommittee =
        document.getElementById('previewCommittee');

    const previewPosition =
        document.getElementById('previewPosition');


    committeeSelect.addEventListener(
        'change',
        function () {

            const option =
                this.options[this.selectedIndex];

            previewCommittee.textContent =
                option.value
                    ? option.text.split(' — ')[0]
                    : 'Not assigned';

        }
    );


    committeePosition.addEventListener(
        'change',
        function () {

            previewPosition.textContent =
                this.value || 'Member';

        }
    );

</script>
@endpush