@extends('auth.security-layout')
@section('content')
<h1>Account Security</h1><p>{{ $user->name }} · {{ $user->email }}</p>
@if($user->must_change_password)<p class="error">Choose a new password before using the application. Your temporary password expires after 24 hours.</p>@else<a href="{{ url('/') }}">Back to dashboard</a> · <a href="{{ route('security.confirm') }}">Confirm password for sensitive changes</a>@endif
<section><h2>Change password</h2><form method="post" action="{{ route('security.password') }}">@csrf
<label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
<label>New password (at least 12 characters)<input type="password" name="password" autocomplete="new-password" minlength="12" required></label>
<label>Repeat new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required></label><button>Change password</button></form></section>
@if(!$user->must_change_password)
<section><h2>Authenticator app</h2><p>Optional. Use an authenticator app to add a second sign-in step. Confirm your password before changing these settings.</p>
@if(!$user->two_factor_secret)
<form method="post" action="{{ route('security.two-factor','enable') }}">@csrf<button>Set up authenticator</button></form>
@elseif(!$user->two_factor_confirmed_at)
@if((int)session('security.confirmed') >= time()-900)
<p>Scan this QR code with your authenticator, then enter its code to finish enrollment.</p>{!! $user->twoFactorQrCodeSvg() !!}
<form method="post" action="{{ route('security.two-factor','confirm') }}">@csrf<label>Six-digit code<input name="code" inputmode="numeric" autocomplete="one-time-code" required></label><button>Confirm enrollment</button></form>
@else<p><a href="{{ route('security.confirm') }}">Confirm your password to continue setup.</a></p>@endif
@else<p>Two-factor authentication is enabled.</p>
<form method="post" action="{{ route('security.two-factor','regenerate') }}">@csrf<button>Replace recovery codes</button></form>
@endif
@if($user->two_factor_secret)<form method="post" action="{{ route('security.two-factor','disable') }}" style="margin-top:12px">@csrf<button>{{ $user->two_factor_confirmed_at ? 'Disable authenticator' : 'Cancel setup' }}</button></form>@endif
@if(session('recovery_codes'))<div class="success"><p>Save these codes privately now. Each works once. They will not be displayed again.</p>@foreach(session('recovery_codes') as $code)<div><code>{{ $code }}</code></div>@endforeach</div>@endif
</section><section><h2>Sessions</h2><p>Sessions end after 30 minutes without activity, or eight hours after sign-in.</p><form method="post" action="{{ route('security.sessions') }}">@csrf<button>Sign out other sessions</button></form></section>
@endif
<section><form method="post" action="{{ route('logout') }}">@csrf<button>Log out</button></form></section>
@endsection