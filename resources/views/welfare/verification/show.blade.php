@extends('layouts.app')

@section('title', 'Verification Details')

@push('styles')
<style>
    .page-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        margin-bottom:22px;
    }

    .identity {
        display:flex;
        align-items:center;
        gap:14px;
    }

    .icon-box {
        width:62px;
        height:62px;
        border-radius:17px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 22px;
    }

    .title {
        font-size: 23px;
        font-weight:700;
        letter-spacing:-.4px;
    }

    .meta {
        margin-top:4px;
        color: #6e7773;
        font-size: 16px;
    }

    .actions {
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .ui-btn {
        height:39px;
        padding:0 12px;
        border-radius:9px;
        display:inline-flex;
        align-items:center;
        gap:7px;
        border:1px solid var(--border);
        background:#fff;
        color:#65726c;
        font-size: 16px;
        font-weight:650;
        cursor:pointer;
        text-decoration:none;
    }

    .ui-btn.primary {
        background:var(--primary);
        border-color:var(--primary);
        color:#fff;
    }

    .ui-btn.complete {
        background:#edf7f0;
        border-color: #6a746d;
        color:#397955;
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
        padding:16px 19px;
        border-bottom:1px solid #eef1ef;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:15px;
    }

    .card-head strong {
        font-size: 16px;
    }

    .card-head span {
        display:block;
        margin-top:4px;
        color: #6c7470;
        font-size: 16px;
    }

    .card-body {
        padding:18px 19px;
    }

    .status {
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:5px 8px;
        border-radius:20px;
        background:#eef3f8;
        color:#55758d;
        font-size: 16px;
        font-weight:650;
        text-transform:capitalize;
    }

    .status::before {
        content:"";
        width:5px;
        height:5px;
        border-radius:50%;
        background:#6d8ea5;
    }

    .grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .info-box {
        padding:13px;
        background:#fafcfb;
        border:1px solid #edf1ee;
        border-radius:10px;
    }

    .info-box label {
        display:block;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .info-box strong {
        display:block;
        margin-top:5px;
        font-size: 16px;
    }

    .applicant-box {
        display:flex;
        align-items:center;
        gap:11px;
    }

    .avatar {
        width:40px;
        height:40px;
        border-radius:11px;
        background:var(--primary-soft);
        color:var(--primary);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size: 16px;
        font-weight:700;
    }

    .applicant-name {
        font-size: 16px;
        font-weight:650;
    }

    .applicant-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .field-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:17px;
    }

    .field label {
        display:block;
        margin-bottom:6px;
        color: #6d7571;
        font-size: 16px;
        text-transform:uppercase;
        letter-spacing:.5px;
    }

    .field strong {
        display:block;
        color:#46534c;
        font-size: 16px;
    }

    .field-text {
        color:#69766f;
        font-size: 16px;
        line-height:1.7;
    }

    .finding {
        padding:12px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
        color:#65726c;
        font-size: 16px;
        line-height:1.6;
    }

    .finding + .finding {
        margin-top:9px;
    }

    .finding strong {
        display:block;
        color:#46534c;
        font-size: 16px;
        margin-bottom:4px;
    }

    .evidence-grid {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:10px;
    }

    .evidence {
        min-height:95px;
        border:1px solid #edf1ee;
        border-radius:10px;
        background:#fafcfb;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        color: #6e7772;
    }

    .evidence i {
        font-size: 22px;
        color:var(--primary);
        margin-bottom:7px;
    }

    .evidence span {
        font-size: 16px;
    }

    .timeline-item {
        display:flex;
        gap:11px;
        padding-bottom:18px;
        position:relative;
    }

    .timeline-item:last-child {
        padding-bottom:0;
    }

    .timeline-dot {
        width:9px;
        height:9px;
        margin-top:4px;
        border-radius:50%;
        background:var(--primary);
        flex-shrink:0;
        position:relative;
    }

    .timeline-item:not(:last-child) .timeline-dot::after {
        content:"";
        position:absolute;
        width:1px;
        height:29px;
        left:4px;
        top:9px;
        background:#dae3df;
    }

    .timeline-title {
        font-size: 16px;
        font-weight:650;
    }

    .timeline-meta {
        margin-top:3px;
        color: #6c7470;
        font-size: 16px;
    }

    .side-number {
        font-size: 27px;
        font-weight:700;
        color:var(--primary);
        text-align:center;
    }

    .side-label {
        text-align:center;
        margin-top:3px;
        color: #6e7772;
        font-size: 16px;
    }

    .side-row {
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:9px 0;
        border-bottom:1px solid #f0f3f1;
        font-size: 16px;
    }

    .side-row:last-child {
        border-bottom:none;
    }

    .side-row span:first-child {
        color: #6d7571;
    }

    .side-row span:last-child {
        color:#4a5750;
        font-weight:650;
        text-align:right;
    }

    .note {
        display:flex;
        gap:9px;
        padding:12px;
        border-radius:10px;
        background:#f7f9f8;
        border:1px solid #e5ebe8;
    }

    .note i {
        color:var(--primary);
        font-size: 17px;
    }

    .note p {
        margin:0;
        color: #6b766f;
        font-size: 16px;
        line-height:1.6;
    }

    @media(max-width:900px) {
        .layout {
            grid-template-columns:1fr;
        }
    }

    @media(max-width:700px) {
        .page-header {
            flex-direction:column;
        }

        .actions {
            width:100%;
        }

        .actions .ui-btn {
            flex:1;
            justify-content:center;
        }

        .grid,
        .field-grid,
        .evidence-grid {
            grid-template-columns:1fr;
        }
    }
</style>
@endpush


@push('styles')
<style>
    .flash{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:14px}
    .flash.ok{background:#e8f5ee;color:#1e6b45}
    .flash.err{background:#fdecec;color:#a33}
    .vform label{display:block;font-size:13px;font-weight:600;margin:12px 0 6px}
    .vform textarea,.vform select{width:100%;border:1px solid var(--border);border-radius:9px;padding:9px 11px;font:inherit;background:#fff}
    .vform .check{display:flex;align-items:center;gap:8px;font-weight:400;margin:8px 0}
    .vform .err-text{color:#a33;font-size:12px;margin-top:4px}
</style>
@endpush

@push('styles')
<style>
    .dropzone{display:block;border:2px dashed #cdd8d2;border-radius:12px;background:#fafcfb;padding:22px 16px;text-align:center;cursor:pointer;transition:.15s;color:#6d7571}
    .dropzone:hover,.dropzone.drag{border-color:var(--primary);background:var(--primary-soft)}
    .dropzone i{font-size:28px;color:var(--primary);display:block;margin-bottom:6px}
    .dropzone strong{display:block;color:#46534c;font-size:15px}
    .dropzone small{display:block;margin-top:4px;font-size:13px}
    .dropzone input[type=file]{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
    .file-list{list-style:none;margin:12px 0 0;padding:0;display:grid;gap:8px}
    .file-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border:1px solid #edf1ee;border-radius:10px;background:#fff}
    .file-item .thumb{width:42px;height:42px;border-radius:8px;object-fit:cover;background:#eef3f0;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:20px}
    .file-item .fname{flex:1;min-width:0;font-size:14px;color:#46534c;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .file-item .fsize{font-size:12px;color:#6d7571}
    .file-item button{border:0;background:none;color:#a33;cursor:pointer;font-size:18px;padding:4px}
    .upload-errors{margin-top:10px;padding:10px 12px;border-radius:10px;background:#fdecec;color:#a33;font-size:13px;display:none}
    .upload-errors.show{display:block}
    .upload-errors div+div{margin-top:3px}
    .upload-summary{margin-top:8px;font-size:12px;color:#6d7571}
    .ev-card{position:relative;min-height:120px;padding:8px;text-decoration:none;gap:6px;overflow:hidden}
    .ev-card img{width:100%;height:88px;object-fit:cover;border-radius:7px;background:#eef3f0}
    .ev-card .ev-icon{height:88px;display:flex;align-items:center;justify-content:center}
    .ev-card .ev-icon i{font-size:34px;margin:0}
    .ev-card .ev-name{display:block;width:100%;font-size:12px;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#46534c}
    .ev-card .ev-meta{font-size:11px;color:#6d7571}
    .ev-dl{position:absolute;top:10px;right:10px;width:26px;height:26px;border-radius:7px;background:#fff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:#55625b;font-size:13px}
    .ui-btn[disabled]{opacity:.65;cursor:not-allowed}
    .banner{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:14px;background:#eef5fb;color:#2f5f85}
</style>
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
        <a href="{{ route('welfare.verification.index') }}" class="ui-btn">
            <i class="bi bi-arrow-left"></i> Back
        </a>

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
                        <a href="{{ route('welfare.beneficiaries.show', $beneficiary) }}" class="ui-btn">
                            View Beneficiary
                        </a>
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
                        <a class="evidence ev-card"
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
                        </a>
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
