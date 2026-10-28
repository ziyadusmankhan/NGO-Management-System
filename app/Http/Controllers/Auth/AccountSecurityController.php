<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class AccountSecurityController
{
    public function index(Request $r)
    {
        return view('auth.security', ['user' => $r->user()]);
    }

    public function confirmation()
    {
        return view('auth.confirm');
    }

    public function confirm(Request $r, AccountSecurity $s)
    {
        $r->validate(['password' => 'required|string']);
        $s->confirm($r);

        return redirect()->route('security.index')->with('success', 'Password confirmed for 15 minutes. Return to your previous page to finish your action.');
    }

    public function password(Request $r, AccountSecurity $s)
    {
        $data = $r->validate(['current_password' => 'required|string', 'password' => 'required|string|min:12|max:200|confirmed']);
        $s->limit($r, 'password-change', (string) $r->user()->id);
        if (! Hash::check($data['current_password'], $r->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }
        DB::transaction(function () use ($r, $s, $data) {
            $user = User::lockForUpdate()->findOrFail($r->user()->id);
            $user->forceFill(['password' => $data['password'], 'must_change_password' => false, 'temporary_password_expires_at' => null])->save();
            $s->revoke($user);
            $s->audit($user, 'password_changed');
            $s->establish($r, $user);
        });

        return redirect()->route('security.index')->with('success', 'Password changed. Other sessions were signed out.');
    }

    public function sessions(Request $r, AccountSecurity $s)
    {
        AccountSecurity::requireRecent($r);
        DB::transaction(function () use ($r, $s) {
            $u = User::lockForUpdate()->findOrFail($r->user()->id);
            $s->revoke($u);
            $s->audit($u, 'other_sessions_revoked');
            $s->establish($r, $u);
        });

        return back()->with('success', 'Other sessions signed out.');
    }

    public function twoFactor(Request $r, AccountSecurity $s, string $action)
    {
        AccountSecurity::requireRecent($r);
        $s->limit($r, 'two-factor-management', (string) $r->user()->id);
        abort_unless(in_array($action, ['enable', 'confirm', 'disable', 'regenerate']), 404);
        DB::transaction(function () use ($r, $s, $action) {
            DB::table('users')->where('id', $r->user()->id)->update(['session_version' => DB::raw('session_version')]);
            $user = User::lockForUpdate()->findOrFail($r->user()->id);
            if ($action === 'enable') {
                abort_if($user->hasEnabledTwoFactorAuthentication(), 409);
                app(EnableTwoFactorAuthentication::class)($user);
            }
            if ($action === 'confirm') {
                abort_if($user->hasEnabledTwoFactorAuthentication(), 409);
                app(ConfirmTwoFactorAuthentication::class)($user, (string) $r->input('code'));
            }
            if ($action === 'disable') {
                app(DisableTwoFactorAuthentication::class)($user);
            }
            if ($action === 'regenerate') {
                abort_unless($user->hasEnabledTwoFactorAuthentication(), 409);
                app(GenerateNewRecoveryCodes::class)($user);
            }
            $s->audit($user, 'two_factor_'.$action);
            if (in_array($action, ['confirm', 'disable'])) {
                $s->revoke($user);
                $s->establish($r, $user);
            }
            if (in_array($action, ['confirm', 'regenerate'])) {
                $r->session()->flash('recovery_codes', $user->recoveryCodes());
            }
        }, 5);

        return redirect()->route('security.index')->with('success', 'Two-factor settings updated.');
    }

    public function reset(Request $r, AccountSecurity $s, User $user)
    {
        abort_unless($r->user()->hasPermission('users.password-reset'), 403);
        AccountSecurity::requireRecent($r);
        abort_if($r->user()->id === $user->id, 422, 'Use Change password for your own account.');
        $temporary = Str::password(24);
        DB::transaction(function () use ($user, $s, $temporary) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->forceFill(['password' => $temporary, 'must_change_password' => true, 'temporary_password_expires_at' => now()->addDay()])->save();
            $s->revoke($user);
            $s->audit($user, 'administrator_password_reset');
        });

        return response()->view('auth.temporary', ['temporary' => $temporary, 'account' => $user])->header('Cache-Control', 'private, no-store');
    }
}
