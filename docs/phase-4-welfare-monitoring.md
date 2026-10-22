# Phase 4: welfare review and monitoring

## Delivered

- Extended the existing assistance review model, controller, request and Blade pages; retained all historical reviews.
- Added **Reviews & Follow-ups** to the sidebar and a monitoring section on assistance details.
- Added live queues for overdue reviews, reviews due today, the next 30 days, missing review dates, follow-ups, closure recommendations and ended assistance periods.
- Reviews record findings, an outcome, the next review date, a follow-up action when required, the reviewing account and the time recorded.
- A later review with outcome **Continue assistance** resolves the previous follow-up in the current queue while retaining both historical entries.
- Duplicate review submissions reuse their original record. Conflicting reuse of a request key is rejected. A form opened before another review was recorded cannot silently overwrite its findings or next date.
- Added approver-authorized closure with a mandatory reason, actor, timestamp and status-history entry.
- Added persistent in-app reminders and the scheduled `welfare:refresh-reminders` command. Each assistance/kind/due-date combination has a unique database constraint.
- Review recording, approval, payment completion, void reopening and closure keep reminder state consistent inside their database transaction.

## Confirmed rules

The user approved these defaults:

| Rule | Behaviour |
| --- | --- |
| Weekly assistance | Suggested next review in 7 days |
| Monthly assistance | Suggested next review in 1 calendar month, without overflowing short months |
| Other frequencies | Suggested next review in 30 days |
| New approval with no review date | Set the default review date |
| Explicitly supplied review date | Preserve it |
| Existing approved assistance with no review date | Retain it in **No review date**; record a review to set its next date |
| Future assistance start | Calculate the suggested review from the start date |
| End date before the suggested review | Suggest the end date, or today if the period already ended |
| End date | Inclusive; flag the period as ended on the following local day |
| Reminder calendar | Asia/Karachi; configurable with `WELFARE_TIMEZONE` |
| Reminder delivery | Shared in-app work items in Reviews & Follow-ups; no email/SMS or per-user inbox |

Frequency does not renew the total approved amount. Weekly/monthly assistance continues to use the Phase 2 cap: PKR 500 approved minus PKR 10.25 paid leaves PKR 489.75. Reviews and reminders do not create installments, payments, debits or new authorizations.

Review dates cannot be in the future. The next review date must be on or after the review date; it is required except for closure recommendations. Historical reviews can therefore leave an overdue follow-up visible. A new review cannot be dated before the latest recorded review.

### Completion versus closure

- Paying the full approved total still automatically changes assistance to **Completed** and resolves active reminders.
- Voiding a payment from a fully paid, completed assistance reopens it as approved when money remains unpaid, retaining its review date and restoring any reminders that are due.
- **Recommend closure** records `no_longer_needed`, clears the next review date, and adds the case to **Awaiting closure**. It leaves the assistance approved and its unpaid allocation committed until an approver decides.
- An approver may explicitly close approved or completed assistance with a reason. Closure blocks new payments/reviews, clears the next review date, resolves reminders, and releases any unpaid allocation. Existing payments and the ledger are retained without an automatic refund.
- Repeating closure does not replace its original reason or audit entry.
- Voiding an old payment after explicit closure restores money correctly but does not reopen the closed case.
- An expired end date produces a reminder; it does not automatically close assistance or change its payment authorization. An approver must review and close it.
- There is no automatic reopening of explicit closure or renewal of signed terms.

## Permissions

| Action | Required permissions |
| --- | --- |
| View queues, reminders and review history | `welfare.assistance.view` and `welfare.reviews.view` |
| Record a review | The two view permissions plus `welfare.reviews.manage` |
| Confirm closure | The two view permissions plus `welfare.reviews.close` |

Phase 5 added the dedicated **Reviews & Follow-ups Officer** role and the separate permissions above. The officer role includes both view permissions and review recording; it cannot confirm closure or record payments. Assign the separate closure permission to the responsible approver through Users & Roles. Administrators retain full access.

## Database and local deployment

Migration: `database/migrations/2026_10_18_000002_add_welfare_monitoring.php`.

It adds nullable closure fields, a nullable unique review request key for compatibility with older reviews, follow-up action text, the reminder table, and relevant indexes.

The migration has already been applied to this local workspace after creating and verifying an online SQLite backup:

`storage/app/backups/before-phase4-20261018-161941-d5ffbd.sqlite`

Deployment preserved the existing counts: **6 funds, 20 transactions, 5 assistance records, 5 payments and 2 reviews**. Existing assistance statuses, amounts, approval details, start/end dates and review dates were checked before/after and did not change. The initial refresh created **2 reminders**.

The local review queue, all five existing assistance detail pages, and review forms for approved assistance rendered successfully. This was a server-side rendering check, not a visual browser inspection.

For another environment, back up its database and apply:

```powershell
php artisan migrate --path=database/migrations/2026_10_18_000002_add_welfare_monitoring.php --force
php artisan welfare:refresh-reminders
```

Do not use `migrate:fresh` on application data. Migration rollback is covered by isolated tests, but rolling it back after use would remove monitoring metadata and reminder history.

## Running reminders

The Laravel schedule is registered for **08:00 Asia/Karachi daily**, with overlap protection. Registering that schedule does not start a persistent background process.

For local development, open a separate terminal in the project directory and leave this running:

```powershell
php artisan schedule:work
```

For a deployed server, configure its normal scheduler to run `php artisan schedule:run` every minute from the project directory. On Windows, this can be configured in Task Scheduler using the PHP executable, `artisan schedule:run` as arguments, and the project folder as **Start in**. No operating-system scheduled task was installed by this change.

