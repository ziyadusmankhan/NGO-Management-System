# Phase 10 — General Audit Trail

The Audit Trail joins retained module history and new capture into a sanitized, append-only central viewer. It is not the Phase 12 general activity dashboard and does not implement unfinished modules.

## Implementation

- One additive `audit_events` table contains stable event identifiers, actor/source references, UTC occurrence/capture times, outcomes, sanitized changes, request correlation and unique historical source keys. The interface displays Asia/Karachi time.
- The list supports actor, module, action, outcome, record-reference, date and historical/new filters, 30-row server pagination and matching summary counts. Details and exports share the sanitized stored data.
- Print output is browser Save as PDF; XLSX is a real spreadsheet with explicit string cells to prevent formula injection. Exports cap at 10,000 matching events and select a fixed upper event ID before recording their own export event.
- `audit.view` opens the organization-wide sanitized viewer; `audit.export` is separate. Administrator and Auditor / Reviewer receive both. The one-time upgrade removes obsolete Viewer/General Secretary audit defaults. Later explicit grants survive rerunning AuditRoleSeeder.
- Source links do not grant source-record or document access. There is no evidence download through Audit Trail.

## Capture and transaction boundaries

Existing Fund, Qard, Business, Project and Procurement event writers feed the shared recorder. Welfare reviews/status records and retained payments are included. Missing administrative and Welfare entity changes are captured through a transactional model concern. User roles, role permissions and committee memberships have transactional relation/table adapters, including raw membership operations used by existing controllers.

Captured business events use the same database connection/transaction as the change. An audit write failure raises an error and rolls back that change. Once the Phase 10 migration has been installed, missing audit storage also blocks business changes; it cannot silently disable capture. The main financial service supplies actual ledger references for donation/expense/Welfare postings; Qard/Business/Project/Procurement events already identify their financial movement. Procurement-linked parent financial events are represented once centrally through Procurement. Original domain histories are retained.

Role and membership adapters compare before/after state and avoid logging no-ops. Last-active writes, routine reads, generated reminders and ordinary profile form validation are excluded. Financial retries use their existing idempotency controls; historical events additionally have unique source keys.

Login, logout and failed-login listeners capture security activity. Request middleware captures authorization denials and rejected sensitive HTTP submissions after inner business transactions have unwound. It records route identity, not raw request payloads. Security logging failures must not change authentication behavior or prevent logout: a sanitized operational error and private failure marker support investigation through `audit:health`.

Console reconciliation completion is identified as a system operation. Direct database edits and uninstrumented third-party maintenance are not claimed as audited actions. Application append-only guards do not provide tamper-proof protection against database administrators.

## Privacy

An explicit recursive field allowlist runs before storage. Passwords/hashes, tokens, cookies, uploaded content, paths, identification/bank details and clinical/household narratives are excluded. Account password changes retain only a boolean flag. Changes to excluded profile fields can retain their field names without their values.

Actor names and operational business references remain available. Text is bounded and common identification, email and account-number patterns are redacted. Free-form text should still be written as an operational reason without personal narratives. Login attempts are masked and unauthenticated attempts never claim the target account as the actor. The captured network address is the server-observed peer, not an unverified forwarded header; network/browser context is not identity proof.

## Historical import

`audit:import-history --dry-run` reads retained history without adding events. The real import commits in small resumable units and uses unique source keys. Original events remain untouched. Unknown actors, prior values, occurrence times and old request context are not reconstructed from today's profile. Imported identity labels reflect what is available at import time.

Sources include Fund changes, Qard/Business/Project/Procurement events, Business category history, Welfare application reviews, assistance reviews/status changes and retained Welfare payment/void evidence. Ordinary old account edits or login events without retained history cannot be invented. Running the import again adds only previously missing source events plus its own import-run administration event.

## Activation

Create and verify a private SQLite backup before applying the additive migration. Preserve existing business tables and user identity/password hashes. Only audit data and intended permission metadata are added or changed.

```text
php scripts/phase10-sqlite-upgrade.php backup
php artisan migrate --force
php artisan db:seed --class=AuditRoleSeeder --force
php artisan audit:import-history --dry-run
php artisan audit:import-history
php scripts/phase10-sqlite-upgrade.php verify
php artisan audit:health
php artisan view:clear
```

The backup helper refuses to overwrite its Phase 10 manifest. Do not reset the database, use demo seeders, or treat a later backup as the pre-upgrade snapshot. Audit rows have indefinite retention in this phase; there is no pruning task. Rollback after activation must preserve the audit evidence rather than dropping it casually.

See [the beginner testing guide](phase-10-audit-testing.md). Automated validation covers permissions, secret filtering, transactional failures, authentication/denials, import idempotency, financial replay, time boundaries, pagination, export limits and actual XLSX contents, alongside the existing financial regression suite. The live viewer, historical detail, record/history filters, printable report layout, login/logout and XLSX download were also checked in the local browser. Printer-specific pagination remains a manual check.

Next: Phase 11 Documents Library and Security Hardening; then Phase 12 Records Reports/dashboard/notifications, Phase 13 cleanup/integration, Phase 14 Tasks/Meetings and Phase 15 persistent Settings.

## Local rollout verification — 27 October 2026

- Verified backup: `storage/app/backups/before-phase10-activation-20261026-224536.sqlite`, with the private manifest retained.
- Applied the additive audit migration and the one-time permission upgrade.
- Imported all 13 retained source events; a repeated dry run reported 13 existing and zero new events.
- SQLite integrity and foreign-key checks passed. Compared 71 existing business/account tables against the backup: identities and password hashes for all nine users are unchanged. A normal `last_active_at` change from browsing is explicitly allowed; every other user field is compared.
- Audit health passed after activation and browser checks. The live filtered XLSX contained the expected three historical records plus its header, 13 columns and no formula cells.
- No database reset, engine change, demo records or private data were committed.

## Final validation

- Full regression: **336 tests, 2,607 assertions passed** on 27 October 2026 (PHP 8.4.24 / PHPUnit 11.5.56, isolated SQLite).
- The focused audit and Qard concurrency rerun passed 20 tests / 114 assertions. An earlier full run had a worker startup timeout; the subsequent complete run above passed all concurrency cases.
- All Blade templates compiled successfully; generated view caches were then cleared.
- Procurement fixtures use Asia/Karachi dates, matching the existing receipt/payment screens and preventing false failures around local midnight.

The existing local Phase 8 and Phase 9 commits and the Phase 10 implementation, testing guide and rollout helper are included in this delivery. Private backups, databases, environment settings, uploaded evidence and test artifacts remain outside Git.
