# Phase 1: signed agreement to welfare assistance

## Implemented workflow

All new assistance uses an approved application with a signed agreement. The existing **Create Assistance** page selects an eligible application, previews its approved details, and submits to the existing store endpoint. The agreement page links to the same flow and service.

Creation checks final approval metadata, beneficiary verification and approval, an active authorized fund, both distinct signatories, verifier IDs, signature timestamps, completion timestamp, and the existence of both evidence files on the private disk. Creation records **pending** assistance and an audit entry. It creates no payment or ledger transaction.

The existing unique index on `welfare_assistances.application_id` remains the database guarantee. The service also checks archived assistance. Creation locks the application and source records inside one transaction; SQLite reserves its writer before reading. Competing attempts produce one record and a validation error for the duplicate.

| Assistance field | Authoritative source |
| --- | --- |
| `application_id` | Selected application |
| `beneficiary_id`, `fund_id` | Application |
| `amount` | Signed agreement's `approved_amount` |
| `purpose_notes` | Application's `purpose_details` |
| `assistance_type` | Application's `request_type`, preserved up to 60 characters |
| Frequency and start/end dates | Application |
| Supporting-document indicator | Application |

Clients cannot submit or edit these inherited fields. Only the next review date, internal notes, and notes are editable on linked assistance. Creation copies decimal strings; standard agreement/assistance model reads normalize SQLite numeric values to two-decimal text before PHP conversion. The boundary amount `9999999999999.99` is covered by tests. Broader financial arithmetic remains scheduled for Phase 5.

Existing manual assistance with no application remains visible and editable under its existing permissions. It cannot be assigned an application through an edit. New manual creation is closed.

## Status and audit decisions

| From | To | Permission |
| --- | --- | --- |
| No record | Pending | `welfare.assistance.manage` |
| Pending | Approved | `welfare.assistance.approve` |
| Pending | Rejected | `welfare.assistance.approve` |
| Rejected | Pending, through explicit resubmission | `welfare.assistance.manage` |
| Approved | Completed, through the existing full one-time payment workflow | Existing payment permission |

All HTTP endpoints also require `welfare.assistance.view`. Approval and resubmission recheck current source eligibility and that the copied terms still match. Rejection remains possible when the source is no longer eligible. Editing does not resubmit or approve a record.

Each new transition stores the old/new status, actor, timestamp and optional reason in `welfare_assistance_status_changes`, in the same transaction. Rejection no longer populates approval metadata. The existing payment completion branch now records its transition too. Historical audit entries are not fabricated for legacy records.

## Migrations to run

Run from the project directory after deploying the code:

```sh
php artisan migrate
```

The three new migrations:

1. Add indexed `welfare_applications.is_test`, default false. Backfill existing **APP-00005** and **APP-00010** once, including archived rows. All runtime agreement/assistance guards and warning views use the flag; normal application forms cannot set it.
2. Add assistance status history with assistance/user foreign keys and a history lookup index.
3. Expand `assistance_type` from 50 to 60 characters to match application request types.

These migrations were exercised on isolated test databases, including rollback and reapplication. They have not been applied to the workspace's existing database. Existing assistance and payments are retained. Rollback of the type expansion refuses to truncate types over 50 characters; resolve those values before rolling it back. Rolling back the history table removes its audit data, so retain it if you need the history.

## Automated checks

The repository now has PHPUnit configuration, a Laravel test base, fixtures and feature tests:

```sh
php vendor/bin/phpunit
```

Tests enforce an in-memory SQLite connection before database refresh. The simultaneous-creation test uses a separate, uniquely named temporary SQLite database and two PHP processes. Private evidence is faked or stored only in that temporary test directory. No tests reset or seed the application's existing database.

Coverage includes creation, exact amount copying, ineligible applications, unsigned/incomplete agreements, missing evidence, fund and beneficiary eligibility, reserved application backfill, protected-field tampering, legacy edits, permission failures, inactive accounts, duplicate and archived duplicate protection, database uniqueness, concurrent creation, status transitions, transactional audit rollback, page rendering, links, payment completion audit, and migration rollback/reapplication.

On this Windows machine, the normal Herd PHP process stalled during the test run. The suite can run with only the required extensions, using this PowerShell command:

```powershell
$phaseOnePhp = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
$phaseOneExtensions = Join-Path (Split-Path $phaseOnePhp) 'ext'
& $phaseOnePhp -n -d "extension_dir=$phaseOneExtensions" -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo -d extension=openssl vendor/bin/phpunit
```

Automated runs covered PHP 8.4 / SQLite. MySQL concurrency and browser appearance were not manually exercised in this phase.

Final verification: **63 tests passed, 419 assertions**. PHP syntax checks passed for 21 affected files, the assistance route list resolved correctly, and `git diff --check` passed.

## Manual verification checklist

1. Run the migrations. Log in with assistance view/manage and agreement view permissions.
2. Open an approved, signed application with an active fund and available signature evidence. From its agreement, select **Create assistance**, review the inherited fields, and submit. Confirm pending status, the correct amount/fund/purpose, a creation history entry, and no new payment or ledger entry.
3. Reopen the agreement and application. Confirm they link to the same assistance. Retry creation in a second tab; confirm a validation error and no second record.
4. Confirm draft/prepared/cancelled agreements, missing evidence, inactive funds and flagged APP-00005/APP-00010 cannot create assistance.
5. Edit linked assistance. Only review date and administrative notes should be editable. Existing manual assistance should still show its existing edit fields and a legacy label.
6. With approval permission, reject a pending assistance. Confirm rejection history and no approval metadata. Editing it must leave it rejected. Resubmit explicitly, then approve. Check actor/time entries and that repeat approval is refused.
7. With view-only permission, confirm create/edit/approve/reject/resubmit actions are unavailable and direct requests are forbidden. Without agreement view permission, the agreement link should be hidden and the agreement route forbidden.

## Assumptions and phase boundary

- Assistance approval remains separate from final application approval and agreement signing.
- The application's fund is authoritative. If it is missing or inactive, resolve the source record before creation; the creation form cannot substitute a fund.
- An archived assistance still occupies its application's unique slot. Restoration/correction is an administrative task; there is no replacement-record workaround.
- Existing staff with both management and approval permissions may perform both actions, matching the current permission model.
- The test flag classifies the existing reserved references. Administrative management of test records remains part of the later cleanup phase.
- No unresolved Phase 1 business-rule decision is required. Payment integration, restricted-fund controls, void/reopen behavior and broader money cleanup remain for their scheduled phases.

Stop after Phase 1 until the next phase is authorized.
