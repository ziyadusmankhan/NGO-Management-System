@extends('layouts.app')

@section('title', 'Organization Settings')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/views/settings-index-1.css') }}">
@endpush

@section('content')

<div class="settings-header">

    <h1>Organization Settings</h1>

    <p>
        Configure organization information, financial rules,
        workflows and system preferences.
    </p>

</div>


<div class="settings-layout">

    <!-- SETTINGS NAVIGATION -->

    <aside class="settings-nav">

        <button
            class="settings-nav-item active"
            data-section="organization"
        >
            <i class="bi bi-building"></i>
            Organization
        </button>

        <button
            class="settings-nav-item"
            data-section="structure"
        >
            <i class="bi bi-diagram-3"></i>
            Structure
        </button>

        <button
            class="settings-nav-item"
            data-section="finance"
        >
            <i class="bi bi-wallet2"></i>
            Finance
        </button>

        <button
            class="settings-nav-item"
            data-section="workflow"
        >
            <i class="bi bi-signpost-split"></i>
            Workflows
        </button>

        <button
            class="settings-nav-item"
            data-section="notifications"
        >
            <i class="bi bi-bell"></i>
            Notifications
        </button>

        <button
            class="settings-nav-item"
            data-section="security"
        >
            <i class="bi bi-shield-lock"></i>
            Security
        </button>

        <div class="settings-nav-divider"></div>

        <button
            class="settings-nav-item"
            data-section="system"
        >
            <i class="bi bi-sliders"></i>
            System
        </button>

    </aside>


    <!-- =========================================
         SETTINGS CONTENT
    ========================================== -->

    <main>


        <!-- =====================================
             ORGANIZATION
        ====================================== -->

        <section
            class="settings-section active"
            id="section-organization"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Organization Information
                    </strong>

                    <span>
                        Basic information displayed throughout the management system.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="form-grid">

                        <div class="form-group">

                            <label class="form-label">
                                Organization Name
                            </label>

                            <input
                                class="form-control"
                                value="تنظیم فلاح نوجوانان کسو"
                            >

                        </div>

                        <div class="form-group">

                            <label class="form-label">
                                English Name
                            </label>

                            <input
                                class="form-control"
                                value="Tanzeem Falah Naujawanan Kasso"
                            >

                        </div>

                        <div class="form-group">

                            <label class="form-label">
                                Location
                            </label>

                            <input
                                class="form-control"
                                value="Kasso"
                            >

                        </div>

                        <div class="form-group">

                            <label class="form-label">
                                Organization Status
                            </label>

                            <select class="form-select">

                                <option selected>
                                    Active
                                </option>

                                <option>
                                    Inactive
                                </option>

                            </select>

                        </div>

                        <div class="form-group full">

                            <label class="form-label">
                                Organization Objective
                            </label>

                            <textarea class="form-control">To improve the social and economic well-being of the people of Kasso through collective effort, responsible use of resources, welfare activities, community development, and sustainable livelihood opportunities.</textarea>

                        </div>

                        <div class="form-group full">

                            <label class="form-label">
                                Organization Description
                            </label>

                            <textarea class="form-control">Tanzeem Falah Naujawanan Kasso was created to bring the people of Kasso together for community development, mutual support, and sustainable welfare work.</textarea>

                        </div>

                    </div>

                </div>

            </div>


            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Organization Branding
                    </strong>

                    <span>
                        Manage the logo and identity used across the application.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="branding-row">

                        <div class="current-logo">

                            <img
                                src="{{ asset('images/organization-logo.png') }}"
                                alt="Organization Logo"
                            >

                        </div>

                        <div class="logo-actions">

                            <button class="mini-btn primary">
                                <i class="bi bi-upload"></i>
                                Replace Logo
                            </button>

                            <button class="mini-btn">
                                Remove Logo
                            </button>

                            <div class="form-help">
                                Recommended: PNG or SVG with transparent background.
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================
             STRUCTURE
        ====================================== -->

        <section
            class="settings-section"
            id="section-structure"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Organizational Structure
                    </strong>

                    <span>
                        Configurable committees, positions and responsibilities.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="config-list">

                        <div class="config-row">

                            <div class="config-main">

                                <strong>
                                    Committees
                                </strong>

                                <span>
                                    Organizational committees currently configured.
                                </span>

                            </div>

                            <div class="config-value">

                                <span class="value-badge">
                                    8 committees
                                </span>

                                <button class="edit-small">
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">

                                <strong>
                                    Positions
                                </strong>

                                <span>
                                    Configurable positions and responsibilities.
                                </span>

                            </div>

                            <div class="config-value">

                                <span class="value-badge">
                                    18 positions
                                </span>

                                <button class="edit-small">
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">

                                <strong>
                                    Reporting Structure
                                </strong>

                                <span>
                                    Define who reports to whom and approval hierarchy.
                                </span>

                            </div>

                            <div class="config-value">

                                <span class="value-badge">
                                    Configured
                                </span>

                                <button class="edit-small">
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================
             FINANCE
        ====================================== -->

        <section
            class="settings-section"
            id="section-finance"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Financial Configuration
                    </strong>

                    <span>
                        Configure funds, financial defaults and restricted-fund behavior.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="config-list">

                        <div class="config-row">

                            <div class="config-main">
                                <strong>Default Currency</strong>
                                <span>Primary currency used in organization records.</span>
                            </div>

                            <div class="config-value">
                                <span class="value-badge">PKR</span>
                                <button class="edit-small">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">
                                <strong>Configured Funds</strong>
                                <span>General, business, Qard-e-Hasana, education and other funds.</span>
                            </div>

                            <div class="config-value">
                                <span class="value-badge">6 funds</span>
                                <button class="edit-small">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">
                                <strong>Restricted Fund Protection</strong>
                                <span>Prevent unrelated spending from designated funds.</span>
                            </div>

                            <div class="config-value">
                                <span class="value-badge">Enabled</span>
                                <button class="edit-small">
                                    <i class="bi bi-shield-check"></i>
                                </button>
                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">
                                <strong>Fund Recycling</strong>
                                <span>Recovered restricted principal remains in its approved fund.</span>
                            </div>

                            <div class="config-value">
                                <span class="value-badge">Enabled</span>
                                <button class="edit-small">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Procurement Rules
