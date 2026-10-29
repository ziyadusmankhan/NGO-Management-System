# Phase 12 beginner testing guide

This guide assumes the application is running and you have at least one Administrator plus a restricted staff account. Use different browser profiles when testing two accounts.

## 1. Prepare safely

1. Back up the database.
2. Run `php artisan migrate --force`.
3. Run each reminder refresh command listed in `phase-12-dashboard-search-notifications.md`.
4. Sign in as Administrator.

Pass: the Dashboard opens, the top search box is visible, and the bell opens Notifications without an error.

## 2. Test global search

1. Create or identify one Member and one Beneficiary with names and record numbers you recognize.
2. Search using part of the Member's name. Open the result.
3. Search using the exact Member number.
4. Search using the complete CNIC or phone number.
5. Try only part of the CNIC or phone number.
6. Search for a known Fund, Welfare application, Qard loan, Project, Procurement request, Task, Meeting, Document and saved Report if those records exist.
7. Search for `%` and then `_`.

Pass: names and record numbers find the correct authorized records; a complete CNIC/phone finds the correct person; partial CNIC/phone does not; wildcard-only searches do not enumerate the database.

## 3. Test search permissions

1. Sign in as a user who can view Welfare but cannot view Finance.
2. Search for a known Welfare number and a known Finance number.
3. Give that user Finance View permission, sign out/in, and repeat.
4. Remove the permission and repeat once more.

Pass: Welfare is visible throughout; Finance appears only while the source permission is present. The same rule applies to every search group.

## 4. Create a due reminder

Use an existing test record rather than changing a real case. Set a Welfare review, Qard follow-up, Business review, Project review, Procurement delivery/invoice/refund, or Task due date to today or earlier using its owning workflow. Run that module's reminder refresh command when applicable.

Pass: a matching item appears in Notifications for an account with source access. An account without source access does not see its title, count or link.

## 5. Test personal notification state

1. Open Notifications and note the unread count.
2. Click **Mark read**. The count falls by one.
3. Click **Mark unread**. The count rises by one.
4. Click **Dismiss**. The item disappears from Current.
5. Select **Dismissed**, then click **Restore**.
6. Sign in as a second authorized user.

Pass: the first user's read/dismiss choices do not change the second user's inbox and do not resolve the source reminder. The header bell agrees with the inbox unread count.

## 6. Test dashboard summaries

1. As an authorized user, compare Dashboard Welfare open/review/payment totals with Welfare records.
2. Compare Qard active/outstanding/follow-up totals with Qard records.
3. Compare Active Projects and Project Status with Projects.
4. Compare Small Business totals and Upcoming Tasks with their source screens.
5. Perform a safe test edit in a module and return to Dashboard.

Pass: the permitted live summaries match their sources. Recent Activity shows the sanitized action, actor, reference and local time, and its link opens only when source access is allowed.

## 7. Test dashboard privacy

1. Sign in as a role without `finance.view`.
2. Confirm Fund Overview, Donations This Month, Total Fund Balance and Recent Transactions are absent.
3. Sign in as a role without `members.view` and confirm Member totals are absent.
4. Confirm the same user sees only authorized Recent Activity.

Pass: Dashboard never becomes a shortcut around module permissions.

## 8. Regression check

Run:

```powershell
php artisan test
```

Pass: all tests succeed. Also open Welfare, Finance, Qard, Business, Projects, Procurement, Documents, Audit Trail, Tasks, Meetings and Reports and confirm their normal list/detail screens still work.
