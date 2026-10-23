# Phase 7 — Small Business Assistance

Implementation and testing guide. Updated 23 October 2026.

## What is now available

Open **Programs → Small Businesses**. The module uses real records and supports:

1. Draft applications, itemized investment budgets, household snapshots and private evidence.
2. Field verification → Chief Coordinator → General Secretary → recorded Decision-Making Committee resolution.
3. An initial agreement with borrower, foundation representative, two witnesses and two signed copies.
4. Staged beneficiary payments, supplier payments and asset purchases with handover acknowledgements.
5. Explicit funding finalization. Unused approval is released. Repayable support requires a signed final statement for actual principal and its schedule.
6. Principal-only repayment, early settlement, oldest-installment allocation and authorized voids.
7. Evidence of the actual business start, monitoring visits, reminders, final report, independent closure and archival.
8. Fund commitments and source links, business-originated principal in Qard, beneficiary history and live business dashboard totals.
9. Small Business reports, shared fund capital, print/save PDF and real Excel workbooks.

Choose **Grant** or **Repayable** for each application. Mixed grants/loans, interest, penalties, write-offs, rescheduling, inventory and actual supplier refunds are outside this phase. A void corrects an incorrectly recorded movement; it does not document goods or money returned by a supplier.

## Responsibilities

| Role | Default Small Business responsibilities |
|---|---|
| Administrator | All actions, including category maintenance and voids; separation rules still apply |
| Small Business Officer | Drafts, submission, verification, agreements, handovers, start evidence and monitoring |
| Implementation Committee | Applications, verification and monitoring |
| Chief Coordinator | Implementation recommendation |
| General Secretary | Secretary review |
| Decision-Making Committee | Record final committee resolution and approve case closure |
| Treasurer | Funding releases, funding finalization, principal repayment and reports |
| Auditor / Reviewer | View cases and reports; no changes |

Permissions are `business.view`, `manage`, `verify`, `recommend`, `review`, `approve`, `agreements`, `disburse`, `finalize`, `repay`, `monitor`, `close`, `void`, `reports`, and `categories` (each prefixed with `business.`). Every action also requires `business.view`.

The creator cannot approve their request. The coordinator, secretary and final decision recorder must be different people. The funding payer/finalizer cannot be the requester, any of those three decision actors, or a consenting committee member. Linking a committee member to a user later does not bypass that check. A different authorized person must accept the final monitoring report for closure; its author and the creator cannot close it.

Committee decisions are recorded from an attached offline resolution. At least two distinct active members of the selected active Decision committee must consent. The requester cannot count. Formal online voting is reserved for Meetings.

## Setup without losing existing data

These are deployment steps for another checkout. The local activation record below states what has already been done here.

1. Back up the database and private files. For SQLite use its backup API or a verified consistent backup, not a copy of a database with uncheckpointed WAL writes.
2. Install the locked dependencies: `composer install`.
3. Run `php artisan migrate --force`.
4. Run `php artisan db:seed --class=BusinessRoleSeeder --force`.
5. Run `php artisan optimize:clear`.
6. Keep the Laravel scheduler running. For local testing: `php artisan schedule:work` in a separate terminal.

Do **not** run `migrate:fresh`, `db:wipe`, or the local reset command to install this phase. The migration adds fourteen tables. The seeder adds permissions and three roles without removing existing role permissions. It creates no users, funds, beneficiaries or demonstration applications. Business categories start empty and are entered by an authorized administrator.

The Excel export uses PhpSpreadsheet. Install the PHP extensions required by Composer, including ZIP, XML, GD and mbstring. Commit and deploy both Composer files together.

## Test from the beginning

Use a local/test copy for invented applicants, payments and signatures. Never represent a generated unsigned PDF as proof that someone signed.

### A. Prepare the people and fund

