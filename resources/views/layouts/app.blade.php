<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

    <style>
        :root {
            --primary: #173f35;
            --primary-light: #235c4e;
            --primary-soft: #edf4f1;

            --accent: #c89b3c;

            --bg: #f5f7f6;
            --surface: #ffffff;

            --text: #17211d;
            --muted: #5c6b64;

            --border: #e4eae7;

            --sidebar-width: 255px;
            --header-height: 72px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);

            font-size: 15px;
            line-height: 1.6;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
        }

        h1, h2, h3, h4, h5, h6 {
            line-height: 1.3;
        }

        table {
            font-size: inherit;
        }

        a {
            text-decoration: none;
        }

        /* ======================================
           APP SHELL
        ====================================== */

        .app-shell {
            min-height: 100vh;
        }

        /* ======================================
           SIDEBAR
        ====================================== */

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;

            width: var(--sidebar-width);

            background: var(--surface);
            border-right: 1px solid var(--border);
            box-shadow: 1px 0 0 rgba(23,63,53,.02), 4px 0 24px rgba(23,63,53,.03);

            z-index: 1000;

            display: flex;
            flex-direction: column;

            transition: transform .25s ease;
        }

        .sidebar-brand {
            height: var(--header-height);

            display: flex;
            align-items: center;

            padding: 0 20px;

            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;

            border-radius: 11px;

            padding: 4px;

            background: var(--primary-soft);

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;
        }

        .sidebar-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .sidebar-brand-text {
            margin-left: 11px;
            min-width: 0;
        }

        .sidebar-brand-text strong {
            display: block;

            font-size: 16px;
            font-weight: 700;

            color: var(--text);

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-brand-text span {
            display: block;

            margin-top: 2px;

            font-size: 16px;
            color: var(--muted);

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-nav {
            flex: 1;

            overflow-y: auto;

            padding: 18px 12px;
        }

        .nav-section {
            margin: 18px 8px 8px;

            color: #6c7470;

            font-size: 16px;
            font-weight: 700;

            letter-spacing: 1.1px;
            text-transform: uppercase;
        }

        .nav-section:first-child {
            margin-top: 2px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 11px;

            width: 100%;

            padding: 10px 12px;

            border-radius: 10px;

            color: #697670;

            font-size: 16px;
            font-weight: 550;

            margin-bottom: 3px;

            transition: all .18s ease;
        }

        .nav-item i {
            width: 19px;

            font-size: 17px;

            text-align: center;
        }

        .nav-item:hover {
            background: #f4f7f5;
            color: var(--primary);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary-soft), #e4efe9);
            color: var(--primary);

            font-weight: 650;
            box-shadow: inset 0 0 0 1px rgba(23,63,53,.06);
        }

        .nav-item.active i {
            color: var(--primary);
        }

        .sidebar-bottom {
            padding: 12px;
            flex-shrink: 0;

            border-top: 1px solid var(--border);
        }

        .sidebar-user {
            display: flex;
            align-items: center;

            padding: 10px;

            border-radius: 11px;

            background: #f8faf9;
        }

        .sidebar-avatar {
            width: 34px;
            height: 34px;
            flex-shrink: 0;

            border-radius: 50%;

            background: var(--primary);

            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
            font-weight: 700;
        }

        .sidebar-user-info {
            min-width: 0;
            margin-left: 9px;
        }

        .sidebar-user-info strong {
            display: block;

            font-size: 16px;

            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .sidebar-user-info span {
            display: block;

            font-size: 16px;

            color: var(--muted);

            margin-top: 2px;
            overflow-wrap: anywhere;
            line-height: 1.35;
        }

        .sidebar-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 8px;
            padding: 9px 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: white;
            color: var(--primary);
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .sidebar-logout:hover {
            background: #edf5f1;
        }

        .sidebar-logout:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* ======================================
           MAIN
        ====================================== */

        .main-area {
            margin-left: var(--sidebar-width);

            min-height: 100vh;

            transition: margin-left .25s ease;
        }

        /* ======================================
           HEADER
        ====================================== */

        .topbar {
            height: var(--header-height);

            background: rgba(255,255,255,.96);

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;

            padding: 0 28px;

            position: sticky;
            top: 0;

            z-index: 900;

            backdrop-filter: blur(10px);
        }

        .mobile-menu-btn {
            display: none;

            border: none;
            background: transparent;

            font-size: 21px;

            color: var(--text);

            margin-right: 10px;
        }

        .global-search {
            position: relative;

            width: min(430px, 42vw);
        }

        .global-search i {
            position: absolute;

            left: 14px;
            top: 50%;

            transform: translateY(-50%);

            color: #6f7672;

            font-size: 17px;
        }

        .global-search input {
            width: 100%;
            height: 40px;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 0 15px 0 39px;

            outline: none;

            background: #fafcfa;

            font-size: 16px;

            transition: all .2s ease;
        }

        .global-search input:focus {
            background: white;
            border-color: #6b7470;

            box-shadow:
                0 0 0 3px rgba(23,63,53,.06);
        }

        .topbar-spacer {
            flex: 1;
        }

        .topbar-action {
            width: 38px;
            height: 38px;

            border: 1px solid var(--border);

            background: white;

            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #68756f;

            margin-left: 9px;

            position: relative;

            cursor: pointer;

            transition: all .18s ease;
        }

        .topbar-action:hover {
            color: var(--primary);
            border-color: #6e7471;
            background: #fafcfa;
        }

        .notification-dot {
            position: absolute;

            right: 7px;
            top: 7px;

            width: 7px;
            height: 7px;

            border-radius: 50%;

            background: #c95c4a;

            border: 2px solid white;

            box-sizing: content-box;
        }

        .profile-menu {
            display: flex;
            align-items: center;

            margin-left: 14px;

            min-width: 0;
        }

        .profile-avatar {
            width: 36px;
            height: 36px;
            flex-shrink: 0;

            border-radius: 50%;

            background: var(--primary);

            color: white;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
            font-weight: 700;
        }

        .profile-info {
            margin-left: 9px;
            margin-right: 7px;
            min-width: 0;
            max-width: 240px;
        }

        .profile-info strong,
        .profile-info span {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .profile-info strong {
            display: block;

            font-size: 16px;
            font-weight: 650;
        }

        .profile-info span {
            display: block;

            margin-top: 2px;

            font-size: 16px;

            color: var(--muted);
        }

        .profile-menu > i {
            color: #6c7570;
            font-size: 16px;
        }

        /* ======================================
           PAGE
        ====================================== */

        .page-content {
            padding: 28px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            margin-bottom: 25px;
        }

        .page-heading h1 {
            margin: 0;

            font-size: 25px;
            font-weight: 700;

            letter-spacing: -.5px;
        }

        .page-heading p {
            margin: 7px 0 0;

            font-size: 16px;
            color: var(--muted);
        }

        .page-heading .date {
            font-size: 16px;
            color: #6c7570;
        }

        /* ======================================
           RESPONSIVE
        ====================================== */

        @media (max-width: 1100px) {

            :root {
                --sidebar-width: 225px;
            }

            .page-content {
                padding: 22px;
            }

            .topbar {
                padding: 0 22px;
            }
        }

        @media (max-width: 850px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: 15px 0 40px rgba(0,0,0,.08);
            }

            .main-area {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }

            .global-search {
                width: min(330px, 48vw);
            }

            .profile-info {
                max-width: 140px;
            }
        }

        @media (max-width: 600px) {

            .topbar {
                padding: 0 15px;
            }

            .global-search {
                display: none;
            }

            .page-content {
                padding: 18px 15px;
            }

            .page-heading h1 {
                font-size: 22px;
            }

            .page-heading .date {
                display: none;
            }

            .topbar-action {
                margin-left: 5px;
            }

            .profile-menu {
                margin-left: 7px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

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


        <nav class="sidebar-nav">

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

            <a
            href="{{ route('members.index') }}"
            class="nav-item {{ request()->routeIs('members.*') ? 'active' : '' }}"
        >
            <i class="bi bi-people"></i>
            <span>Members</span>
        </a>

            <a
                href="{{ route('committees.index') }}"
                class="nav-item {{ request()->routeIs('committees.*') ? 'active' : '' }}"
            >
                <i class="bi bi-diagram-3"></i>
                <span>Committees</span>
            </a>

            <a
                href="{{ route('users.index') }}"
                class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
            >
                <i class="bi bi-person-badge"></i>
                <span>Users & Roles</span>
            </a>


            @if(
            auth()->user()->hasPermission('finance.view') ||
            auth()->user()->hasPermission('finance.reports.view')
        )

            <div class="nav-section">
                Finance
            </div>

            @if(auth()->user()->hasPermission('finance.view'))

                <a
                    href="{{ route('finance.donations.index') }}"
                    class="nav-item {{ request()->routeIs('finance.donations.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-cash-coin"></i>
                    <span>Donations</span>
                </a>

                <a
                    href="{{ route('finance.funds.index') }}"
                    class="nav-item {{ request()->routeIs('finance.funds.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-wallet2"></i>
                    <span>Funds</span>
                </a>

                <a
                    href="{{ route('finance.expenses.index') }}"
                    class="nav-item {{ request()->routeIs('finance.expenses.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-receipt"></i>
                    <span>Expenses</span>
                </a>

                <a
                    href="{{ route('finance.restricted-funds.index') }}"
                    class="nav-item {{ request()->routeIs('finance.restricted-funds.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-shield-lock"></i>
                    <span>Restricted Funds</span>
                </a>

                <a
                    href="{{ route('finance.transactions.index') }}"
                    class="nav-item {{ request()->routeIs('finance.transactions.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-journal-text"></i>
                    <span>Transactions</span>
                </a>

            @endif

            @if(auth()->user()->hasPermission('finance.reports.view'))

                <a
                    href="{{ route('finance.reports.index') }}"
                    class="nav-item {{ request()->routeIs('finance.reports.*') ? 'active' : '' }}"
                >
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Finance Reports</span>
                </a>

            @endif

        @endif

            <div class="nav-section">
                Welfare
            </div>

            <a
                href="{{ route('welfare.beneficiaries.index') }}"
                class="nav-item {{ request()->routeIs('welfare.beneficiaries.*') ? 'active' : '' }}"
            >
                <i class="bi bi-heart"></i>
                <span>Beneficiaries</span>
            </a>

            <a
                href="{{ route('welfare.applications.index') }}"
                class="nav-item {{ request()->routeIs('welfare.applications.*') ? 'active' : '' }}"
            >
                <i class="bi bi-file-earmark-person"></i>
                <span>Applications</span>
            </a>

            <a
                href="{{ route('welfare.verification.index') }}"
                class="nav-item {{ request()->routeIs('welfare.verification.*') ? 'active' : '' }}"
            >
                <i class="bi bi-clipboard-check"></i>
                <span>Field Verification</span>
            </a>

            <a
                href="{{ route('welfare.assistance.index') }}"
                class="nav-item {{ request()->routeIs('welfare.assistance.*') && ! request()->routeIs('welfare.assistance.reviews.*') ? 'active' : '' }}"
            >
                <i class="bi bi-hand-heart"></i>
                <span>Welfare Assistance</span>
            </a>

            @if(auth()->user()?->hasPermission('welfare.reviews.view'))
                <a href="{{ route('welfare.assistance.reviews.index') }}" class="nav-item {{ request()->routeIs('welfare.assistance.reviews.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i><span>Reviews &amp; Follow-ups</span>
                </a>
            @endif

            <div class="nav-section">
                Programs
            </div>

            @if(auth()->user()->hasPermission('business.view'))
            <a
                href="{{ route('business.applications.index') }}"
                class="nav-item {{ request()->routeIs('business.*') ? 'active' : '' }}"
            >
                <i class="bi bi-shop"></i>
                <span>Small Businesses</span>
            </a>
            @endif

            <a
                href="{{ route('qard.index') }}"
                class="nav-item {{ request()->routeIs('qard.*') ? 'active' : '' }}"
            >
                <i class="bi bi-arrow-repeat"></i>
                <span>Qard-e-Hasana</span>
            </a>
            @can('projects.view')
            <a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <i class="bi bi-kanban"></i><span>Projects</span>
            </a>
            @endcan

            <div class="nav-section">
                Operations
            </div>

            <a
                href="{{ route('operations.tasks.index') }}"
                class="nav-item {{ request()->routeIs('operations.tasks.*') ? 'active' : '' }}"
            >
                <i class="bi bi-check2-square"></i>
                <span>Tasks</span>
            </a>

            <a
                href="{{ route('operations.procurement.index') }}"
                class="nav-item {{ request()->routeIs('operations.procurement.*') ? 'active' : '' }}"
            >
                <i class="bi bi-cart-check"></i>
                <span>Procurement</span>
            </a>

            <a
                href="{{ route('operations.meetings.index') }}"
                class="nav-item {{ request()->routeIs('operations.meetings.*') ? 'active' : '' }}"
            >
                <i class="bi bi-calendar3"></i>
                <span>Meetings</span>
            </a>


            <div class="nav-section">
                Records
            </div>

            <a
                href="{{ route('records.documents.index') }}"
                class="nav-item {{ request()->routeIs('records.documents.*') ? 'active' : '' }}"
            >
                <i class="bi bi-folder2-open"></i>
                <span>Documents</span>
            </a>

            <a
                href="{{ route('records.reports.index') }}"
                class="nav-item {{ request()->routeIs('records.reports.*') ? 'active' : '' }}"
            >
                <i class="bi bi-bar-chart"></i>
                <span>Reports</span>
            </a>

            <a
                href="{{ route('records.audit.index') }}"
                class="nav-item {{ request()->routeIs('records.audit.*') ? 'active' : '' }}"
            >
                <i class="bi bi-shield-check"></i>
                <span>Audit Trail</span>
            </a>


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


    <!-- MAIN AREA -->
    <div class="main-area">

        <!-- TOPBAR -->
        <header class="topbar">

            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
            >
                <i class="bi bi-list"></i>
            </button>


            <div class="global-search">

                <i class="bi bi-search"></i>

                <input
                    type="search"
                    placeholder="Search members, projects, donations..."
                >

            </div>


            <div class="topbar-spacer"></div>


            <button
                type="button"
                class="topbar-action"
                title="Notifications"
            >
                <i class="bi bi-bell"></i>

                <span class="notification-dot"></span>
            </button>


            <button
                type="button"
                class="topbar-action"
                title="Help"
            >
                <i class="bi bi-question-circle"></i>
            </button>


            <div class="profile-menu" aria-label="Signed-in account">

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

            </div>

        </header>


        <!-- PAGE CONTENT -->
        <main class="page-content">

            @yield('content')

        </main>

    </div>

</div>


<script>
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');

    mobileMenuBtn?.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
</script>

<script src="{{ asset('js/money.js') }}"></script>
@stack('scripts')

</body>
</html>
