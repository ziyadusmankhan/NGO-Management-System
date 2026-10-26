# Phase 8 — Project Management

Delivered scope: community projects, versioned itemized budgets, independent approval, direct payments, supplier refunds, amendments, implementation reports, closure, private evidence and project reporting.

## What is built

- Projects list, drafts, correction revisions, field review and three independent decision stages.
- Chief Coordinator → General Secretary → Decision-Making Committee. Final acceptance records an offline resolution and at least two active consenting members.
- Approval creates a commitment, with **no ledger debit**. Payments require a separate authorized finance account.
- Original fund protection, dedicated named-project funds, and restricted project donations. Other modules cannot debit a dedicated project fund.
- Item and total spending limits use integer paisa. Each payment has one expense debit; each actual supplier refund has one refund credit to the original fund and original budget item.
- Amendments pause payments. Prior approved commitments remain until replacement approval. Rejected or withdrawn amendments require an explicit Resume action.
- Explicit project start, progress reports, monthly next-review defaults, final report, completion, independent closure, cancellation and archival.
- Completed projects can pay final bills. Closure releases unused commitments and blocks payments. Late refunds and recording corrections remain possible, including archived history.
- Live Project Status and Active Projects dashboard widgets for project viewers. Active means **In Progress**. Tasks and general Recent Activity remain in development.
- Filtered reporting, print-friendly browser Save as PDF and genuine XLSX files. Existing Finance screens show project payments without creating duplicate generic expenses.

## Workflow and role boundaries

Draft → Submit → Field Review → Chief Coordinator → General Secretary → Committee Approval → Authorized Payments → Explicit Start → Progress → Completed → Closed.

Payment can precede the explicit project start, but does not mark work started. Only approved budget items and their approved payees can be paid. The final bill may follow completion.

| Role | Default project permissions |
|---|---|
| Project Officer / Implementation Committee | View, proposals, field review, amendment preparation, monitoring |
| Chief Coordinator | View and recommendation |
| General Secretary | View and secretary review |
| Decision-Making Committee | View, final approval, closure/cancellation/reopening |
| Treasurer | View, payments, refunds, reports |
| Auditor / Reviewer | View and reports |
| Administrator | All project permissions, while still respecting independence |

The technical slugs are `projects.view`, `manage`, `verify`, `recommend`, `review`, `approve`, `pay`, `refund`, `monitor`, `amend`, `close`, `void`, `reports`, `categories` (each action has the `projects.` prefix). Creating a dedicated fund additionally requires the existing `finance.funds.manage` permission. Project view alone does not grant donor access.

The creator and revision preparer cannot approve their proposal. The three decision stages require different users. Payers cannot be creators, revision preparers, decision actors or consenting members. Closure and spent-project cancellation require someone independent of creation, implementation, monitoring and payments. An administrator account does not bypass these checks.

## Beginner testing guide

Use test data you create yourself. The upgrade does not add demo projects or change existing passwords. A red validation message means nothing was posted; read it, correct the entry and try again. After another account changes a project, reload its page before submitting your next action.

### 1. Prepare separate accounts and committees

1. Log in with your existing administrator account.
2. Open **Users & Roles → Manage Roles**. Confirm the Projects permissions appear.
3. Create or use five **different active users**: a Project Officer, Chief Coordinator, General Secretary, Decision-Making Committee user, and Treasurer. Use different emails. Keep your administrator account for setup and corrections.
4. Open **Members**. Create two active members for committee consent. Neither should be the project creator or amendment preparer; do not link them to the Treasurer account.
5. Open **Committees**. Create an active **Implementation** committee. Create an active **Decision** committee and add the two members, with active membership and a joining date no later than the resolution date.
6. Keep a small PDF or JPG available for testing uploads. Only PDF, JPG and PNG are accepted, up to 10 MB each.

**Expected:** users, committee types and active memberships are saved. Committee consent is documented offline consent, not an online voting feature.

### 2. Create a category and fund