1. Log in as your administrator.
2. In **Users & Roles**, create separate active accounts for Officer, Chief Coordinator, General Secretary, Decision-Making Committee, Treasurer and Auditor / Reviewer. Assign the matching roles above. Keep a note of each test account's password.
3. Create at least two active organization Members. In **Committees**, create an active committee of type **Decision**, then add those members as active members, with joining dates on or before today. Link members to their user accounts where applicable. The treasurer must not be a consenting member for this test.
4. In **Funds**, create a restricted fund designated **Small Business**. Use an opening balance of PKR 0 if funding it with a donation.
5. In **Donations**, record and post a PKR 1,000 donation to that fund. Open the fund and confirm its ledger balance is PKR 1,000. Do not also enter the same amount as an opening balance.
6. In **Beneficiaries**, create a test applicant with identity, family, income, assets and debts. Complete verification and approval. Unverified or unapproved applicants will not appear in the business form.
7. Open **Small Businesses → Categories**. Add an active category such as **Retail**.

Expected: your existing accounts and records remain present. Setup creates only the records you choose to enter.

### B. Create a repayable application

1. Log out. Log in as the **Small Business Officer**.
2. Open **Small Businesses → New application**.
3. Select the approved beneficiary and Retail category. Enter a business name, location, proposed activity, skills, customers, risks and expected start date.
4. Choose **Repayable**, your Small Business fund, **Monthly**, and **3 installments**.
5. Add one budget item: **Initial stock**. Set total cost to **600.00**, applicant's own share to **100.00**, and release method to **Beneficiary cash release**.
6. Enter reasonable projected monthly sales and expenses. Attach application evidence if available.
7. Click **Save draft**. The organization cap should be **PKR 500.00** (600 minus 100). No ledger transaction should exist yet.
8. Click **Edit draft**, change a description and save. Then click **Submit application**.
9. Enter field findings and attach a PDF, JPG or PNG under **Field verification and viability**. Click **Record verification**.

Expected: the case reaches **Chief Coordinator**. Posted money remains zero. Drafts are editable; submitted terms are controlled by the workflow.

### C. Complete the three decisions

1. Log in as **Chief Coordinator**. Open the case, choose Accept, enter reasons and record the decision.
2. Log in as **General Secretary**. Accept with review notes.
3. Log in as **Decision-Making Committee**. Select the active Decision committee. Enter the resolution reference and date, attach the resolution, and select the two consenting members. Accept.
4. Check the case's decision history. Each stage should show a different actor, notes and timestamp. The final decision retains the consenting member names and positions.
5. Open the fund as an authorized finance user. It should show **500.00 allocated**, **1,000.00 ledger balance**, **500.00 available after commitments**. Approval has not created a payment.

Negative checks: try a decision as the creator; try the coordinator account again at the secretary stage; try only one committee member; try no resolution file. Each should fail without advancing the case. Administrators do not bypass these checks.

### D. Sign the initial agreement

1. Log in as the Officer and open the case.
2. Click **Print the current agreement**. Use your browser's Print → Save as PDF to inspect the template.
3. Obtain genuine signatures for real cases. For local tests, use files clearly marked **TEST ONLY**.
4. Enter borrower and foundation representative names, two different witness names and the signing date. Upload the borrower and foundation signed copies. Confirm the applicant contribution.
5. Click **Record both signatures**.

Expected: the case becomes Signed. It still has no ledger payment. Signed terms include the original fund and item budgets. Changing approved terms before any payment requires **Case controls → Change approved terms**, a reason, a new revision, fresh decisions and new signed copies.

### E. Record staged funding

1. Log in as Treasurer. Open the case and select Initial stock under **Record a funding release**.
2. Enter **10.25**, today's date, payment method, recipient, receipt number and payment evidence. Record it.
3. Confirm Released **10.25**, Remaining to release **489.75**, and exactly one posted ledger debit.
4. Try another release of **500.00**. It must fail because only **489.75** remains.
5. Enter **489.75** and record it. Released should become **500.00**, with two matching debits and a **500.00** fund balance.
6. Open the printable **final principal statement**. Check actual principal **500.00** and installments **166.66, 166.66, 166.68**.
7. Complete the final signing fields and upload both signed final copies. Enter a funding completion reason and click **Finalize funding**.

Expected: no further funding releases are allowed. The first installment is due one monthly interval after finalization. The final schedule creates no ledger entry. For finalization on January 31, dates are February 28 (29 in a leap year), March 31 and April 30.

Also test finalizing another case below its cap: release **100.00** of a **500.00** approval and finalize. Only **100.00** is owed; the unused **400.00** commitment is released.

### F. Recover the principal

