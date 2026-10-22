# Phase 5: exact money, review permissions and a fresh start

## Delivered

- Finance reports, dashboard totals, donation/expense summaries, ledger totals and welfare summaries now use integer paisa and exact decimal strings.
- Application contributions, committee recommendation amounts and final approval amounts no longer pass through PHP floating point.
- SQLite money reads use fixed-scale text before arithmetic, including donations, expenses, application reviews and beneficiary income.
- Money inputs reject extra decimal places, scientific notation and floating-point JSON numbers. Send JSON money as text, such as `"10.25"`. Ordinary HTML forms already do this.
- Financial pages and form previews show two decimal places. Browser previews use integer paisa with BigInt. The server independently calculates all financial results.
- The dashboard chart uses floating-point geometry only for its angles; its monetary labels and balances remain exact.
- Unimplemented Qard, Small Business, Operations and Records pages now show an honest empty state. Their old sample templates remain in the source, but their routes no longer display sample transactions or people.

No new database migration is required for this phase.

## Reviews & Follow-ups permissions

The role **Reviews & Follow-ups Officer** is installed in the local application.

| Permission | Purpose |
| --- | --- |
| `welfare.reviews.view` | View the queue, reminders and review findings |
| `welfare.reviews.manage` | Record reviews and follow-up actions |
| `welfare.reviews.close` | Confirm closure with a reason |

These permissions appear together under **Reviews Followups** in Users & Roles. Assistance viewing (`welfare.assistance.view`) is a prerequisite. Recording and closure also require review viewing.

The new officer role includes welfare viewing, assistance viewing, review viewing and review recording. It does **not** include payments, assistance approval or closure. Administrators have full access. Assign closure separately to the responsible approver if needed.

For another installation:

```powershell
php artisan db:seed --class=WelfareReviewRoleSeeder --force
```

The seeder is repeatable and does not create users or erase custom role permissions.

## Local database reset

**The live reset completed on 20 October 2026.** The user selected `admin@example.com`. It is now the only user account (ID 2), and its existing password is unchanged. All 23 non-configuration tables were verified empty, including business records, sessions and caches. Roles, permissions and migration history remain. Both the live database and backup passed integrity checks, and the live database has no foreign key violations.

Verified backup immediately before deletion:

`storage/app/backups/before-phase5-reset-20261020-141111-4ugrxefu.sqlite`

A verified backup was created before installing the new permissions:

`storage/app/backups/before-phase5-permissions-20261018-235602-b9053a.sqlite`

The new `foundation:reset-test-data` command:

1. Requires the local environment, a SQLite database and an explicitly selected existing active administrator.
2. Shows a preview unless `--execute` is supplied.
3. Acquires the database writer lock and creates an online backup in `storage/app/backups`.
4. Verifies backup integrity and user count before deleting data.
5. Clears business tables, including soft-deleted rows, members, committees, funds, donations, expenses, transactions, beneficiaries, applications, agreements, payments, reviews, reminders and histories.
6. Deletes every other user, clears sessions and remembered login tokens, and resets business record numbering.
7. Keeps the selected administrator's ID, email and password, together with roles, permissions and migration history.
8. Checks empty table counts, foreign keys and administrator preservation before committing. A failure rolls back the deletion.

Existing uploaded files are retained on disk. This is a database reset; backups and uploaded files are not erased.

Preview, replacing the email with the selected account:

```powershell
php artisan foundation:reset-test-data --admin-email="admin@example.com"
```

Execute the reviewed reset:

```powershell
php artisan foundation:reset-test-data --admin-email="admin@example.com" --execute
```

Sign in again afterward with that account's existing password. Avoid entering new records in another browser while performing a reset.

## Step-by-step manual checks after the reset

1. **Check the empty starting point.** Sign in as the retained administrator. Users should contain one account. Members, committees, funds, donations, expenses, transactions, beneficiaries, applications and assistance should all be empty. Dashboard financial totals should be `0.00`. Roles and permissions intentionally remain.
2. **Create test accounts.** In Users & Roles, add an account with **Reviews & Follow-ups Officer**. Add other accounts with the operational roles you want to test. Use the sidebar logout button to switch accounts.
3. **Create a fund.** Add a general, unrestricted fund with an opening balance of `1000.00`. Its ledger balance should be `1000.00`.
4. **Record donations.** Record received donations of `10.25`, `0.10` and `0.20` into that fund. Posted credits should total `10.55`, and the fund balance should be `1010.55`. Check the donation list, finance report, transactions page and dashboard.
5. **Record an expense.** Create an expense for `0.30`, then approve it as an authorized user. The posted debit should be `0.30` and the fund balance `1010.25`. Check both the expense detail and ledger.
6. **Check decimal validation.** Try entering `10.255` or `1e2` in a money field. It must not save. Enter `10.25` and confirm both preview and saved display retain `.25`. JSON API callers must quote the amount.
7. **Create a welfare case.** Create and verify/approve a beneficiary, submit an application, complete field verification, committee recommendation, General Secretary review, final decision and the signed agreement. Use a fund whose designation permits the assistance purpose.
8. **Check the contribution.** For requested assistance of `500.00` with applicant contribution `10.25`, the organization's contribution must be `489.75`. The server must calculate this even if a client sends a different total.
9. **Check the payment cap.** Approve assistance with a total cap of `500.00`. Record `10.25`. The remaining payable amount must be `489.75`; a further `500.00` payment must fail. Paying `489.75` should complete the assistance with total payments of `500.00`.
10. **Check review access on an approved, unpaid case.** Log in as the new review officer, open Reviews & Follow-ups and record an action with outcome **Follow-up required**. The account should be able to read the history but must not have payment, assistance approval or closure authority.
11. **Check closure separately.** The officer may recommend closure. Sign in as the administrator, or an account explicitly granted the closure permission, and confirm closure with a reason. This must not create a financial transaction.
12. **Check reminders.** Set a review date to today and run `php artisan welfare:refresh-reminders` twice. The case should have one open due reminder. A later review with a future date should resolve it.

## Automated checks

**Verified on 20 October 2026: 219 PHP tests passed (1,856 assertions), plus 10 JavaScript money-preview assertions.**

The suite covers exact totals, large amounts, invalid money, contribution subtraction, role isolation, empty-page rendering, all previous financial/workflow tests, and resetting a disposable database while preserving its administrator and verified backup.

```powershell
php vendor/bin/phpunit
node tests/Support/money-preview.test.cjs
```

Tests use isolated databases. The reset test uses its own temporary SQLite file and refuses destructive execution in the normal testing environment until it explicitly exercises the local-only guard inside that disposable test.
