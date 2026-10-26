# Phase 9 — Procurement implementation notes

Phase 9 replaces the active Procurement placeholders with a purchasing workflow for Projects, Small Business supplier/assets and unrestricted office purchases. See [the beginner testing guide](phase-9-procurement-testing.md) for account setup and complete exercises.

## Delivered workflow

Draft → equivalent supplier quotations → Chief Coordinator → General Secretary → committee resolution with at least two active consents → issued purchase order → independent acceptance → invoice allocation → authorized payments → closure/cancellation and archive.

Requests retain revisions, original funds, supplier snapshots, item references, policy snapshots, documents and audit events. A submitted amendment freezes new payments. Rejection/withdrawal requires an explicit Resume action. Cancellation cannot be reversed by an old approval. Actual Project/office returns and refunds can be recorded after closure without reopening spending.

## Financial ownership

| Context | Authoritative posting | Fund commitment |
|---|---|---|
| Project | ProjectMovement and the existing ProjectPaymentService | Already included in the project's approved unpaid budget |
| Small Business | BusinessMovement and the existing BusinessPaymentService | Already included in the business funding commitment |
| Office | ProcurementMovement through TransactionService | Approved unpaid office order value |

Procurement links to parent movements instead of duplicating them. One payment/refund has one ledger entry. Approval, order issue, receipt, invoice and a credit note do not post cash. Office procurement payments appear in Finance Expenses; parent movements continue through existing financial reports. Reconciliation detects missing/mismatched entries and does not manufacture replacement procurement payments.

Quantities use integer thousandths; amounts use integer paisa. Brick Math performs multiplication before rounding. Invoice splits allocate cumulative rounded values, avoiding repeated-rounding drift. Returns allocate their invoice value proportionally. Inputs exceeding the supported financial range are rejected.

An accepted invoice balance limits payment. Parent limits, competing order reservations and actual fund cash are checked too. Parent budgets already account for procurement commitments and are not counted twice. Refunds credit the original fund only; Project refunds restore the same budget item. Payers must satisfy procurement and parent independence rules.

Transactions lock parent → order → fund, with SQLite's writer lock obtained before snapshot reads. Request versions reject stale forms; unique payment keys and payload/file hashes handle duplicate submissions. Unique supplier invoice references prevent duplicate billing. Posted movement edits are prohibited; authorized voids retain reasons and dependency history. Refund reversals also respect fund cash and outstanding Project reservations.

## New code and integration

- `app/Domain/Procurement`: models, rules, exact calculations, workflow, operations, financial service, central integration guard and reports/reminders.
- `app/Http/Controllers/Procurement`, `app/Http/Requests/Procurement`, `routes/procurement.php`: authenticated actions, scoped downloads, reports and saved procurement policy.
- `resources/views/operations/procurement`, `public/js/procurement-form.js`: live list/forms/detail, quotations, order print, delivery/invoice/return/payment forms, history, reports and suppliers/rules.
- `2026_10_26_000001_create_procurement_domain.php`: additive tables and legacy compatibility markers.
- `ProcurementRoleSeeder`: additive permissions and two new default roles; no accounts or password changes.
- `procurement:refresh-reminders`: in-app delivery/invoice/refund flags at 08:15 Asia/Karachi, provided Laravel's scheduler runs.
- Existing Project/Business services enforce procurement reservations, mandatory goods routing, parent closure/finalization and protected correction paths. Generic Expenses require a non-goods classification. TransactionService also checks procurement at the ledger boundary.
- Parent document downloads check Procurement permission for procurement-linked payment evidence, in addition to normal parent access. Private procurement files are scoped to their order; donor details are not added.

## Permissions

All below have the `procurement.` prefix:

`view`, `manage`, `suppliers`, `quotations`, `recommend`, `review`, `approve`, `issue`, `receive`, `invoices`, `pay`, `returns`, `refund`, `void`, `close`, `reports`, `policy`.

`manage` alone grants no approval or financial authority. Procurement Officer receives request/supplier/quotation/issue/invoice actions. Receiving Officer receives acceptance. Existing coordinator, secretary, decision committee, treasurer and auditor roles receive their respective actions. Administrator receives all permissions but remains subject to business independence. Linked records require `projects.view` or `business.view`; parent payment/refund/void permissions are also required by their authoritative services.

