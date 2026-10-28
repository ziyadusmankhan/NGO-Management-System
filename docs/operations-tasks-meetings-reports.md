# Operations Tasks, Meetings and Records Reports

This delivery replaces the three remaining placeholder sections with live database-backed workflows.

## Tasks

- Create, assign and filter tasks by user, committee, project, priority, status and due date.
- Record progress, blocking/completion notes and timestamped updates.
- Meeting action items become ordinary linked tasks and use the same dashboard, permissions and audit trail.
- Stale form submissions are rejected with a reload message. Completed tasks must be exactly 100%; a non-completed task cannot be 100%.
- Only completed or cancelled tasks can be archived. Archived records can be restored and retain their history.
- Descriptions, expected outcomes, completion notes and comments use the Phase 11 encrypted-field system.

## Meetings

- Schedule committee, general, special or project meetings with a chair, location, agenda, invited users and quorum.
- Record actual attendance, minutes, decisions and up to 25 action items in one atomic operation.
- Minutes cannot be finalized without the recorded quorum and can only include invited users.
- Finalized minutes cannot be rewritten through the ordinary form. Scheduled meetings may instead be cancelled with a retained reason.
- Held and cancelled meetings can be archived/restored. Agendas, minutes and decisions are encrypted.

## Records Reports

- Generate retained snapshots for Finance, Welfare, Qard, Small Business, Projects, Procurement, Operations and Organization.
- The report list only offers modules the signed-in user is independently authorized to view. `reports.view` does not expose a source module by itself.
- `reports.generate` is separately required to create or export snapshots.
- Date filters use each domain's real creation/payment/meeting dates. Posted financial movements are the source for money totals.
- Generated reports remain fixed even when later records change. Each report has a browser print/Save-as-PDF view and a genuine XLSX export.
- Report creation and export, task changes, meetings and action items are recorded by the general Audit Trail. Free-text operational details stay in their encrypted source records.

The dashboard Upcoming Tasks card is now live for authorized users. General Recent Activity remains assigned to the later dashboard/notification work.

No existing users, passwords, operational records or financial records are reset by this delivery. The migration is additive and creates no demo tasks, meetings or report runs.

## Verified local activation

Activated on 28 October 2026 after a verified database-and-upload backup and an isolated restore rehearsal. The rehearsal preserved 9 users and every password hash, verified 24 uploaded files and 81 protected values, and passed SQLite integrity and foreign-key checks. The live security and audit health checks passed after migration. The complete automated suite passed with 366 tests and 2,837 assertions; Composer reported no known locked-dependency advisories.

The recovery backup is stored under `storage/app/backups/phase11-20261028-193426`. It is private operational data and must not be committed or shared.