@if(auth()->user()->hasPermission('procurement.view'))@if(auth()->user()?->hasPermission('procurement.view'))<a href="{{ route('operations.procurement.settings') }}">Manage saved procurement rules</a>@endif
@endif
                    </strong>

                    <span>
                        Rules used for significant purchases and quotations.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="form-grid">

                        <div class="form-group">

                            <label class="form-label">
                                Required Quotations
                            </label>

                            <select class="form-select">

                                <option>1 quotation</option>
                                <option selected>2 quotations</option>
                                <option>3 quotations</option>
                                <option>4 quotations</option>

                            </select>

                        </div>

                        <div class="form-group">

                            <label class="form-label">
                                Procurement Review
                            </label>

                            <select class="form-select">

                                <option selected>
                                    Required for significant purchases
                                </option>

                                <option>
                                    Optional
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================
             WORKFLOWS
        ====================================== -->

        <section
            class="settings-section"
            id="section-workflow"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Approval Workflows
                    </strong>

                    <span>
                        Configure approval limits and organizational decision paths.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="config-list">

                        <div class="config-row">

                            <div class="config-main">

                                <strong>
                                    Project Approval
                                </strong>

                                <span>
                                    Field Review → Implementation Committee →
                                    General Secretary → Decision-Making Committee.
                                </span>

                            </div>

                            <div class="config-value">

                                <span class="value-badge">
                                    4 stages
                                </span>

                                <button class="edit-small">
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">

                                <strong>
                                    Small Business Assistance
                                </strong>

                                <span>
                                    Application → Verification → Recommendation →
                                    Approval → Agreement → Disbursement.
                                </span>

                            </div>

                            <div class="config-value">

                                <span class="value-badge">
                                    7 stages
                                </span>

                                <button class="edit-small">
                                    <i class="bi bi-arrow-right"></i>
                                </button>

                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">

                                <strong>
                                    Expense Approval Limits
                                </strong>

                                <span>
                                    Configurable limits rather than hard-coded amounts.
                                </span>

                            </div>

                            <div class="config-value">

                                <span class="value-badge">
                                    Configurable
                                </span>

                                <button class="edit-small">
                                    <i class="bi bi-pencil"></i>
                                </button>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Approval Safety Rules
                    </strong>

                    <span>
                        Protection against one-person control of important decisions.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Prevent self-approval</strong>
                            <span>A user cannot approve their own financial request.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Require approval before spending</strong>
                            <span>No project spending before required approval.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Require documented approval</strong>
                            <span>Important approvals must have traceable records.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================
             NOTIFICATIONS
        ====================================== -->

        <section
            class="settings-section"
            id="section-notifications"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Notification Preferences
                    </strong>

                    <span>
                        Configure which events should notify authorized users.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Project approval / rejection</strong>
                            <span>Notify responsible users about decisions.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Beneficiary applications</strong>
                            <span>Notify authorized users about new applications.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Loan installments due / missed</strong>
                            <span>Notify responsible users about repayment activity.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Donation recorded</strong>
                            <span>Notify authorized finance users when donations are entered.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Restricted fund usage</strong>
                            <span>Notify authorized users when designated funds are used.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Monthly report ready</strong>
                            <span>Notify authorized users when monthly reports are prepared.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================
             SECURITY
        ====================================== -->

        <section
            class="settings-section"
            id="section-security"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Security Settings
                    </strong>

                    <span>
                        Configure account and application security policies.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Two-factor authentication</strong>
                            <span>Require an additional verification factor for users.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox">
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Session timeout</strong>
                            <span>Automatically end inactive user sessions.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Audit trail</strong>
                            <span>Record important administrative and financial changes.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>


                    <div class="switch-row">

                        <div class="switch-text">
                            <strong>Soft delete important records</strong>
                            <span>Prevent permanent deletion of important approved or financial records.</span>
                        </div>

                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>

                    </div>

                </div>

            </div>


            <div class="settings-card danger-card">

                <div class="settings-card-header">

                    <strong style="color:var(--legacy-text-8f5d51, #8f5d51);">
                        Sensitive Security Actions
                    </strong>

                    <span>
                        These actions should be restricted to authorized administrators.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="danger-note">

                        <i class="bi bi-exclamation-triangle"></i>

                        <p>
                            Destructive actions, permanent deletion and
                            security-policy changes should require appropriate
                            authorization and should be recorded in the audit trail.
                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================
             SYSTEM
        ====================================== -->

        <section
            class="settings-section"
            id="section-system"
        >

            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        System Preferences
                    </strong>

                    <span>
                        General interface and application behavior.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="form-grid">

                        <div class="form-group">

                            <label class="form-label">
                                Date Format
                            </label>

                            <select class="form-select">

                                <option selected>
                                    DD MMM YYYY
                                </option>

                                <option>
                                    DD/MM/YYYY
                                </option>

                                <option>
                                    YYYY-MM-DD
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Time Zone
                            </label>

                            <select class="form-select">

                                <option selected>
                                    Asia/Karachi
                                </option>

                                <option>
                                    UTC
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Default Language
                            </label>

                            <select class="form-select">

                                <option selected>
                                    English
                                </option>

                                <option>
                                    Urdu
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Default Records Per Page
                            </label>

                            <select class="form-select">

                                <option>10</option>
                                <option selected>25</option>
                                <option>50</option>
                                <option>100</option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            <div class="settings-card">

                <div class="settings-card-header">

                    <strong>
                        Data & Backup
                    </strong>

                    <span>
                        Database and document storage preferences.
                    </span>

                </div>

                <div class="settings-card-body">

                    <div class="config-list">

                        <div class="config-row">

                            <div class="config-main">
                                <strong>Database Backups</strong>
                                <span>Automatic database backups.</span>
                            </div>

                            <div class="config-value">
                                <span class="value-badge">Enabled</span>
                                <button class="edit-small">
                                    <i class="bi bi-gear"></i>
                                </button>
                            </div>

                        </div>


                        <div class="config-row">

                            <div class="config-main">
                                <strong>Document Storage</strong>
                                <span>Secure storage for agreements, receipts and other documents.</span>
                            </div>

                            <div class="config-value">
                                <span class="value-badge">Private</span>
                                <button class="edit-small">
                                    <i class="bi bi-folder2"></i>
                                </button>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- SAVE -->

        <div class="save-bar">

            <div class="save-status">
                UI preview mode · Changes are not persisted yet.
            </div>

            <div class="save-actions">

                <button class="save-btn">
                    Reset
                </button>

                <button
                    class="save-btn primary"
                    onclick="previewSaveSettings()"
                >
                    <i class="bi bi-check-lg"></i>
                    Save Changes
                </button>

            </div>

        </div>

    </main>

</div>

@endsection


@push('scripts')
<script>

    const navItems =
        document.querySelectorAll('.settings-nav-item');

    const sections =
        document.querySelectorAll('.settings-section');


    navItems.forEach(item => {

        item.addEventListener('click', () => {

            const section =
                item.dataset.section;

            navItems.forEach(nav => {
                nav.classList.remove('active');
            });

            sections.forEach(panel => {
                panel.classList.remove('active');
            });

            item.classList.add('active');

            document
                .getElementById('section-' + section)
                .classList.add('active');

        });

    });


    function previewSaveSettings() {

        alert(
            'UI preview only.\n\n' +
            'Organization settings will be persisted when the backend is connected.'
        );

    }

</script>
@endpush