Refresh reminders immediately without waiting for 08:00:

```powershell
php artisan welfare:refresh-reminders
```

This is safe to repeat: it creates missing due reminders, resolves stale ones, and never issues a payment or closes assistance. It also catches up overdue records after downtime. No queue worker or email configuration is needed for these database-backed in-app reminders.

`php artisan schedule:list` shows the registered command. With the application's existing UTC timezone, its display may show **03:00 UTC**, which is **08:00 Asia/Karachi**.

## Step-by-step manual test

Use a separate test copy of the application database for actions that change reviews or close cases.

1. **Open the queue.** Log in with the Reviews & Follow-ups Officer role and select **Reviews & Follow-ups** in the sidebar. Confirm the seven filters and **Scheduled reminders** section appear.
2. **Check default dates.** Create assistance through the existing signed-agreement workflow, leave its next review date blank, then approve it. A weekly case should receive a date 7 days ahead; a monthly case should receive one calendar month ahead, subject to its start/end dates. An explicitly supplied date should remain unchanged.
3. **Record a follow-up.** Open an approved assistance → **Record Review**. Select **Follow-up required**, use today's review date, enter findings and a concrete action, and set the next review to today. Submit.
   - The history must show your account, findings, action, review date and recorded timestamp.
   - The case should appear under both **Due today** and **Follow-up required**.
   - No payment or financial transaction should be created.
4. **Check duplicates and stale forms.** Open the review form in two tabs before saving either. Submit one tab, then the other.
   - The second form must report that a newer review exists.
   - Resending the exact same original request must reuse its existing review rather than create a duplicate.
5. **Resolve a follow-up.** Open a fresh review form, choose **Continue assistance**, record that the action was completed, and select a future next review date.
   - The case should leave **Follow-up required** and **Due today**.
   - Both review records must remain visible.
6. **Check scheduled reminders.** Record another review with the next date today. Run `php artisan welfare:refresh-reminders` twice.
   - One review reminder should appear for that assistance/date.
   - The second run must not add another copy.
   - Reschedule the next review into the future and confirm the old reminder disappears from the open reminder list.
7. **Check overdue and expired cases.** In the test database, use a case with a next review date before today and/or an end date before today. Run the reminder command.
   - It should appear under **Overdue reviews** and/or **Period ended**.
   - An end date equal to today is not expired.
   - Status must remain approved; no payment or automatic closure occurs.
8. **Recommend closure.** As a reviewer, choose **Recommend closure**, provide findings explaining why support is no longer needed, and submit.
   - The case should appear under **Awaiting closure**.
   - It must remain approved; its unpaid fund allocation remains committed.
   - A reviewer without approval permission must not see or use **Confirm closure**.
9. **Confirm closure.** Log in with an approver account, open the assistance, expand **Close assistance**, enter a reason and confirm.
   - Status becomes **Closed**, with approver, time and reason recorded.
   - Next review becomes empty; open reminders disappear.
   - New reviews and payments are blocked. Existing payments/reviews remain visible.
   - Its unpaid allocation is removed from the fund breakdown; the ledger balance itself is unchanged.
10. **Check repeat closure and payment corrections.** Refresh after closure; its audit must not duplicate. On a separate fully paid test case, confirm explicit closure and then void an old payment.
    - Its fund balance should be restored once.
    - The case must remain closed and must not regain an unpaid allocation.
11. **Check ordinary completion.** On another approved case, pay only the remaining approved amount.
    - It becomes **Completed**, with no open review reminders.
    - Void that final payment before explicitly closing the case: it reopens as approved and due reminders return without duplicates.
12. **Check restricted accounts.** A view-only account may read the queue/history but cannot record reviews or close cases. An account without welfare view permission must receive a permission error.

## Automated verification

The full suite passed with **204 tests and 1,678 assertions**, including **31 new Phase 4 cases**:

- 26 monitoring feature/validation cases in `tests/Feature/WelfareMonitoringTest.php`.
- Five concurrency cases added to the existing payment concurrency harness: identical reviews, competing reviews, closure versus payment, closure versus review, and simultaneous reminder refresh.

Coverage includes permissions, stale submissions, duplicate request conflicts, date validation, calendar-month boundaries, Karachi midnight, inclusive end dates, follow-up resolution, closure audit/rollback, allocation release, payment void behaviour, reminder cleanup, schedule registration, page rendering and reversible migration.

Run Phase 4 feature tests:

```powershell
php vendor/bin/phpunit tests/Feature/WelfareMonitoringTest.php
```

Run the complete suite, including concurrent processes:

```powershell
php vendor/bin/phpunit
```

Verified minimal Herd runtime if the default PHP invocation stalls:

```powershell
& 'C:\Users\Ziyad Usman Khan\.config\herd\bin\php84\php.exe' -n -d 'extension_dir=C:\Users\Ziyad Usman Khan\.config\herd\bin\php84\ext' -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo -d extension=openssl vendor/bin/phpunit
```

Tests use an isolated in-memory database or guarded disposable concurrency databases, not local application records. Concurrency was tested on SQLite; MySQL-specific concurrency has not been exercised here.

## Scope and remaining setup

- Start the Laravel scheduler, or configure the host's minute-by-minute scheduler, for automatic daily refresh.
- No business-rule decision remains open for this phase.
- Email/SMS, personal notification inboxes, broader reporting, and additional operational workflows remain their planned later phases.
- Phase 5 has not been started.
