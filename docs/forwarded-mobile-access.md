# Mobile access through a forwarded development port

Laravel must receive the external HTTPS scheme and hostname from a trusted proxy. Setting APP_URL alone does not override the origin of a normal incoming HTTP request.

The local VS Code forwarding agent connects to the development server through loopback. The local environment therefore uses:

```dotenv
TRUSTED_PROXIES=127.0.0.1,::1
SESSION_SECURE_COOKIE=null
SESSION_DOMAIN=
```

TrustConfiguredProxies reads the allowlist from loaded configuration when the request is handled. Reading env() in the application-builder middleware callback was too early: the environment had not yet loaded, so the list was empty and the tunnel's HTTPS scheme was ignored. Wildcard trust is still rejected.

The null secure-cookie setting lets Symfony choose Secure for a recognized HTTPS request and allows direct local HTTP development. Host-only cookies (empty SESSION_DOMAIN) keep the local and tunnel sessions separate. HTTPS-only production should explicitly set SESSION_SECURE_COOKIE=true and configure the actual production proxy addresses instead of blindly copying development settings.

## Trying it on a phone

1. Keep MySQL and the Laravel server running; forward port 8000.
2. Open the complete forwarded **HTTPS** URL in the phone browser, followed by `/login`. Do not use 127.0.0.1 on the phone.
3. Reload the page. If it still displays old content or loops, clear site data/cookies for that forwarded hostname or try a private tab, then sign in again.
4. If the forwarding tool changes the hostname, use the new address and update APP_URL for CLI-generated links. Keep SESSION_DOMAIN empty.
5. After changing environment settings, run `php artisan config:clear`. Restart `php artisan serve` if the forwarding/server process has retained old environment settings.

Do not make the forwarded port public merely to bypass a forwarding-provider access prompt. If the provider requires sign-in or permission to view the port, complete its normal access flow on the phone.

## Verification

A live request to port 8000 with the tunnel host and HTTPS forwarding headers now returns HTTPS application assets and login action URLs. CSS and JavaScript endpoints return 200 with the expected content types. A direct local HTTP request remains supported. Automated checks cover forwarded assets/form URLs, secure host-only cookies, successful login and dashboard access, and rejection of forwarded-origin headers from an unknown proxy. The forwarding and account-security suites pass 15 tests / 118 assertions. External tunnel access from the diagnostic environment could not be confirmed; phone/provider access still depends on the current forwarded URL and provider permissions.
