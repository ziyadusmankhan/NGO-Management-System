# Review fixes, 1 November 2026

- User-profile management no longer implicitly changes roles. Omitted roles retain assignments. A separate `users.assign-roles` permission is introduced additively, with Administrator access by default. Self role changes are denied. Delegated role assignment cannot grant/remove Administrator or roles containing permissions the actor lacks. Delegated managers cannot edit/archive administrators. Role-permission editors cannot edit their own role or Administrator, or alter permissions beyond their authority.
- Run `php artisan migrate --force` during the normal backed-up deployment to register the new permission. Grant it deliberately to a delegated manager only when needed. Existing profiles and password hashes are preserved.
- Field-verification creation locks the application, rechecks eligibility and active assignments, and retries deadlocks. A two-process regression exercises the real controller against an isolated file-backed database.
- On SQLite, creation reserves the writer before reading; row locks alone do not work on that engine. MySQL uses the parent row lock.
- Document access uses correlated SQL existence checks, including source evidence and procurement parent restrictions. Pagination/search/counts no longer hydrate the whole library or construct an ID list. Individual authorization uses the same query policy.
- Docker uses committed public assets without an npm build. Supervisor manages Apache and cron; the monitored scheduler wrapper reports missing/stale/failing runs. Configure a per-minute scheduled task on Alwaysdata separately, as described in README_DEPLOY.md.
- MySQL reads its CA path and enables certificate verification. Actual remote TLS negotiation must be verified on the deployment host.
- Playwright is declared and locked, with browser-install/preview-generation instructions and GitHub Actions checks. Composer's PHP requirement now matches the locked PHP 8.4.1 minimum.

No production migrations, account changes or deployment are performed by this local review.

Deployment references: [Laravel scheduling](https://laravel.com/framework/docs/13.x/scheduling), [Playwright CI](https://playwright.dev/docs/ci).

## Verification

- Final sequential suite: **477 tests, 4,037 assertions passed** using PHP 8.4.24 with `memory_limit=1G`.
- Clean npm install and Playwright Chromium installation completed. Browser check: **63 pages, 315 viewport checks**, no overflow, missing labels, tiny text, white surfaces in dark mode or JavaScript errors. Theme persistence, dropdown, mobile drawer and fund graph checks passed.
- Composer validation, shell syntax and diff whitespace checks passed.
- The report test now freezes its date within the report window rather than assuming the real date is still October 2026.
- Docker image execution/build remains unverified because the local Docker daemon is unavailable. TLS PDO configuration is tested, but the remote production handshake must still be checked on the hosting server.