## Compatibility and deliberate boundaries

- Existing approved Project/Business budget rows receive a `legacy_direct_purchase` marker. New approvals and replacement revisions do not inherit it. Posted generic expenses receive a legacy marker; pending unclassified goods cannot post through generic Expenses.
- Ordinary labor/services, Welfare cash payments, Qard disbursements and Business cash releases retain their workflows.
- One original fund, parent and selected supplier per request; each parent budget item appears once per request. Multiple equivalent quantities belong on one line. Different suppliers require separate requests.
- Receipt-backed lines retain supplier, unit price, specification and budget mapping when amended. Other goods require a separate request. Invoice references remain reserved even after voiding; corrected supplier documents need distinct references.
- Returns and accepted supplier credit notes are recorded together by an authorized user with evidence. They do not imply cash received. Business supplier returns after acceptance/payment are deferred; rejected delivery quantities do not count as acceptance.
- No advances, inventory, staff advances, automated supplier contact, automatic payment, automatic replacement purchase or general Settings implementation.
- Print-to-PDF uses the browser; XLSX is a real spreadsheet export. Mixed item units are not meaningful aggregate quantities; use item-level order/receipt history for unit-specific checks.
- Database-engine migration is outside Phase 9. Concurrency verification uses SQLite; MySQL/PostgreSQL deployment needs separate engine testing.

## Local activation

The helper requires the application's local SQLite configuration. It writes private backups under ignored `storage/app/backups/` and refuses to overwrite its Phase 9 manifest.

```text
php scripts/phase9-sqlite-upgrade.php backup
php artisan migrate --force
php artisan db:seed --class=ProcurementRoleSeeder --force
php scripts/phase9-sqlite-upgrade.php verify
php artisan view:clear
php artisan procurement:refresh-reminders
```

Do not run the backup step again after activation and treat that as a pre-upgrade backup. Preserve the original file and manifest. Backup verification checks SQLite integrity and foreign keys, fingerprints every existing business table using its original columns, and checks user identities/password hashes remain identical. Intended permission additions, migration bookkeeping and transient cache/session tables are excluded from the data comparison. New domain tables must be empty except for the initial quotation policy of 2.

Do not use `migrate:fresh`, `db:wipe` or general demo seeders. Rollback after users enter procurement activity requires a planned data recovery/migration, not blindly dropping the new tables.

## Verification

Automated tests cover office/Project/Business integration, PKR 500 installment payments, unsigned Business blocking, quotations and policy exceptions, independent actors, accepted quantities, duplicate invoices, revisions, cancellation, private evidence including parent links, actual XLSX numbers, rounding, overdue flags, late refunds, void dependencies and migration compatibility.

Separate SQLite worker processes exercise identical-key submission, different-key competing payments, shared-fund cash contention, concurrent refunds, amendment-versus-payment and competing parent-budget order approvals. The complete prior Welfare/Qard/Business/Project/Finance suite is run together with these tests.

Rendered HTTP responses and Blade compilation are checked automatically. A desktop browser print-preview walkthrough is not part of that automated evidence; the manual guide includes inspecting the saved PDF and the visible forms.

### Verified local delivery — 26 October 2026

- Full suite: **320 tests, 2,522 assertions passed** using PHP 8.4 and isolated SQLite databases, including six multi-process concurrency scenarios. The existing financial-domain regression tests remain passing.
- Blade template compilation and the procurement reminder command succeeded.
- Saved and integrity-checked `storage/app/backups/before-phase9-activation-20261026-175942.sqlite`. The Phase 9 schema was already installed when this resumption's backup was taken; `migrate --force` therefore reported no pending migration. This particular backup is a continuation/activation checkpoint, **not a pre-schema rollback snapshot**.
- The additive Procurement role seeder completed. The subsequent comparison verified **71 existing tables unchanged**, **9 users with unchanged identity/password data**, no foreign-key violations, and **zero procurement requests**. No reset or sample data was created.
- Existing private backup and manifest files remain ignored by Git. Implementation is saved in local commits; publishing to GitHub is a separate action.

Next: **Phase 10 — General Audit Trail**, then 11 Documents/security, 12 Records Reports/dashboard/notifications, 13 cleanup/integration, 14 Tasks/Meetings and 15 persistent Settings. Each requires its own detailed plan.
