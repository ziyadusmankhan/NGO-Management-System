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

    <link rel="stylesheet" href="{{ asset('css/views/auth-login-1.css') }}">
<link rel="stylesheet" href="{{ asset('css/foundation-auth.css') }}"><script src="{{ asset('js/theme-init.js') }}"></script><link rel="stylesheet" href="{{ asset('css/foundation-theme.css') }}"></head>

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