1. As Treasurer, record a repayment of **10.25** with its receipt and method.
2. Confirm Recovered **10.25**, Outstanding **489.75**, and one recovery credit to the original fund.
3. Try a repayment of **500.00**. It must fail.
4. Record **489.75**. Recovery status becomes **Repaid** and outstanding becomes **0.00**.
5. Open the fund. Starting from the PKR 1,000 example, its balance is **1,000.00**, recovered principal is **500.00**, and there is no extra “recycling” transaction.
6. Open **Qard-e-Hasana** with a user permitted to view both modules. Find the case in **Business-originated principal**. Its link goes to the business case; there is no duplicate standalone Qard loan.

Early and partial repayments are allowed after funding finalization. Money is applied to the oldest unpaid installment first. Voluntary donations must use Donations separately and do not reduce loan principal.

### G. Try grant, supplier and asset cases

1. Create a second case with **Grant**. Complete its approvals and signatures. Pay and finalize it. It must have no debt or repayment schedule.
2. For a supplier item, enter the selected supplier and two distinct price checks with amounts and references. Attach combined quotation evidence before submission.
3. If two checks are unavailable, enter a purchase exception reason. The final committee recorder must explicitly confirm that the attached resolution approves the exception.
4. A supplier payment must name the approved supplier. Wrong supplier, excessive item amount or missing evidence must be blocked.
5. For an **Asset purchase and handover**, record the supplier payment, then switch to Officer and record recipient, date, item/quantity/identifier details and handover acknowledgement.
6. Try finalizing or recording business start before the handover. Both must fail. Complete the handover and try again.

Restricted Qard funds permit repayable business support only; they cannot pay grants. Education or other incompatible restricted funds cannot fund these cases. New generic Project expenses cannot use Small Business restricted funds. Existing welfare livelihood assistance and historical expense entries remain intact.

### H. Start, monitor and close

1. As Officer, use **Confirm business started** to enter the actual date and evidence. Funding alone must not increase the businesses-started count.
2. Record a monitoring visit: date, running/paused/stopped condition, findings, actions, optional observed sales/expenses and next review date.
3. Set the next review date to today. Confirm a monitoring reminder appears. Run `php artisan business:refresh-reminders` twice; there should still be one reminder per case and no new payment.
4. For future reminders, keep `php artisan schedule:work` running. Business reminders refresh at **08:10 Asia/Karachi** daily.
5. Mark a visit as a **Final report**. This requests closure; it does not close the case automatically.
6. Try closing a repayable case with outstanding principal. It must fail even if the business has stopped.
7. After funding is finalized and principal is settled, use a different authorized closure account to accept the final report and close the case.
8. Archive the closed case. Use the archived list to reopen its retained history and private files.

Do not change your computer clock to test missed installments. The automated suite simulates future dates. An unpaid installment becomes overdue after its due day has passed, not at the start of its due day.

### I. Corrections and access checks

1. Use an account with `business.void`. Enter a reason when voiding an incorrect repayment. Outstanding principal and installment balances should increase again.
2. Try repeating the same void: no extra balance movement should occur.
3. Try voiding a funding release while posted repayments exist: blocked. Void repayments first.
4. After a release is voided, funding reopens and the old final schedule is superseded. Re-finalize the actual remaining releases with a newly signed final statement before accepting repayments.
5. If recovered money has already been spent, a repayment void that would overdraw the fund must fail.
6. Open a stale payment/finalization form, change the case in another tab, then submit the old form. It should request a reload, not apply stale amounts.
7. Auditor / Reviewer can view and report but must not see change controls. A user without `business.view` must receive Access Denied for a copied business or document URL.
8. A document URL from case A must not download when substituted into case B's URL.

### J. Reports and links

1. Open **Small Businesses → Reports**, choose a date range and optionally a fund/type.
2. Verify released/recovered amounts, principal outstanding, overdue amounts, actual businesses started and the funding queue against case history.
3. Include today's date as the end date and confirm today's payments are included.
4. Inspect **Current shared fund capital**: ledger balance and commitments are different figures. Returned principal is already included in the balance.
5. Download Excel. It is an actual `.xlsx` workbook with business rows, pooled capital and report notes. Money is exact decimal text; names cannot execute spreadsheet formulas.
6. Use **Print / Save PDF** and your browser's Save as PDF option.
7. Open a beneficiary's history, the business dashboard card, the fund allocation list, and a posted transaction. Check their business links and totals. Access remains permission-controlled.

