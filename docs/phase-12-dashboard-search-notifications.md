# Phase 12 — Dashboard, Search and Notifications

## Delivered scope

Phase 12 turns the top search box and bell into live, permission-aware tools and completes the remaining operational dashboard summaries.

- Global search covers Members, Committees, Users, Beneficiaries, Welfare, Finance, Qard, Small Business, Projects, Procurement, Tasks, Meetings, Documents and saved Reports.
- Search runs only against modules the signed-in user may view. Procurement and Documents also reuse their record-level visibility scopes.
- CNIC and phone searches require the complete normalized value and use the encrypted exact-search indexes. Names and record numbers support partial text.
- `%` and `_` are not accepted as public wildcards, preventing a blank-like query from listing every authorized record.
- The notification inbox unifies existing Welfare, Qard, Business, Project and Procurement reminders plus due Tasks. It never creates a payment or changes a workflow state.
- Read, unread, dismiss and restore state belongs to each user. Dismissing a reminder does not resolve the source reminder for other staff.
- The header bell displays the current unread count. Source permissions are rechecked every time the inbox is opened or changed.
- Dashboard Recent Activity uses sanitized Phase 10 audit events and source permissions. Full Audit Trail viewers retain organization-wide audit access; ordinary staff see only events for modules they may view.
- Welfare and Qard dashboard summaries are live. Existing Project, Small Business and Upcoming Tasks widgets remain live.
- Member and financial dashboard cards are hidden when the user lacks their source permission.

## Data and routes

The additive `user_notification_states` table stores only the notification key and the signed-in user's read/dismiss timestamps. Source reminder records remain authoritative and unchanged.

Routes added:

- `GET /search`
- `GET /notifications`
- `POST /notifications/read-all`
- `POST /notifications/change`

All routes require authentication. Search and notifications intentionally need no broad new permission: the returned modules are the intersection of the user's existing source permissions.

## Refresh and scheduling

The unified inbox reads existing reminder tables. The existing scheduler refreshes Welfare, Qard, Business and Project reminders daily; Procurement refreshes at 08:15 Asia/Karachi. Tasks are calculated from their current due date when the inbox opens.

For a local machine where the scheduler is not kept running, refresh the source reminders manually:

```powershell
php artisan welfare:refresh-reminders
php artisan qard:refresh-reminders
php artisan business:refresh-reminders
php artisan projects:refresh-reminders
php artisan procurement:refresh-reminders
```

These commands only refresh reminder records. They do not issue payments, approve applications or close records.

## Boundaries

- No email, SMS or WhatsApp delivery is added.
- There is no full-text search inside uploaded files or encrypted narratives.
- Notifications are current-work reminders, not a replacement for the retained Audit Trail.
- The application does not claim that a reminder was personally seen merely because it was generated.

## Rollout

Back up the live database, run `php artisan migrate --force`, refresh reminder sources, and run the regression suite. The migration is additive and preserves users, passwords, source reminders, financial records and documents.

Local activation completed on 29 October 2026.

- Verified backup: `storage/app/backups/phase11-20261029-003010`.
- Isolated restore verified 28 original files, 134 protected values, nine users with unchanged password hashes, eight document records and no missing versions.
- Migration batch 44 added `user_notification_states`; the database integrity check returned `ok` with zero foreign-key violations.
- All source reminder refresh commands, `security:health`, `audit:health` and Blade compilation succeeded. The login page returned HTTP 200 after maintenance mode ended.
- Final automated result: 371 tests passed with 2,884 assertions.

Next: Phase 13 integration, cleanup and deployment hardening; Phase 15 remains persistent Settings.
