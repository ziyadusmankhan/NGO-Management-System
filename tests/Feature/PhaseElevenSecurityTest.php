<?php

namespace Tests\Feature;

use App\Domain\Audit\Models\AuditEvent;
use App\Domain\Qard\Services\QardRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\CreatesWelfareFixtures;
use Tests\Support\ValidUpload;
use Tests\TestCase;

class PhaseElevenSecurityTest extends TestCase
{
    use CreatesWelfareFixtures,RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_password_login_is_rate_limited_and_remember_is_ignored(): void
    {
        $u = $this->staff([]);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        Cache::flush();
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password', 'remember' => 1])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
        $this->assertNull($u->fresh()->remember_token);
    }

    public function test_second_factor_is_required_and_recovery_code_is_single_use(): void
    {
        $u = $this->staff([]);
        app(EnableTwoFactorAuthentication::class)($u);
        $u->forceFill(['two_factor_confirmed_at' => now()])->save();
        $code = $u->recoveryCodes()[0];
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password'])->assertRedirect(route('security.challenge'));
        $this->assertGuest();
        $this->assertSame(0, AuditEvent::where('action', 'login')->count());
        $this->post(route('security.verify'), ['code' => $code])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
        $this->assertSame(1, AuditEvent::where('action', 'login')->count());
        $this->post('/logout');
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password']);
        $this->post(route('security.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->assertStringNotContainsString($code, DB::table('audit_events')->get()->toJson());
    }

    public function test_disabling_an_account_between_password_and_second_factor_blocks_access(): void
    {
        $u = $this->staff([]);
        app(EnableTwoFactorAuthentication::class)($u);
        $u->forceFill(['two_factor_confirmed_at' => now()])->save();
        $code = $u->recoveryCodes()[0];
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password']);
        $u->update(['status' => 'inactive']);
        $this->post(route('security.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_idle_absolute_and_revoked_sessions_are_rejected(): void
    {
        $u = $this->staff([]);
        foreach ([['security.seen' => time() - 1801], ['security.started' => time() - 28801], ['security.version' => 99]] as $state) {
            $this->actingAs($u)->withSession($state)->get('/')->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_sensitive_changes_require_recent_confirmation(): void
    {
        $u = $this->staff([]);
        $this->actingAs($u)->withSession(['security.confirmed' => 0])->post(route('security.two-factor', 'enable'))->assertSessionHasErrors('password');
        $this->assertNull($u->fresh()->two_factor_secret);
        $this->post(route('security.confirm.store'), ['password' => 'test-password'])->assertRedirect(route('security.index'));
        $this->post(route('security.two-factor', 'enable'))->assertRedirect(route('security.index'));
        $this->assertNotNull($u->fresh()->two_factor_secret);
        $this->assertNull($u->fresh()->two_factor_confirmed_at);
        $this->get(route('security.index'))->assertOk()->assertSee('Scan this QR code');
    }

    public function test_admin_reset_is_once_displayed_preserves_two_factor_and_forces_new_password(): void
    {
        $admin = $this->staff(['users.password-reset']);
        $u = $this->staff([]);
        app(EnableTwoFactorAuthentication::class)($u);
        $u->forceFill(['two_factor_confirmed_at' => now()])->save();
        $secret = $u->two_factor_secret;
        $response = $this->actingAs($admin)->post(route('security.reset', $u))->assertOk();
        $temporary = $response->viewData('temporary');
        $this->assertTrue(Hash::check($temporary, $u->fresh()->password));
        $this->assertSame($secret, $u->fresh()->two_factor_secret);
        $this->assertTrue($u->fresh()->must_change_password);
        $this->assertSame(2, $u->fresh()->session_version);
        $this->assertStringNotContainsString($temporary, DB::table('audit_events')->get()->toJson());
        $this->actingAs($u->fresh())->get('/')->assertRedirect(route('security.index'));
        $this->post(route('security.password'), ['current_password' => $temporary, 'password' => 'new-safe-password', 'password_confirmation' => 'new-safe-password'])->assertRedirect(route('security.index'));
        $this->assertFalse($u->fresh()->must_change_password);
        $this->assertNull($u->fresh()->temporary_password_expires_at);
    }

    public function test_short_password_and_expired_temporary_password_fail(): void
    {
        $u = $this->staff([]);
        $this->actingAs($u)->post(route('security.password'), ['current_password' => 'test-password', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->post('/logout');
        $u->forceFill(['temporary_password_expires_at' => now()->subMinute(), 'must_change_password' => true])->save();
        $this->post('/login', ['email' => $u->email, 'password' => 'test-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_other_session_revocation_keeps_this_session_and_audits(): void
    {
        $u = $this->staff([]);
        $this->actingAs($u)->post(route('security.sessions'))->assertRedirect();
        $this->assertSame(2, $u->fresh()->session_version);
        $this->get(route('dashboard'))->assertOk();
        $this->assertTrue(AuditEvent::where('action', 'other_sessions_revoked')->exists());
    }

    public function test_server_recovery_requires_reason_revokes_sessions_and_retains_password(): void
    {
        $u = $this->staff([]);
        app(EnableTwoFactorAuthentication::class)($u);
        $hash = $u->password;
        $this->artisan('security:recover-two-factor', ['email' => $u->email])->assertExitCode(1);
        $this->artisan('security:recover-two-factor', ['email' => $u->email, '--reason' => 'Verified identity, recovery ticket SEC-10'])->assertExitCode(0);
        $this->assertNull($u->fresh()->two_factor_secret);
        $this->assertSame($hash, $u->fresh()->password);
        $this->assertSame(2, $u->fresh()->session_version);
    }

    public function test_authenticator_code_confirms_enrollment_and_completes_login(): void
    {
        $user = $this->staff([]);
        $this->actingAs($user)->post(route('security.two-factor', 'enable'))->assertRedirect();
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);
        $code = (new Google2FA)->getCurrentOtp($secret);
        $this->post(route('security.two-factor', 'confirm'), ['code' => $code])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('recovery_codes');
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->post('/logout');
        // Fresh challenge verifies a different enrolled account's actual TOTP, without a mock.
        $other = $this->staff([]);
        app(EnableTwoFactorAuthentication::class)($other);
        $other->forceFill(['two_factor_confirmed_at' => now()])->save();
        $secret = Fortify::currentEncrypter()->decrypt($other->two_factor_secret);
        $code = (new Google2FA)->getCurrentOtp($secret);
        $this->post('/login', ['email' => $other->email, 'password' => 'test-password'])->assertRedirect(route('security.challenge'));
        $this->post(route('security.verify'), ['code' => $code])->assertRedirect('/')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($other);
    }

    public function test_new_qard_signatures_require_two_distinct_witness_names(): void
    {
        $valid = ['borrower_name' => 'Borrower', 'foundation_name' => 'Foundation', 'borrower_file' => ValidUpload::pdf(), 'foundation_file' => ValidUpload::pdf()];
        foreach ([[], ['One'], [' Same Name ', 'same  name']] as $witnesses) {
            try {
                QardRules::validate('sign', $valid + ['witnesses' => $witnesses]);
                $this->fail('Invalid witnesses accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $data = QardRules::validate('sign', $valid + ['witnesses' => ['First person', 'Second person']]);
        $this->assertCount(2, $data['witnesses']);
    }
}