1. As administrator, open **Projects → Categories**.
2. Add `Road repairs`, choose **Community development**, and save it as Active.
3. For a shared fund test, open **Funds** and create an active unrestricted general fund, or a restricted Community development fund. Give it at least PKR 1,000 of legitimate test opening balance or a recorded test donation.
4. Category labels may be edited. Once used, their controlled funding purpose cannot be changed; create a new category for another purpose.

**Expected:** Qard and Small Business restricted funds are not valid funds for this project.

### 3. Save the first project

1. Log out and log in as the **Project Officer**.
2. Open **Projects → New project**.
3. Enter a name, location, description, outcomes, approach and estimated beneficiaries.
4. Select the category, implementation committee, responsible active user, planned start and completion dates, and eligible fund.
5. Add one budget item: description `Repair labor`, type **Labor**, payee `Worker One`, maximum **500.00**.
6. Enter a labor estimate reference, such as `Labor quote Q-1`. Upload its evidence below the budget.
7. Click **Save draft revision**. Open **Edit draft** to confirm the saved details. Then submit the revision.

**Expected:** a PRJ number is created. Submission locks the original fund. No payment or debit exists.

For a purchase instead of labor, enter two different suppliers' quote amounts and references and attach their evidence. If there are fewer than two price checks, enter an exception reason; the final committee must explicitly approve that exception.

### 4. Complete the independent approval chain

1. As Project Officer, record **Field review**, findings and evidence.
2. Log in as **Chief Coordinator**, open the project and accept with a reason.
3. Log in as **General Secretary** and accept with a reason.
4. Log in as the **Decision-Making Committee user**. Select Accept, enter a reason, choose the active decision committee, enter resolution reference and date, upload the signed resolution and select both consenting members. Approve any documented price-check exception explicitly.
5. Check **Funds → original fund**. The commitment should be **500.00** and ledger cash should be unchanged.
6. Try using the creator for a decision or the same person for two decision stages on a separate test case. Try one committee member only. These attempts must be rejected.

**Expected:** the budget is approved, but the project still has zero payments. To test rejection, reject a revision, prepare a corrected proposal revision, edit it and repeat the chain. Earlier revisions remain visible.

### 5. Test the exact PKR 500 payment limit

1. Log in as **Treasurer** and open the approved project.
2. Under **Record authorized payment**, choose the budget item. Enter the exact approved payee name `Worker One`.
3. Enter **10.25**, a current date, payment method, receipt number and evidence. Record it.
4. Confirm gross payments **10.25**, net expenditure **10.25**, and unused budget **489.75**.
5. Try a further **500.00**. It must be rejected.
6. Record **489.75**. Total gross payments and net expenditure must now be **500.00**, with **0.00** unused budget.
7. Open **Finance → Expenses**, **Transactions**, and the original fund. There must be exactly two matching project debits, for 10.25 and 489.75. No additional generic expense record is needed.
8. Try paying as the creator, a decision actor or a consenting member's linked account. Each attempt must be blocked even if that person has payment permission.

Automated separate-process tests cover double submissions and concurrent requests. A repeated request key returns the existing payment; a different stale form must be reloaded.

### 6. Test actual supplier refunds

1. As Treasurer, choose the **489.75 original payment** under **Record an actual supplier refund**.
2. Enter **100.00**, the original payee, date, receipt and evidence of money actually returned.
3. Confirm gross payments **500.00**, supplier refunds **100.00**, net expenditure **400.00**, and unused budget **100.00**.
4. Open the original fund. There must be exactly one refund credit of 100.00. Its refunded total must include the return once.
5. Try refunding another **400.00** against that 489.75 payment. It must fail because only 389.75 remains refundable on that payment.
6. On a project with two budget items, confirm a refund on one item does not increase the other item's limit.

**Expected:** refunds always follow the original payment and fund. A wrong fund cannot be supplied by the form or accepted in a forged request.

### 7. Test budget amendments