## Automated verification

```text
php artisan test --compact --filter=Business
php artisan test --compact
node --test tests/Support/money-preview.test.cjs
composer audit
```

The PHP suite uses in-memory SQLite. Race tests use guarded temporary SQLite databases with independent PHP processes; they never use your live database. Scenarios cover identical request keys, competing different keys, two cases sharing one fund, repayment caps and release versus finalization races.

SQLite coverage does not certify MySQL/PostgreSQL behavior. Run the migration and concurrency suite on the chosen target engine before switching databases. Backup and restore must include private evidence as well as database records.

## Implementation notes

- Domain: `app/Domain/Business`. Route definitions: `routes/business.php`. Config: `config/business.php`.
- Fourteen new tables preserve submitted revisions, budgets, decisions, signed terms, documents, payments, allocations, monitoring and audit events.
- Money is validated as decimal text and calculated in integer paisa. The shared schedule helper is also used by standalone Qard.
- Requests use optimistic case versions; financial actions also lock cases and funds and post atomically. An identical idempotency key returns the original posted movement; a changed or voided request key is rejected.
- Approval creates a reporting commitment, not guaranteed cash reservation. Actual releases require sufficient ledger balance. Negative availability after commitments is shown rather than hidden.
- Private files are kept outside public storage, checked for permitted type/extension/size and downloaded through authorized case-scoped routes. File hashes and signed-term snapshots are retained.
- Normal HTTP actions cannot edit or delete posted movements. Authorized voids retain both source and ledger history. Raw database administrator access remains outside application enforcement.
- `finance:sync-transactions` recognizes Small Business movements and reports missing/mismatched links without manufacturing a second business payment. It can change legacy finance sources; it is not a read-only audit.
- The number of required price checks and consents is configurable in `config/business.php`, with a minimum of two. Persistent organization-wide Settings remains Phase 15.
- Earlier Phase 1–6 documents are dated delivery records. Their references to future Small Business work and generic Small Business Project expenses are superseded by this guide.

## Roadmap after Phase 7

| Phase | Deliverable |
|---|---|
| 8 | Projects: proposals, budgets, approvals, implementation, monitoring and closure |
| 9 | Procurement: requests, quotations, approval, receipt and authorized payment |
| 10 | General Audit Trail viewer for sensitive actions |
| 11 | Documents library and security hardening, including standalone Qard witnesses |
| 12 | Records Reports, remaining dashboard widgets, global search and notifications |
| 13 | Cleanup, integration checks and deployment readiness |
| 14 | Tasks and Meetings, including minutes, voting and action items |
| 15 | Persistent organization Settings and supported operational preferences |

Each phase receives a detailed business-rule plan before implementation. Client specification phase numbering is broader than this delivery roadmap; the mapping is in `client-requirements-checklist.md`.

## Local activation and verification record

- Activated on **23 October 2026** with the additive migration and `BusinessRoleSeeder`.
- Backup: `storage/app/backups/before-phase7-activation-20261023-144356.sqlite` (local and excluded from Git). Backup and live SQLite integrity checks passed; no foreign-key violations.
- Verified **32 existing non-configuration data tables unchanged**, including all **9 users** and their password hashes. Added **14 empty tables**. No sample business categories, applications or payments were added. Normal browser sign-in subsequently updated the administrator's login/activity timestamps.
- Current configuration: **14 roles**, **72 permissions**. Existing role permissions were retained.
- Full PHP suite: **270 tests passed, 2,219 assertions**. Business subset: **24 tests**, including five separate-process race scenarios. Existing Welfare, Qard and Finance regressions passed.
- JavaScript money preview: **10 assertions passed**. All Blade templates compiled; all **32 business routes** registered.
- Composer audit: **no security vulnerability advisories found** after compatible updates to Laravel, CommonMark, Flysystem and the affected Symfony packages. PhpSpreadsheet provides the new XLSX export.
- Browser checks: live list, application form, repayment-field toggle, adding budget items, dashboard summary and report page. Populated approval/payment/closure screens and export content were exercised by isolated automated HTTP tests. No live business test records were saved.
- The scheduler must still be run by the local terminal/server scheduler. No operating-system scheduled task was installed.
