<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ config('app.name', 'Foundation Management System') }} — Login</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        :root {
            --primary: #173f35;
            --primary-light: #235c4e;
            --accent: #c89b3c;
            --background: #f5f7f6;
            --text-dark: #17211d;
            --text-muted: #6f7b76;
            --border: #e3e8e5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--background);
            color: var(--text-dark);
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
        }

        /* --------------------------------
           LEFT BRANDING PANEL
        -------------------------------- */

        .brand-panel {
            width: 48%;
            min-height: 100vh;
            background:
                radial-gradient(
                    circle at 20% 20%,
                    rgba(255,255,255,0.08),
                    transparent 30%
                ),
                linear-gradient(
                    145deg,
                    #12352d 0%,
                    #173f35 45%,
                    #0d2d25 100%
                );

            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            width: 430px;
            height: 430px;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 50%;
            top: -180px;
            right: -130px;
        }

        .brand-panel::after {
            content: "";
            position: absolute;
            width: 600px;
            height: 600px;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 50%;
            bottom: -380px;
            left: -250px;
        }

        .brand-content {
            width: 100%;
            max-width: 580px;
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            width: 74px;
            height: 74px;
            border-radius: 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.16);

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 30px;

            box-shadow:
                0 15px 40px rgba(0,0,0,0.12);

            backdrop-filter: blur(10px);
        }

        .brand-logo i {
            font-size: 34px;
            color: #707070;
        }

        .organization-name {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.3px;
            opacity: 0.88;
            margin-bottom: 12px;
        }

        .brand-title {
            font-size: clamp(38px, 4vw, 58px);
            font-weight: 700;
            line-height: 1.06;
            letter-spacing: -1.8px;
            margin-bottom: 24px;
        }

        .brand-description {
            max-width: 500px;
            color: rgba(255,255,255,0.72);
            font-size: 17px;
            line-height: 1.8;
            margin-bottom: 42px;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 13px 15px;
            border-radius: 12px;

            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.07);

            color: rgba(255,255,255,0.84);
            font-size: 16px;
        }

        .feature-item i {
            color: #867144;
            font-size: 17px;
        }

        .brand-footer {
            position: absolute;
            bottom: 34px;
            left: 60px;
            right: 60px;

            display: flex;
            justify-content: space-between;

            color: rgba(255,255,255,0.45);
            font-size: 16px;
        }

        /* --------------------------------
           RIGHT LOGIN PANEL
        -------------------------------- */

        .login-panel {
            width: 52%;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 40px;
            background: #ffffff;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .mobile-brand {
            display: none;
        }

        .login-header {
            margin-bottom: 34px;
        }

        .login-header .eyebrow {
            color: var(--accent);
            text-transform: uppercase;
            font-weight: 700;
            font-size: 16px;
            letter-spacing: 1.8px;
            margin-bottom: 11px;
        }

        .login-header h1 {
            font-size: 34px;
            font-weight: 700;
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        .login-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 17px;
            line-height: 1.7;
        }

        .form-label {
            font-size: 16px;
            font-weight: 600;
            color: #34403b;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper > i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c7570;
            font-size: 18px;
            z-index: 2;
        }

        .form-control {
            height: 54px;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding-left: 47px;
            padding-right: 47px;

            font-size: 17px;

            box-shadow: none !important;
            transition: all 0.2s ease;
        }

        .form-control:hover {
            border-color: #6d7270;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(23,63,53,0.08) !important;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);

            border: none;
            background: transparent;
            color: #6c7570;
            padding: 0;

            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-top: 15px;
            margin-bottom: 25px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-left: 0;
        }

        .form-check-input {
            margin: 0;
            width: 17px;
            height: 17px;
            border-color: #6e7371;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            color: #68746e;
            font-size: 16px;
        }

        .forgot-link {
            color: var(--primary);
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            height: 54px;

            border: none;
            border-radius: 13px;

            background: var(--primary);
            color: white;

            font-size: 17px;
            font-weight: 650;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            transition: all 0.2s ease;
            box-shadow: 0 8px 22px rgba(23,63,53,0.14);
        }

        .login-btn:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(23,63,53,0.18);
        }

        .security-note {
            margin-top: 24px;

            display: flex;
            align-items: flex-start;
            gap: 11px;

            padding: 14px 15px;

            border-radius: 12px;
            background: #f7f9f8;
            border: 1px solid #e7ece9;
        }

        .security-note i {
            color: var(--primary);
            font-size: 18px;
            margin-top: 1px;
        }

        .security-note p {
            margin: 0;
            color: #6c7772;
            font-size: 16px;
            line-height: 1.6;
        }

        .login-bottom {
            margin-top: 34px;
            text-align: center;

            color: #6f7572;
            font-size: 16px;
        }
        .logo-wrapper {
            width: 120px;
            height: 120px;
            border-radius: 28px;

            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.18);

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 12px;
            margin-bottom: 24px;

            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.16);
            backdrop-filter: blur(12px);
        }

        .organization-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50px;
        }

        .organization-name {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 3px;
        }

        .organization-name-en {
            color: rgba(255, 255, 255, 0.62);
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .brand-divider {
            width: 48px;
            height: 2px;
            background: #c89b3c;
            margin: 22px 0;
        }

        .brand-title {
            font-size: clamp(34px, 3.4vw, 50px);
            line-height: 1.08;
            letter-spacing: -1.5px;
            font-weight: 700;
            margin-bottom: 22px;
        }

        .brand-description {
            max-width: 520px;
            color: rgba(255,255,255,0.70);
            font-size: 17px;
            line-height: 1.75;
            margin-bottom: 25px;
        }

        .mission-box {
            display: flex;
            gap: 13px;

            padding: 16px;

            border-radius: 15px;

            background: rgba(255,255,255,0.065);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .mission-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;

            border-radius: 11px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(200,155,60,0.16);
            color: #867144;
        }

        .mission-label {
            display: block;

            font-size: 16px;
            font-weight: 700;
            letter-spacing: 1.4px;

            color: #867144;

            margin-bottom: 5px;
        }

        .mission-box p {
            margin: 0;

            color: rgba(255,255,255,0.67);

            font-size: 16px;
            line-height: 1.65;
        }

        .principles {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;

            margin-top: 18px;
        }

        .principles span {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 7px 10px;

            border-radius: 30px;

            background: rgba(255,255,255,0.055);
            border: 1px solid rgba(255,255,255,0.07);

            color: rgba(255,255,255,0.72);

            font-size: 16px;
        }

        .principles i {
            color: #867144;
        }

        /* --------------------------------
           RESPONSIVE
        -------------------------------- */

        @media (max-width: 992px) {

            .brand-panel {
                width: 42%;
                padding: 40px;
            }

            .login-panel {
                width: 58%;
                padding: 30px;
            }

            .brand-title {
                font-size: 40px;
            }

            .feature-list {
                grid-template-columns: 1fr;
            }

            .brand-footer {
                left: 40px;
                right: 40px;
            }
        }

        @media (max-width: 768px) {

            .login-wrapper {
                display: block;
            }

            .brand-panel {
                display: none;
            }

            .login-panel {
                width: 100%;
                min-height: 100vh;
                padding: 26px 20px;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 13px;
                margin-bottom: 48px;
            }

            .mobile-logo {
                width: 48px;
                height: 48px;

                border-radius: 13px;

                background: var(--primary);
                color: white;

                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-logo i {
                font-size: 22px;
            }

            .mobile-brand-text strong {
                display: block;
                color: var(--text-dark);
                font-size: 16px;
            }

            .mobile-brand-text span {
                display: block;
                color: var(--text-muted);
                font-size: 16px;
                margin-top: 2px;
            }

            .login-container {
                max-width: 500px;
            }

            .login-header h1 {
                font-size: 30px;
            }
        }

        @media (max-width: 420px) {

            .login-panel {
                padding: 22px 16px;
            }

            .login-header {
                margin-bottom: 28px;
            }

            .login-header h1 {
                font-size: 27px;
            }

            .remember-row {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <!-- =========================================
         BRANDING PANEL
    ========================================== -->

        <section class="brand-panel">

        <div class="brand-content">

            <!-- Organization Logo -->
            <div class="logo-wrapper">
                <img
                    src="{{ asset('images/falah-logo.jpg') }}"
                    alt="Tanzeem Falah Naujawanan Kasso Logo"
                    class="organization-logo"
                >
            </div>

            <!-- Organization Name -->
            <div class="organization-name">
                تنظیم فلاح نوجوانان کسو
            </div>

            <div class="organization-name-en">
                Tanzeem Falah Naujawanan Kasso
            </div>

            <div class="brand-divider"></div>

            <!-- Main Message -->
            <h2 class="brand-title">
                Serving Kasso.<br>
                Empowering People.<br>
                Building Together.
            </h2>

            <p class="brand-description">
                Tanzeem Falah Naujawanan Kasso is a community-based organization
                created to bring people together, identify genuine needs, use
                resources responsibly, and develop practical and sustainable
                solutions for the welfare and development of Kasso.
            </p>

            <!-- Mission -->
            <div class="mission-box">

                <div class="mission-icon">
                    <i class="bi bi-bullseye"></i>
                </div>

                <div>
                    <span class="mission-label">
                        OUR OBJECTIVE
                    </span>

                    <p>
                        To improve the social and economic well-being of the
                        people of Kasso through collective effort, responsible
                        use of resources, welfare activities, community
                        development, and sustainable livelihood opportunities.
                    </p>
                </div>

            </div>

            <!-- Core Principles -->
            <div class="principles">

                <span>
                    <i class="bi bi-check2-circle"></i>
                    Service
                </span>

                <span>
                    <i class="bi bi-check2-circle"></i>
                    Unity
                </span>

                <span>
                    <i class="bi bi-check2-circle"></i>
                    Consultation
                </span>

                <span>
                    <i class="bi bi-check2-circle"></i>
                    Transparency
                </span>

                <span>
                    <i class="bi bi-check2-circle"></i>
                    Trust
                </span>

                <span>
                    <i class="bi bi-check2-circle"></i>
                    Accountability
                </span>

            </div>

        </div>

    <div class="brand-footer">

        <span>
            Community • Welfare • Development
        </span>

        <span>
            Secure Organization Portal
        </span>

    </div>

</section>

    <!-- =========================================
         LOGIN PANEL
    ========================================== -->

    <main class="login-panel">

        <div class="login-container">

            <!-- Mobile branding -->

            <div class="mobile-brand">

                <div class="mobile-logo">
                    <i class="bi bi-buildings"></i>
                </div>

                <div class="mobile-brand-text">
                    <strong>تنظیم فلاح نوجوانان کسو</strong>
                    <span>Foundation Management System</span>
                </div>

            </div>


            <!-- Login heading -->

            <div class="login-header">

                <div class="eyebrow">
                    Secure Organization Portal
                </div>

                <h1>
                    Welcome back
                </h1>

                <p>
                    Sign in to access your organization's management dashboard.
                </p>

            </div>


            <!-- Login form -->

            <form method="POST" action="{{ route('login') }}">

                @csrf

                <!-- Email -->

                <div class="mb-3">

                    <label for="email" class="form-label">
                        Email address
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="name@organization.org"
                            autocomplete="email"
                            required
                            autofocus
                        >

                    </div>

                    @error('email')
                        <div class="text-danger mt-2 small">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <!-- Password -->

                <div class="mb-3">

                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                    @error('password')
                        <div class="text-danger mt-2 small">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <!-- Remember / Forgot -->

                <div class="remember-row">

                    @if (Route::has('password.request'))

                        <a
                            href="{{ route('password.request') }}"
                            class="forgot-link"
                        >
                            Forgot password?
                        </a>

                    @endif

                </div>


                <!-- Login -->

                <button
                    type="submit"
                    class="login-btn"
                >
                    <span>Sign in to dashboard</span>
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>


            <!-- Security note -->

            <div class="security-note">

                <i class="bi bi-shield-lock"></i>

                <p>
                    Your account access is protected by role-based permissions.
                    Financial and confidential organization information is only
                    available to authorized users.
                </p>

            </div>


            <!-- Footer -->

            <div class="login-bottom">
                © {{ date('Y') }} تنظیم فلاح نوجوانان کسو ·
                Foundation Management System
            </div>

        </div>

    </main>

</div>


<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function () {

        const isPassword =
            passwordInput.getAttribute('type') === 'password';

        passwordInput.setAttribute(
            'type',
            isPassword ? 'text' : 'password'
        );

        this.innerHTML = isPassword
            ? '<i class="bi bi-eye-slash"></i>'
            : '<i class="bi bi-eye"></i>';

    });
</script>

</body>
</html>