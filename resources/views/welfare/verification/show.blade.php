@extends('layouts.app')

@section('title', 'Verification Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-verification-show-1.css') }}">
@endpush


@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-verification-show-2.css') }}">
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/welfare-verification-show-3.css') }}">
@endpush

@section('content')

@php
    $beneficiary = $verification->beneficiary ?? $verification->application?->beneficiary;
    $workflow    = $verification->workflow_status ?? 'assigned';
    $checklist   = $verification->checklist ?? [];
    $checkItems  = \App\Domain\Welfare\Verification\Models\BeneficiaryVerification::CHECKLIST_ITEMS;
    $progress    = match ($workflow) {
        'assigned' => 25, 'in_progress' => 60, 'completed' => 100, default => 0,
    };

    // Only the assigned officer (who also holds the permission) can work the verification.
    $isOfficer = $verification->assigned_to !== null
        && (int) $verification->assigned_to === (int) auth()->id()
        && auth()->user()?->hasPermission('welfare.beneficiaries.verify');

    $evidenceFiles = $verification->evidence;
    $needsEvidence = old('recommendation') === 'approve';
@endphp

@if (session('success'))
    <div class="flash ok">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="flash err">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

@if ($workflow === 'in_progress' && $isOfficer)
    <div class="banner">
        <i class="bi bi-info-circle"></i>
        This verification is in progress. Scroll down to <a href="#complete-verification">record your findings and upload evidence</a>.
    </div>
@endif

