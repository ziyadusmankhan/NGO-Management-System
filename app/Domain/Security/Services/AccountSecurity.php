<?php

namespace App\Domain\Security\Services;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class AccountSecurity
{
    public function installed(): bool
    {
        if (Schema::hasColumn('users', 'session_version')) {
            return true;
        }
        if (Schema::hasTable('migrations') && DB::table('migrations')->where('migration', '2026_10_27_000001_create_document_library_and_account_security')->exists()) {
            throw new \RuntimeException('Required account security schema is unavailable.');
        }

        return false;
    }

    public function limit(Request $request, string $purpose, string $identity): void
    {
        $keys = ['security:'.$purpose.':'.hash('sha256', strtolower($identity).'|'.$request->ip()) => 5, 'security:address:'.hash('sha256', (string) $request->ip()) => 30];
        $blocked = false;
        foreach ($keys as $key => $max) {
            // Atomic increments also enforce the cap when requests arrive together.
            if (RateLimiter::hit($key, 60) > $max) {
                $blocked = true;
            }
        }
        if ($blocked) {
            app(AuditRecorder::class)->security('authentication_throttled', ['actor_id' => null, 'actor_kind' => 'anonymous', 'outcome' => 'blocked']);
            throw ValidationException::withMessages(['email' => 'Too many attempts. Wait one minute before trying again.']);
        }
    }

    public function valid(User $user): bool
    {
        return $user->isActive() && ! $user->trashed() && (! $user->temporary_password_expires_at || $user->temporary_password_expires_at->isFuture());
    }

    public function establish(Request $request, User $user, bool $newLogin = false): void
    {
        $request->session()->regenerate();
        $request->session()->put(['security.version' => (int) $user->session_version, 'security.started' => $newLogin ? time() : (int) $request->session()->get('security.started', time()), 'security.seen' => time(), 'security.complete' => true]);
        $request->session()->forget('security.pending');
    }

    public function login(Request $request, User $user): void
    {
        abort_unless($this->valid($user), 403);
        Auth::guard('web')->login($user, false);
        $this->establish($request, $user, true);
        $user->recordActivity(true);
    }

    public static function requireRecent(Request $request): void
    {
        if ((int) $request->session()->get('security.confirmed', 0) < time() - 900) {
            throw ValidationException::withMessages(['password' => 'Confirm your password on Account Security before this action, then return and submit again.']);
        }
    }

    public function confirm(Request $request): void
    {
        $this->limit($request, 'confirmation', (string) $request->user()->id);
        if (! Hash::check((string) $request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'The password is incorrect.']);
        }
        $request->session()->put('security.confirmed', time());
    }

    public function secondFactor(User $user, string $code, ?int $expectedVersion = null): bool
    {
        // Serialize consumption on the account, including SQLite writers, so a recovery code cannot be reused concurrently.
        return DB::transaction(function () use ($user, $code, $expectedVersion) {
            DB::table('users')->where('id', $user->id)->update(['session_version' => DB::raw('session_version')]);
            $user = User::whereKey($user->id)->lockForUpdate()->first();
            if (! $user || ! $this->valid($user) || ! $user->hasEnabledTwoFactorAuthentication() || ($expectedVersion !== null && (int) $user->session_version !== $expectedVersion)) {
                return false;
            }
            $codes = $user->recoveryCodes();
            foreach ($codes as $i => $recovery) {
                if (hash_equals($recovery, $code)) {
                    unset($codes[$i]);
                    $user->forceFill(['two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(array_values($codes)))])->save();
                    app(AuditRecorder::class)->security('two_factor_recovery_code_used', ['actor_id' => $user->id]);

                    return true;
                }
            }

            return app(TwoFactorAuthenticationProvider::class)->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
        }, 5);
    }

    public function revoke(User $user): void
    {
        $user->forceFill(['session_version' => (int) $user->session_version + 1, 'remember_token' => null])->save();
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    public function audit(User $user, string $action): void
    {
        app(AuditRecorder::class)->record('security', $action, ['subject_type' => User::class, 'subject_id' => $user->id, 'subject_reference' => 'User #'.$user->id]);
    }
}