1. As Project Officer, select **Prepare budget amendment**, give a reason, then edit the new draft.
2. Confirm payments are paused and the previous approved budget and commitment are still shown.
3. Keep the stable existing item reference. Try a limit below its current net expenditure: saving must fail.
4. Increase its limit or change its supplier, upload fresh estimate/quote evidence, and submit.
5. Repeat field review and all three independent decisions. Only final approval activates the replacement budget. No debit is created by approval.
6. For another amendment, use **Withdraw amendment** or reject it during review. Confirm payments stay paused until **Resume previous approved budget** is explicitly recorded.

**Expected:** progress reports and refunds remain available during amendments. Every submitted revision, item limit and decision remains visible in retained history.

### 8. Start, monitor, complete and close

1. As Project Officer, select **Start implementation**, enter the actual start date and upload evidence.
2. Open Dashboard as an authorized project viewer. Active Projects increases because the lifecycle is now **In Progress**.
3. Record progress with a date, percentage, findings and risks. Leave next review blank to default to one calendar month later, with month-end adjustment.
4. When work is done, choose **Mark Completed with final report**. Enter the actual completion date, 100%, final findings and final report attachment.
5. If budget remains, log in as Treasurer and pay a legitimate final bill. Completed projects allow this within the approved limits.
6. Log in as an independent closure approver. Choose **Accept final report and close**, enter the reason and confirm all final bills are settled.
7. Confirm new payments are blocked and the fund's unused project commitment is released.
8. Record a genuine late refund. It credits the fund, but the project stays Closed and spending stays blocked.
9. To resume spending, an authorized closer prepares a **reopening amendment**. An officer edits and submits it through the full chain. A previously started project returns to In Progress and needs a new completion report; past completion history is retained.
10. Archive a closed or cancelled record. Find it using the list's Archived filter. History, documents, late refunds and authorized correction access remain available.

Before spending, cancellation needs a reason. After spending, it also needs a final accounting attachment and an independent authorized approver.

### 9. Test a dedicated named-project fund

1. Create another project draft and leave the fund empty.
2. As an authorized finance user with project view and fund management permissions, open that draft and select **Create a dedicated restricted project fund**.
3. The new fund starts at zero and is bound exclusively to this project. Keep it as this project's original fund.
4. Open **Donations → Add donation**. Choose the bound fund, **Restricted** designation and **Project** purpose. Record the received test donation.
5. Confirm the project's original fund is locked and the donation appears in that fund's finance history.
6. Try using this fund for another project, generic expense, Welfare, Qard or Small Business payment. Every attempt must be rejected.
7. Try moving the donation to another fund or changing its classification to unrestricted. Both must be rejected.
8. Close the named project with money remaining. The remaining cash must still be restricted to that project; closure does not allow reuse elsewhere.
9. Log in as someone who has only project permissions. They can see project history and private project evidence, but not donor details or the finance donation screens.

### 10. Test corrections, reminders and exports

1. Use an account with **Projects void** permission. Void a recording error with a reason. An actual return of money is a Refund, not a void.
2. A payment with posted refunds cannot be voided until those refunds are voided first.
3. Voiding a refund must fail if it would overdraw the fund or exceed current approved item/project limits. Approve a suitable amendment first when higher authority is needed. The failed attempt must leave both records posted.
4. Repeating a void must not move money twice. Check retained audit reasons and transaction statuses.
5. Run `php artisan projects:refresh-reminders` to refresh reminders immediately. Due reviews include today's date; completion is overdue only after the planned date has passed, using Asia/Karachi dates.
6. Open **Projects → Reports & exports**. Try date, status, category, responsible-person and fund filters.
7. Download XLSX and open it in Excel or LibreOffice. Compare totals with the page. Click **Print-friendly report**, then Ctrl+P → Landscape → Save as PDF. Check headings and all columns in print preview.
8. Date filters apply to currently posted payments/refunds, inclusively. Budgets, unused budget/variance, commitments and progress show current lifetime values. Voided movements remain in case history and do not count in posted totals.
9. Open a document link while logged out or using an account without project view. Access must be refused. A document ID from another project must return 404.

