<?php

namespace App\Console\Commands;

use App\Domain\Audit\Services\AuditRecorder;
use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class RecoverTwoFactor extends Command
{
    protected $signature = 'security:recover-two-factor {email} {--reason= : Required recovery authorization reference}';

    protected $description = 'Audited server-side recovery for a verified account that has lost both its authenticator and recovery codes';

    public function handle(AccountSecurity $s): int
    {
        if (strlen(trim((string) $this->option('reason'))) < 10) {
            $this->error('Provide an explicit --reason of at least 10 characters.');

            return 1;
        }
        $u = User::where('email', $this->argument('email'))->firstOrFail();
        DB::transaction(function () use ($u, $s) {
            $u = User::lockForUpdate()->findOrFail($u->id);
            app(DisableTwoFactorAuthentication::class)($u);
            $s->revoke($u);
            app(AuditRecorder::class)->record('security', 'server_two_factor_recovery', ['subject_type' => User::class, 'subject_id' => $u->id, 'reason' => $this->option('reason'), 'actor_kind' => 'system']);
        });
        $this->info('Second factor recovered for user #'.$u->id.'. All sessions revoked; account password remains required.');

        return 0;
    }
}
