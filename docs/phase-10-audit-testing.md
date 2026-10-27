# Phase 10 — Audit Trail testing guide

An audit trail is the application's record book. It shows who changed something, when it happened and whether the action succeeded or was blocked.

Use your local testing application. You do not need to reset the database. The upgrade keeps existing users, passwords and financial records and imports only history already retained by the application.

## 1. Check who can open the record book

1. Log in as Administrator.
2. Open **Records → Audit Trail**. You should see real events or an honest empty result, never sample rows.
3. Open **Users & Roles** and create a separate test account with **Auditor / Reviewer**. Log out and log in with that account. It should see the Audit Trail and export buttons.
4. Try a Viewer and a General Secretary account. These roles no longer receive Audit Trail access by default. The sidebar link should be hidden; opening `/records/audit` directly should give Access denied.
5. If an administrator deliberately grants `audit.view` later, that grant should work. The permission upgrade does not remove deliberate later grants when rerun.
6. For a read-only audit account, grant `audit.view` without `audit.export`. It should read the list and details but cannot export.

Audit permission does not let someone open an unrelated source document. Source pages and private evidence keep their own permissions.

## 2. Follow a simple account change

1. As Administrator, create a clearly named test user. Do not change a real person's password for this exercise.
2. Change that test user's name, status and assigned role.
3. Open Audit Trail. Filter **Module = Accounts**, select your administrator as Actor, then Apply filters.
4. Open the account and role-change events. Check the actor, record ID, time and before/after values.
5. Change the test user's password. The audit should show **Password changed: Yes**, without the password or its hash.
6. Archive the test user. Its history should remain readable.
7. Open your own profile or refresh a page several times. Routine page views and last-active updates should not create a stream of audit changes.

If an event says a personal field changed without showing its contents, that is intentional. Audit data is filtered before it is saved.

## 3. Check committees and permissions

1. Create a test committee and assign an active test member.
2. Change the membership position, then end that membership.
3. Filter Audit Trail to **Organization**. Confirm the membership assignments and changes appear.
4. Change a test role's permissions in Users & Roles. Filter to **Accounts** and open **Permissions changed**.
5. Confirm the before/after lists show the permission slugs actually removed or added. Restore your intended role permissions afterward.

The audit records join-table changes such as memberships and role assignments, not just changes to a user's name.

## 4. Follow one financial payment

Use the Phase 9 guide to prepare an approved, accepted and invoiced **PKR 500** office purchase with separate requester, approvers, receiver and payer.

1. As Treasurer, record **PKR 10.25** through Procurement.
2. Open the audit as Administrator or Auditor / Reviewer. Filter **Module = Procurement** and enter the `PRC-...` reference.
3. Open **Pay posted**. Check the payer, PKR 10.25 amount, original fund and ledger reference.
4. Double-clicking or replaying the same submitted form must not add a second payment or a second successful posting event. Automated tests cover real concurrent attempts; manual clicking alone does not prove concurrency safety.
5. Attempt another **PKR 500** payment. It must fail because only PKR 489.75 remains. Check for a blocked sensitive-action event and confirm no extra ledger debit exists.
6. Reload and pay **PKR 489.75**. Confirm its separate successful event.
7. For a Project or Business procurement payment, the central audit uses the Procurement event with its authoritative parent ledger reference. It must not count the same posting as a second parent payment event.

Separate events such as request creation, approval and payment are expected. They describe different actions and do not mean multiple ledger payments occurred.

## 5. Check other working modules

Perform one normal authorized action in each module, following its earlier testing guide:

- Create or change a donation, fund and non-goods expense.
- Verify a beneficiary and record a Welfare decision, agreement, assistance review or payment.
- Record a Qard repayment.
- Record a Business handover, monitoring update or payment.
- Record a Project amendment decision, progress update or refund.
- Change a Procurement supplier or quotation-count policy.

Filter by the module and record reference. Check the actor, action and safe retained values. Recorded action reasons appear when they are available in the source event; private narratives and evidence contents are not copied into the audit.

## 6. Check login and blocked access

1. Log out, attempt a login with a wrong password, then log in correctly.
2. Open Audit Trail with an authorized account and filter **Module = Security**.
3. Confirm failed login, login and logout events appear. The failed attempt must not claim that the account owner was authenticated.
4. Try opening Audit Trail from an unauthorized account. Check the access-denied event afterward from an authorized account.
5. Check that passwords, complete attempted email addresses, cookies and session tokens do not appear.

Request address and browser information are informational only. They do not prove who was behind a device.

## 7. Check imported history

1. Select **History = Imported history**.
2. Open a retained Fund change or Welfare review from before Phase 10. Check its source action and available original time.
3. Unknown prior values, missing actors and old request details must stay unavailable. The upgrade cannot recover facts that were never recorded.
4. An operator can run `php artisan audit:import-history --dry-run` to count candidates without writing anything.
5. Run `php artisan audit:import-history` to import pending history. Run it again: previously imported source events must not duplicate. Each completed real import run has its own administration event.
6. Existing module history pages must still work and retain their original records.

Historical actor names reflect the identity available during import. A current financial ledger reference is context, not an invented account of every past ledger edit.

## 8. Filter and export

1. Choose a date range, actor, module and outcome. Press **Apply filters**. Dates use **Asia/Karachi**, including the whole selected end date.
2. Check the successful, blocked and security counts against those filters. Security events can also be blocked; these cards are not mutually exclusive totals.
3. Open an event to see the retained before/after values.
4. Choose **Download XLSX** and open the file in Excel or LibreOffice. Check event count, references, amounts and timestamps against the selected list. Values are exported as safe text, so an entered value beginning with `=` cannot become a spreadsheet formula.
5. Choose **Print / Save as PDF**, then use your browser's Print dialog. Select **Landscape → Save as PDF**. Inspect all pages before sharing, especially long change details.
6. Filter more narrowly if more than 10,000 events match. The application rejects oversized exports rather than silently dropping rows.
7. After exporting, find **Export generated** in the audit. The export uses a fixed event snapshot, so it does not include its own later export event.

Events with an unknown occurrence time are shown without a date filter and excluded when you request a specific date range.

## 9. Retention and failure checks

- There is no Edit, Delete or Clear Audit button, including for administrators.
- Archiving a user or source record must not erase its central history.
- `php artisan audit:health` checks audit storage and reports recorded security-capture failures.
- A failure marker under private application storage must be investigated alongside the operational log. Do not delete it just to obtain a green health check. After diagnosing and fixing the cause, an operator can preserve the evidence elsewhere and acknowledge the marker operationally.
- Do not test database failures by damaging the live database. Automated tests inject failures in an isolated test database and verify rollback of the user, permissions, payment and ledger changes.
- Logout must still work if security logging fails. Such a failure is reported operationally; the application must not pretend that event was successfully stored.

Finally, repeat an ordinary Welfare payment, Qard repayment, Business operation, Project payment and Procurement operation. Phase 10 must preserve the existing financial controls. Report problems with the source record number, exact action and error message; do not manually add replacement ledger entries.

## Verified delivery baseline

On 27 October 2026, the full automated suite passed **336 tests and 2,607 assertions**. The local browser checks covered login/logout, the audit list and event details, historical/reference filters, the printable report and an actual XLSX download. Existing accounts and password hashes were verified against the pre-upgrade backup. Run the steps above when you want to check your own roles and records.