<div class="page-header">

    <div class="identity">
        <div class="icon-box"><i class="bi bi-clipboard-check"></i></div>
        <div>
            <div class="title">{{ $verification->verification_number ?? 'VER-'.str_pad($verification->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="meta">
                {{ $beneficiary?->full_name ?? 'Unknown beneficiary' }}
                · {{ $verification->application?->application_number ?? 'No application' }}
                · Assigned to {{ $verification->assignedTo?->name ?? 'Unassigned' }}
            </div>
        </div>
    </div>

    <div class="actions">
        @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.verification.index') }}" class="ui-btn">
            <i class="bi bi-arrow-left"></i> Back
        </a>@endif

        @if ($workflow === 'assigned' && $isOfficer)
            <form method="POST" action="{{ route('welfare.verification.start', $verification) }}"
                  onsubmit="const b=this.querySelector('button');b.disabled=true;b.innerHTML='<i class=&quot;bi bi-hourglass-split&quot;></i> Starting…';">
                @csrf
                <button type="submit" class="ui-btn complete">
                    <i class="bi bi-play-circle"></i> Start Verification
                </button>
            </form>
        @endif
    </div>

</div>


<div class="layout">

    <div>

        <!-- SUMMARY -->
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>Verification Overview</strong>
                    <span>Current assignment information</span>
                </div>
                <span class="status">{{ str_replace('_', ' ', $workflow) }}</span>
            </div>

            <div class="card-body">
                <div class="grid">
                    <div class="info-box">
                        <label>Verification ID</label>
                        <strong>{{ $verification->verification_number ?? '—' }}</strong>
                    </div>
                    <div class="info-box">
                        <label>Application</label>
                        <strong>{{ $verification->application?->application_number ?? '—' }}</strong>
                    </div>
                    <div class="info-box">
                        <label>Assigned Officer</label>
                        <strong>{{ $verification->assignedTo?->name ?? 'Unassigned' }}</strong>
                    </div>
                    <div class="info-box">
                        <label>Assigned Date</label>
                        <strong>{{ ($verification->assigned_at ?? $verification->created_at)?->format('d M Y') ?? '—' }}</strong>
                    </div>
                    <div class="info-box">
                        <label>Visit Date</label>
                        <strong>{{ $verification->visit_date?->format('d M Y') ?? '—' }}</strong>
                    </div>
                    <div class="info-box">
                        <label>Priority</label>
                        <strong>{{ ucfirst($verification->priority ?? 'normal') }}</strong>
                    </div>
                </div>
            </div>
        </div>


        <!-- BENEFICIARY -->
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>Beneficiary</strong>
                    <span>Person whose circumstances are being verified.</span>
                </div>
            </div>

            <div class="card-body">
                <div class="applicant-box">
                    <div class="avatar">
                        {{ strtoupper(substr($beneficiary?->full_name ?? '?', 0, 2)) }}
                    </div>

                    <div style="flex:1">
                        <div class="applicant-name">{{ $beneficiary?->full_name ?? 'Unknown beneficiary' }}</div>
                        <div class="applicant-meta">{{ $beneficiary?->beneficiary_number ?? '—' }}</div>
                    </div>

                    @if ($beneficiary)
                        @if(auth()->user()?->hasPermission('welfare.view'))<a href="{{ route('welfare.beneficiaries.show', $beneficiary) }}" class="ui-btn">
                            View Beneficiary
                        </a>@endif
                    @endif
                </div>
            </div>
        </div>


        <!-- FINDINGS -->
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>Field Assessment</strong>
                    <span>Verification findings recorded during the visit.</span>
                </div>
            </div>

            <div class="card-body">
                @if ($verification->findings)
                    <div class="finding">
                        <strong>Findings</strong>
                        {!! nl2br(e($verification->findings)) !!}
                    </div>
                @else
                    <div class="finding">No findings recorded yet.</div>
                @endif

                @if ($verification->notes)
                    <div class="finding">
                        <strong>Notes</strong>
                        {!! nl2br(e($verification->notes)) !!}
                    </div>
                @endif
            </div>
        </div>


        <!-- CHECKLIST -->
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>Verification Checklist</strong>
                    <span>Evidence-based assessment checklist.</span>
                </div>
            </div>

            <div class="card-body">
                <div class="field-grid">
                    @foreach ($checkItems as $key => $label)
                        <div class="field">
                            <label>{{ $label }}</label>
                            <strong>
                                @if ($workflow !== 'completed')
                                    Pending
                                @else
                                    {{ !empty($checklist[$key]) ? 'Yes' : 'No' }}
                                @endif
                            </strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>


        <!-- EVIDENCE -->
        @if ($evidenceFiles->isNotEmpty())
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>Evidence</strong>
                    <span>{{ $evidenceFiles->count() }} file(s) uploaded by the field officer.</span>
                </div>
            </div>

            <div class="card-body">
                <div class="evidence-grid">
                    @foreach ($evidenceFiles as $file)
                        @if(auth()->user()?->hasPermission('welfare.view'))<a class="evidence ev-card"
                           href="{{ route('welfare.verification.evidence', [$verification, $file]) }}"
                           target="_blank" rel="noopener"
                           title="{{ $file->original_name }}">
                            @if ($file->isImage())
                                <img src="{{ route('welfare.verification.evidence', [$verification, $file]) }}"
                                     alt="{{ $file->original_name }}" loading="lazy">
                            @else
                                <div class="ev-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                            @endif
                            <span class="ev-name">{{ $file->original_name }}</span>
                            <span class="ev-meta">{{ $file->humanSize() }}</span>
                            <span class="ev-dl"
                                  onclick="event.preventDefault();event.stopPropagation();window.location='{{ route('welfare.verification.evidence', [$verification, $file, 'download' => 1]) }}'"
                                  title="Download"><i class="bi bi-download"></i></span>
                        </a>@endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif


        <!-- COMPLETE FORM -->
        @if ($workflow === 'in_progress' && $isOfficer)
        <div class="card" id="complete-verification">
            <div class="card-head">
                <div>
                    <strong>Complete Verification</strong>
                    <span>Record findings, upload evidence and submit a recommendation.</span>
                </div>
            </div>

            <div class="card-body">
                <form method="POST"
                      id="completeForm"
                      action="{{ route('welfare.verification.complete', $verification) }}"
                      enctype="multipart/form-data"
                      class="vform"
                      data-limits='@json($evidenceLimits)'>
                    @csrf

                    <label for="findings">Findings *</label>
                    <textarea id="findings" name="findings" rows="5" required maxlength="10000">{{ old('findings') }}</textarea>
                    @error('findings') <div class="err-text">{{ $message }}</div> @enderror

                    <label>Checklist</label>
                    @foreach ($checkItems as $key => $label)
                        <label class="check">
                            <input type="hidden" name="checklist[{{ $key }}]" value="0">
                            <input type="checkbox" name="checklist[{{ $key }}]" value="1"
                                   @if ($key === 'photos_attached') id="photosAttached" @endif
                                   @checked(old("checklist.$key"))>
                            {{ $label }}
                        </label>
                    @endforeach

                    <label for="recommendation">Recommendation *</label>
                    <select id="recommendation" name="recommendation" required>
                        <option value="">Select…</option>
                        <option value="approve" @selected(old('recommendation') === 'approve')>Approve</option>
                        <option value="revise"  @selected(old('recommendation') === 'revise')>Revise</option>
                        <option value="reject"  @selected(old('recommendation') === 'reject')>Reject</option>
                    </select>
                    @error('recommendation') <div class="err-text">{{ $message }}</div> @enderror

                    <label for="recommendation_notes">Recommendation notes</label>
                    <textarea id="recommendation_notes" name="recommendation_notes" rows="3" maxlength="5000">{{ old('recommendation_notes') }}</textarea>

                    <label for="notes">Additional notes</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes') }}</textarea>

                    <label for="evidenceInput">
                        Evidence photos / documents
                        <span id="evidenceRequired" style="color:#a33;{{ $needsEvidence ? '' : 'display:none' }}">* required for approval</span>
                    </label>

                    <label class="dropzone" id="dropzone" for="evidenceInput">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <strong>Click to choose files or drag &amp; drop here</strong>
                        <small>
                            PDF, JPG, PNG or WEBP · up to {{ $evidenceLimits['max_files'] }} files ·
                            max {{ number_format($evidenceLimits['per_file'] / 1048576, $evidenceLimits['per_file'] % 1048576 ? 1 : 0) }} MB each
                        </small>
                        <input type="file" id="evidenceInput" name="evidence[]" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp">
                    </label>

                    <ul class="file-list" id="fileList"></ul>
                    <div class="upload-summary" id="uploadSummary"></div>
                    <div class="upload-errors" id="uploadErrors" role="alert"></div>

                    @if ($errors->any())
                        <div class="err-text" style="margin-top:8px">
                            If the form was rejected, please re-select your evidence files — browsers don't keep file selections after a reload.
                        </div>
                    @endif

                    <div style="margin-top:16px">
                        <button type="submit" id="completeBtn" class="ui-btn complete">
                            <i class="bi bi-check-circle"></i> Mark Completed
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif


        <!-- HISTORY -->
        <div class="card">
            <div class="card-head">
                <div>
                    <strong>Verification History</strong>
                    <span>Assignment and review activity.</span>
                </div>
            </div>

            <div class="card-body">
                @if ($verification->assigned_at)
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div>
                            <div class="timeline-title">Verification assigned</div>
                            <div class="timeline-meta">
                                {{ $verification->assigned_at->format('d M Y H:i') }}
                                @if ($verification->assignedTo) · {{ $verification->assignedTo->name }} @endif
                            </div>
                        </div>
                    </div>
                @endif

                @if ($verification->started_at)
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div>
                            <div class="timeline-title">Field visit started</div>
                            <div class="timeline-meta">{{ $verification->started_at->format('d M Y H:i') }}</div>
                        </div>
                    </div>
                @endif

                @if ($verification->completed_at)
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div>
                            <div class="timeline-title">Verification completed</div>
                            <div class="timeline-meta">
                                {{ $verification->completed_at->format('d M Y H:i') }}
                                @if ($verification->verifiedBy) · {{ $verification->verifiedBy->name }} @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>


    <aside>

        <div class="card">
            <div class="card-body">
                <div class="side-number">{{ $progress }}%</div>
                <div class="side-label">Verification Complete</div>

                <div style="height:1px;background:#eef1ef;margin:16px 0;"></div>

                <div class="side-row">
                    <span>Status</span>
                    <span>{{ ucfirst(str_replace('_', ' ', $workflow)) }}</span>
                </div>
                <div class="side-row">
                    <span>Officer</span>
                    <span>{{ $verification->assignedTo?->name ?? 'Unassigned' }}</span>
                </div>
                <div class="side-row">
                    <span>Visit</span>
                    <span>{{ $verification->visit_date?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="side-row">
                    <span>Priority</span>
                    <span>{{ ucfirst($verification->priority ?? 'normal') }}</span>
                </div>
            </div>
        </div>


        <div class="card">
            <div class="card-head"><strong>Recommendation</strong></div>
            <div class="card-body">
                @if ($verification->recommendation)
                    <div class="side-row">
                        <span>Decision</span>
                        <span>{{ ucfirst($verification->recommendation) }}</span>
                    </div>
                    @if ($verification->recommendation_notes)
                        <div class="note">
                            <i class="bi bi-info-circle"></i>
                            <p>{{ $verification->recommendation_notes }}</p>
                        </div>
                    @endif
                @else
                    <div class="note">
                        <i class="bi bi-info-circle"></i>
                        <p>
                            Final recommendation should be based on verified
                            field findings and submitted to the appropriate
                            committee for review.
                        </p>
                    </div>
                @endif
            </div>
        </div>

    </aside>

</div>

@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('completeForm');
    if (!form) return;

    const limits   = JSON.parse(form.dataset.limits);
    const input    = document.getElementById('evidenceInput');
    const zone     = document.getElementById('dropzone');
    const list     = document.getElementById('fileList');
    const summary  = document.getElementById('uploadSummary');
    const errBox   = document.getElementById('uploadErrors');
    const photos   = document.getElementById('photosAttached');
    const rec      = document.getElementById('recommendation');
    const reqLabel = document.getElementById('evidenceRequired');
    const btn      = document.getElementById('completeBtn');
    const btnHtml  = btn.innerHTML;

    const okTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    const okExt   = /\.(pdf|jpe?g|png|webp)$/i;

    let files = [];
    const urls = [];

    const fmt = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    const total = () => files.reduce((n, f) => n + f.size, 0);

    function showErrors(msgs) {
        errBox.innerHTML = msgs.map(m => '<div></div>').join('');
        [...errBox.children].forEach((el, i) => el.textContent = msgs[i]);
        errBox.classList.toggle('show', msgs.length > 0);
    }

    function sync() {
        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        input.files = dt.files;
        render();
    }

    function render() {
        urls.splice(0).forEach(u => URL.revokeObjectURL(u));
        list.innerHTML = '';

        files.forEach((f, i) => {
            const li = document.createElement('li');
            li.className = 'file-item';

            let thumb;
            if (f.type.startsWith('image/')) {
                thumb = document.createElement('img');
                thumb.className = 'thumb';
                const u = URL.createObjectURL(f);
                urls.push(u);
                thumb.src = u;
                thumb.alt = '';
            } else {
                thumb = document.createElement('div');
                thumb.className = 'thumb';
                thumb.innerHTML = '<i class="bi bi-file-earmark-pdf"></i>';
            }

            const name = document.createElement('div');
            name.className = 'fname';
            name.textContent = f.name;

            const size = document.createElement('div');
            size.className = 'fsize';
            size.textContent = fmt(f.size);

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.title = 'Remove';
            rm.innerHTML = '<i class="bi bi-x-circle"></i>';
            rm.addEventListener('click', () => { files.splice(i, 1); showErrors([]); sync(); });

            li.append(thumb, name, size, rm);
            list.appendChild(li);
        });

        summary.textContent = files.length
            ? files.length + ' file(s) selected · ' + fmt(total()) + ' total'
            : '';

        if (photos) photos.checked = files.length > 0 ? true : photos.checked;
    }

    function addFiles(incoming) {
        const problems = [];

        for (const f of incoming) {
            const typeOk = okTypes.includes(f.type) || (!f.type && okExt.test(f.name));

            if (!typeOk || !okExt.test(f.name)) {
                problems.push('"' + f.name + '" is not a PDF, JPG, PNG or WEBP file.');
                continue;
            }
            if (f.size > limits.per_file) {
                problems.push('"' + f.name + '" is ' + fmt(f.size) + ' — the limit is ' + fmt(limits.per_file) + ' per file.');
                continue;
            }
            if (files.some(x => x.name === f.name && x.size === f.size && x.lastModified === f.lastModified)) {
                continue; // duplicate
            }
            if (files.length >= limits.max_files) {
                problems.push('You can upload at most ' + limits.max_files + ' files.');
                break;
            }
            if (total() + f.size > limits.total) {
                problems.push('Adding "' + f.name + '" would exceed the total upload limit of ' + fmt(limits.total) + '.');
                continue;
            }
            files.push(f);
        }

        showErrors([...new Set(problems)]);
        sync();
    }

    input.addEventListener('change', () => addFiles(Array.from(input.files)));

    ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => {
        e.preventDefault(); zone.classList.add('drag');
    }));
    ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => {
        e.preventDefault(); zone.classList.remove('drag');
    }));
    zone.addEventListener('drop', e => addFiles(Array.from(e.dataTransfer.files)));

    rec.addEventListener('change', () => {
        reqLabel.style.display = rec.value === 'approve' ? '' : 'none';
    });

    form.addEventListener('submit', e => {
        if (rec.value === 'approve' && files.length === 0) {
            e.preventDefault();
            showErrors(['Please upload at least one photo or document as evidence before recommending approval.']);
            zone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Prevent double submits while large files upload.
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ' +
            (files.length ? 'Uploading &amp; saving…' : 'Saving…');
    });

    // Restore the button if the user comes back via the browser's back button.
    window.addEventListener('pageshow', () => { btn.disabled = false; btn.innerHTML = btnHtml; });
})();
</script>
@endpush