## Developer notes and activation

The domain lives under `app/Domain/Projects`; routes are in `routes/projects.php`. The additive migration creates 11 project tables and adds nullable `funds.project_id`. No existing records are relinked or deleted. Revisions snapshot category labels, purpose, assignments, scope and budget. Stable item identities connect all approved versions and movements.

Payment records and ledger entries are committed atomically. SQLite writer locks and row locks serialize project/fund operations; global request keys, payload hashes and project versions protect retries and stale forms. Files are private and cleaned up if a transaction fails. MySQL/PostgreSQL deployment and concurrency certification remain separate work.

New generic Project expenses cannot spend outside this workflow. Historical posted Project expenses remain historical; marking one Paid reuses its original debit. Reconciliation checks project sources and ledger entries and reports missing/mismatched entries without creating replacement project payments. Historical shared-purpose donations are not guessed into project links.

### Activate another local checkout

Close browser forms during the backup/migration comparison. From the repository root, run each command and stop if it reports an error:

```powershell
php scripts/phase8-sqlite-upgrade.php backup
php artisan migrate --force
php artisan db:seed --class=ProjectRoleSeeder --force
php scripts/phase8-sqlite-upgrade.php verify
php artisan view:clear
php artisan projects:refresh-reminders
```

The helper uses SQLite's online backup API, checks integrity and foreign keys, and saves a manifest under ignored `storage/app/backups`. Verification compares the original columns of existing tables, including users/password hashes, and requires new project tables to be empty. It excludes permission/configuration/runtime tables intentionally changed by activation. Do not rerun the backup command over an existing Phase 8 manifest. There is no reset or fresh migration in this procedure.

Daily refresh is scheduled for **08:15 Asia/Karachi**. For local testing, use `php artisan schedule:work` in another terminal while you need scheduled reminders. Deployment requires the normal Laravel scheduler to run each minute. Reminders do not create payments.

### Automated checks

```powershell
php artisan test --compact --filter=Project
php artisan test --compact
node --test tests/Support/money-preview.test.cjs
php artisan view:cache
php artisan route:list --path=projects
```

Run PHP test commands sequentially: test cases use shared fake storage names, while their databases are isolated. The test base refuses live database configurations. Concurrency tests use guarded temporary SQLite files and independent PHP workers.

## Local rollout verification

The additive migration and `ProjectRoleSeeder` were applied on 25 October 2026. The verified pre-upgrade SQLite backup is `storage/app/backups/before-phase8-activation-20261025-085704.sqlite` (private and excluded from Git).

- Database integrity and foreign-key checks passed.
- All 46 compared existing data tables were unchanged. All 9 existing users, including their password hashes, were preserved.
- The 11 new project tables were empty after activation; no demo projects were created.
- Full regression suite: **294 tests passed, 2,366 assertions**. This includes 24 project workflow, integrity and concurrency tests, plus the existing Welfare, Qard, Small Business and Finance checks.
- A subsequent focused export/access check passed (1 test, 15 assertions), including an end-date-only report filter and reading back the actual XLSX cells for PKR 10.25 gross payments and PKR 489.75 remaining budget.
- Existing money-preview JavaScript checks passed (10 assertions). The new project form JavaScript passed its syntax check.
- Blade view compilation, the project reminder refresh command and discovery of all 28 project routes passed. Database preservation checks were repeated successfully on 26 October 2026.
- Automated HTTP tests exercise rendered project screens and access restrictions. A visual browser walkthrough could not be completed because the browser preview failed to attach; use the beginner checklist above to inspect the layout and PDF print preview in your browser.

## Remaining roadmap

Phase 9 Procurement → 10 unified Audit Trail → 11 Documents/security → 12 Records Reports, remaining dashboard widgets, search and notifications → 13 cleanup/integration → 14 Tasks/Meetings → 15 persistent Settings. Each gets a detailed business-rule plan first.

Procurement orders/receipts, staff advances, inventory, loans and donor refunds are outside Phase 8.
