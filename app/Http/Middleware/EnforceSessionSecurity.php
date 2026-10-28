<?php

namespace App\Http\Middleware;

use App\Domain\Security\Services\AccountSecurity;
use App\Domain\Users\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceSessionSecurity
{
    public function handle(Request $request, Closure $next)
    {
        $security = app(AccountSecurity::class);
        if ($security->installed() && ($user = $request->user())) {
            $fresh = User::withTrashed()->find($user->id);
            if ($fresh) {
                Auth::guard('web')->setUser($fresh);
                $user = $fresh;
            }
            $session = $request->session();
            if (! $fresh || ! $security->valid($user) || ! $session->get('security.complete') || (int) $session->get('security.version') !== (int) $user->session_version ||
               (int) $session->get('security.started', 0) < time() - 28800 || (int) $session->get('security.seen', 0) < time() - 1800) {
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Your session ended. Please sign in again.']);
            }
            $session->put('security.seen', time());
            if ($user->must_change_password && ! $request->routeIs('security.index', 'security.password', 'logout')) {
                return redirect()->route('security.index');
            }
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
        if ($request->user() || $request->routeIs('login', 'security.*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
