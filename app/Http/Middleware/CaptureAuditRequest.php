<?php

namespace App\Http\Middleware;

use App\Domain\Audit\Services\AuditRecorder;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CaptureAuditRequest
{
    public function handle(Request $r, Closure $next)
    {
        $r->attributes->set('audit_correlation', (string) Str::uuid());
        try {
            $response = $next($r);
        } catch (\Throwable $e) {
            if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
                $this->denied($r, 'access_denied');
            } elseif ($e instanceof ValidationException && $this->sensitive($r)) {
                $this->denied($r, 'sensitive_action_rejected');
            }
            throw $e;
        }
        if ($response->getStatusCode() === 403) {
            $this->denied($r, 'access_denied');
        } elseif ($this->sensitive($r) && ($response->getStatusCode() === 422 || ($response->isRedirection() && $r->hasSession() && $r->session()->has('errors')))) {
            $this->denied($r, 'sensitive_action_rejected');
        }

        return $response;
    }

    private function sensitive(Request $r): bool
    {
        return ! $r->isMethodSafe() && (bool) preg_match('/(pay|refund|void|approve|decid|submit|sign|disburs|repay|finaliz|clos|cancel|receive|invoice|return|amend|role|permission|security|documents.change)/i', $r->route()?->getName() ?? '');
    }

    private function denied(Request $r, string $action): void
    {
        if ($r->attributes->get('audit_denial_recorded')) {
            return;
        }
        $r->attributes->set('audit_denial_recorded', true);
        app(AuditRecorder::class)->security($action, ['outcome' => 'blocked', 'actor_id' => $r->user()?->id, 'actor_kind' => $r->user() ? 'user' : 'anonymous', 'summary' => 'Blocked action: '.($r->route()?->getName() ?? 'protected route')]);
    }
}
