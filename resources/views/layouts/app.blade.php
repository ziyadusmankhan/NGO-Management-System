<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="{{ asset('js/theme-init.js') }}"></script>

    <title>
        @yield('title', 'Dashboard') — Foundation Management System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >



    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/foundation-ui.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/foundation-legacy-tokens.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/foundation-theme.css') }}?v=2">
    <link rel="stylesheet" media="print" href="{{ asset('css/foundation-print.css') }}?v=3">
<script defer src="{{ asset('js/foundation-print.js') }}?v=1"></script></head>

<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

@php
    $signedInUser = auth()->user();
    $accountName = $signedInUser?->name ?? 'Guest';
    $accountRoles = $signedInUser
        ? $signedInUser->loadMissing('roles')->roles->sortBy('name')->pluck('name')->join(', ')
        : '';
    $accountRoleLabel = $accountRoles ?: ($signedInUser ? 'No role assigned' : 'Not signed in');
    $accountNameParts = preg_split('/\s+/u', trim($accountName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $accountInitials = mb_strtoupper(
        mb_substr($accountNameParts[0] ?? '', 0, 1)
        . (count($accountNameParts) > 1 ? mb_substr(end($accountNameParts), 0, 1) : '')
    ) ?: '?';
@endphp

<div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">

            <div class="sidebar-logo">

                <img
                    src="{{ asset('images/falah-logo.jpg') }}"
                    alt="Organization Logo"
                >

            </div>

            <div class="sidebar-brand-text">

                <strong>
                    تنظیم فلاح نوجوانان کسو
                </strong>

                <span>
                    Foundation Management System
                </span>

            </div>

        </div>


        <nav class="sidebar-nav" aria-label="Main navigation">

            <div class="nav-section">
                Overview
            </div>

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>


            <div class="nav-section">
                Organization
            </div>

            @if(auth()->user()?->hasPermission('members.view'))<a
            href="{{ route('members.index') }}"
            class="nav-item {{ request()->routeIs('members.*') ? 'active' : '' }}"
        >
            <i class="bi bi-people"></i>
            <span>Members</span>
        </a>@endif

            @if(auth()->user()?->hasPermission('committees.view'))<a
                href="{{ route('committees.index') }}"
                class="nav-item {{ request()->routeIs('committees.*') ? 'active' : '' }}"
            >
                <i class="bi bi-diagram-3"></i>
                <span>Committees</span>
            </a>@endif

            @if(auth()->user()?->hasPermission('users.view'))<a
                href="{{ route('users.index') }}"
                class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
            >
                <i class="bi bi-person-badge"></i>
                <span>Users & Roles</span>
            </a>@endif


            @if(
            auth()->user()->hasPermission('finance.view') ||
            auth()->user()->hasPermission('finance.reports.view')
        )

            <div class="nav-section">
                Finance
            </div>

            @if(auth()->user()->hasPermission('finance.view'))

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.donations.index') }}"
                    class="nav-item {{ request()->routeIs('finance.donations.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-cash-coin"></i>
                    <span>Donations</span>
                </a>@endif

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.funds.index') }}"
                    class="nav-item {{ request()->routeIs('finance.funds.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-wallet2"></i>
                    <span>Funds</span>
                </a>@endif

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.expenses.index') }}"
                    class="nav-item {{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-receipt"></i>
                    <span>Expenses</span>
                </a>@endif

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.restricted-funds.index') }}"
                    class="nav-item {{ request()->routeIs('finance.restricted-funds.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-shield-lock"></i>
                    <span>Restricted Funds</span>
                </a>@endif

                @if(auth()->user()?->hasPermission('finance.view'))<a
                    href="{{ route('finance.transactions.index') }}"
                    class="nav-item {{ request()->routeIs('finance.transactions.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-journal-text"></i>
                    <span>Transactions</span>
                </a>@endif

            @endif

            @if(auth()->user()->hasPermission('finance.reports.view'))

                @if(auth()->user()?->hasPermission('finance.reports.view'))<a
                    href="{{ route('finance.reports.index') }}"
                    class="nav-item {{ request()->routeIs('finance.reports.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Finance Reports</span>
                </a>@endif

            @endif

        @endif

            <div class="nav-section">
                Welfare
            </div>

            @if(auth()->user()?->hasPermission('welfare.view'))<a
                href="{{ route('welfare.beneficiaries.index') }}"
                class="nav-item {{ request()->routeIs('welfare.beneficiaries.*') ? 'active' : '' }}"
            >
                <i class="bi bi-heart"></i>
                <span>Beneficiaries</span>
            </a>@endif

            @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                href="{{ route('welfare.applications.index') }}"
                class="nav-item {{ request()->routeIs('welfare.applications.*') ? 'active' : '' }}"
            >
                <i class="bi bi-file-earmark-person"></i>
                <span>Applications</span>
            </a>@endif

            @if(auth()->user()?->hasPermission('welfare.view'))<a
                href="{{ route('welfare.verification.index') }}"
                class="nav-item {{ request()->routeIs('welfare.verification.*') ? 'active' : '' }}"
            >
                <i class="bi bi-clipboard-check"></i>
                <span>Field Verification</span>
            </a>@endif

            @if(auth()->user()?->hasPermission('welfare.assistance.view'))<a
                href="{{ route('welfare.assistance.index') }}"
                class="nav-item {{ request()->routeIs('welfare.assistance.*') && ! request()->routeIs('welfare.assistance.reviews.*') ? 'active' : '' }}"
            >
                <i class="bi bi-hand-heart"></i>
                <span>Welfare Assistance</span>
            </a>@endif

            @if(auth()->user()?->hasPermission('welfare.reviews.view'))
                @if(auth()->user()?->hasPermission('welfare.assistance.view') && auth()->user()?->hasPermission('welfare.reviews.view'))<a href="{{ route('welfare.assistance.reviews.index') }}" class="nav-item {{ request()->routeIs('welfare.assistance.reviews.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i><span>Reviews &amp; Follow-ups</span>
                </a>@endif
            @endif

            <div class="nav-section">
                Programs
            </div>

            @if(auth()->user()->hasPermission('business.view'))
            @if(auth()->user()?->hasPermission('business.view'))<a
                href="{{ route('business.applications.index') }}"
                class="nav-item {{ request()->routeIs('business.*') ? 'active' : '' }}"
            >
                <i class="bi bi-shop"></i>
                <span>Small Businesses</span>
            </a>@endif
            @endif

            @if(auth()->user()?->hasPermission('qard.view'))<a
                href="{{ route('qard.index') }}"
                class="nav-item {{ request()->routeIs('qard.*') ? 'active' : '' }}"
            >
                <i class="bi bi-arrow-repeat"></i>
                <span>Qard-e-Hasana</span>
            </a>@endif
            @can('projects.view')
            @if(auth()->user()?->hasPermission('projects.view'))<a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <i class="bi bi-kanban"></i><span>Projects</span>
            </a>@endif
            @endcan

            <div class="nav-section">
                Operations
            </div>

            @can('tasks.view')@if(auth()->user()?->hasPermission('tasks.view'))<a
                href="{{ route('operations.tasks.index') }}"
                class="nav-item {{ request()->routeIs('operations.tasks.*') ? 'active' : '' }}"
            >
                <i class="bi bi-check2-square"></i>
                <span>Tasks</span>
            </a>@endif
@endcan

            @if(auth()->user()->hasPermission('procurement.view'))
@if(auth()->user()?->hasPermission('procurement.view'))<a
                href="{{ route('operations.procurement.index') }}"
                class="nav-item {{ request()->routeIs('operations.procurement.*') ? 'active' : '' }}"
            >
                <i class="bi bi-cart-check"></i>
                <span>Procurement</span>
            </a>@endif
@endif

            @can('meetings.view')@if(auth()->user()?->hasPermission('meetings.view'))<a
                href="{{ route('operations.meetings.index') }}"
                class="nav-item {{ request()->routeIs('operations.meetings.*') ? 'active' : '' }}"
            >
                <i class="bi bi-calendar3"></i>
                <span>Meetings</span>
            </a>@endif
@endcan


            <div class="nav-section">
                Records
            </div>

            @if(auth()->user()?->hasPermission('documents.view'))<a
                href="{{ route('records.documents.index') }}"
                class="nav-item {{ request()->routeIs('records.documents.*') ? 'active' : '' }}"
            >
                <i class="bi bi-folder2-open"></i>
                <span>Documents</span>
            </a>@endif

            @can('reports.view')@if(auth()->user()?->hasPermission('reports.view'))<a
                href="{{ route('records.reports.index') }}"
                class="nav-item {{ request()->routeIs('records.reports.*') ? 'active' : '' }}"
            >
                <i class="bi bi-bar-chart"></i>
                <span>Reports</span>
            </a>@endif
@endcan

            @can('audit.view')@if(auth()->user()?->hasPermission('audit.view'))<a
                href="{{ route('records.audit.index') }}"
                class="nav-item {{ request()->routeIs('records.audit.*') ? 'active' : '' }}"
            >
                <i class="bi bi-shield-check"></i>
                <span>Audit Trail</span>
            </a>@endif
@endcan


            <div class="nav-section">
                System
            </div>
            <a
            href="{{ route('settings.index') }}"
            class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}"
        >
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>

            

        </nav>


        <div class="sidebar-bottom">

            <div class="sidebar-user">

                <div class="sidebar-avatar">
                    {{ $accountInitials }}
                </div>

                <div class="sidebar-user-info">

                    <strong title="{{ $accountName }}">
                        {{ $accountName }}
                    </strong>

                    <span title="{{ $accountRoleLabel }}">
                        {{ $accountRoleLabel }}
                    </span>

                </div>

            </div>

            @auth
                <a href="{{ route('security.index') }}" class="sidebar-logout">Account Security</a>
                    <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        Log out
                    </button>
                </form>
            @endauth

        </div>

    </aside>
    <button type="button" class="sidebar-backdrop" aria-label="Close navigation" tabindex="-1"></button>


    <!-- MAIN AREA -->
    <div class="main-area">

        <!-- TOPBAR -->
        <header class="topbar">

            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Toggle navigation"
                aria-controls="sidebar"
                aria-expanded="true"
            >
                <i class="bi bi-list"></i>
            </button>


            <form class="global-search" method="get" action="{{ route('search.index') }}">

                <i class="bi bi-search"></i>

                <input
                    type="search"
                    name="q"
                    value="{{ request()->routeIs('search.index') ? request('q') : '' }}"
                    minlength="2"
                    maxlength="100"
                    placeholder="Search members, projects, donations..."
                    aria-label="Global search"
                >

            </form>


            <div class="topbar-spacer"></div>
            <a class="topbar-action ui-mobile-search" href="{{ route('search.index') }}" aria-label="Search"><i class="bi bi-search" aria-hidden="true"></i></a>


            <a
                href="{{ route('notifications.index') }}"
                class="topbar-action"
                title="Notifications"
                aria-label="Notifications{{ $notificationUnreadCount ? ': '.$notificationUnreadCount.' unread' : '' }}"
            >
                <i class="bi bi-bell"></i>
                @if($notificationUnreadCount)<span class="notification-count">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>@endif
            </a>


            <button type="button" class="topbar-action theme-toggle" id="themeToggle" aria-label="Switch to dark theme" aria-pressed="false" title="Switch to dark theme"><i class="bi bi-moon theme-moon" aria-hidden="true"></i><i class="bi bi-sun theme-sun" aria-hidden="true"></i></button>
            <details class="account-dropdown"><summary class="profile-menu" aria-label="Account menu for {{ $accountName }}">

                <div class="profile-avatar">
                    {{ $accountInitials }}
                </div>

                <div class="profile-info">

                    <strong title="{{ $accountName }}">
                        {{ $accountName }}
                    </strong>

                    <span title="{{ $accountRoleLabel }}">
                        {{ $accountRoleLabel }}
                    </span>

                </div>

            </summary><div class="account-dropdown-menu"><a href="{{ route('security.index') }}">Account Security</a><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form></div></details>

        </header>


        <!-- PAGE CONTENT -->
        <main class="page-content" id="main-content" tabindex="-1">
            @unless(request()->routeIs('dashboard'))
            <nav class="ui-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('dashboard') }}">Overview</a><span aria-hidden="true">/</span><span aria-current="page">@yield('title', 'Workspace')</span></nav>
            @endunless

            @yield('content')

        </main>

    </div>

</div>


<script src="{{ asset('js/money.js') }}"></script>
@stack('scripts')
<script src="{{ asset('js/foundation-ui.js') }}?v=1"></script>

</body>
</html>
