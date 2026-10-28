<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AccountSecurity $security): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $security->limit($request, 'password', $data['email']);
        $user = User::where('email', $data['email'])->first();
        $valid = $user && Hash::check($data['password'], $user->password) && $security->valid($user);
        if (! $valid) {
            // Perform a hash check even for unknown accounts to reduce account enumeration by timing.
            if (! $user) {
                Hash::check($data['password'], '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
            }
            event(new Failed('web', null, ['email' => $data['email']]));

            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }
        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->regenerate();
            $request->session()->put('security.pending', ['id' => $user->id, 'version' => (int) $user->session_version, 'until' => time() + 300]);

            return redirect()->route('security.challenge');
        }
        $security->login($request, $user);

        return $user->must_change_password ? redirect()->route('security.index') : redirect()->intended('/');
    }

    public function challenge(): View
    {
        return view('auth.challenge');
    }

    public function verify(Request $request, AccountSecurity $security): RedirectResponse
    {
        $data = $request->validate(['code' => 'required|string|max:100']);
        $pending = $request->session()->get('security.pending', []);
        $security->limit($request, 'two-factor', (string) ($pending['id'] ?? 'unknown'));
        $user = User::find($pending['id'] ?? 0);
        if (! $user || ($pending['until'] ?? 0) < time() || ($pending['version'] ?? 0) !== (int) $user->session_version || ! $security->secondFactor($user, $data['code'], (int) ($pending['version'] ?? 0))) {
            app(AuditRecorder::class)->security('two_factor_failed', ['actor_id' => null, 'actor_kind' => 'anonymous', 'outcome' => 'blocked']);

            return back()->withErrors(['code' => 'The code is invalid or this sign-in has expired.']);
        }
        $security->login($request, $user);

        return $user->must_change_password ? redirect()->route('security.index') : redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
