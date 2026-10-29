# Realistic-data application testing

This follow-up fixes the defects found in the original 29 October review and supplies a populated, isolated MySQL application for hands-on testing. All people, contact details, donations and evidence in this environment are synthetic. They represent no actual payment, client, donor or signature.

## Open the populated test application

1. Open **http://127.0.0.1:8011/login**.
2. Enter **qa.admin@example.test**.
3. Enter **QA-test-only-2026!** as the password.
4. Click **Sign in**. The dashboard should show actual sample funds, one active project, upcoming tasks and recent activity.

The ordinary application on port **8000** still uses `foundation_management`. The sample application uses `foundation_management_test_20261029145718_835f` and separate upload storage and session cookies. Its database name is retained in the ignored `tmp/mysql-test-target.json` receipt. No working users were reset and no synthetic accounts or funds were added to the working database.

If port 8011 has stopped, open a terminal in this project and run:

```powershell
php -S 127.0.0.1:8011 -t public scripts/qa-browser-router.php
```

Keep that terminal running. The router refuses the working database and binds this environment to the local test port.

## Accounts for independent actions

All accounts below exist **only in the sample application**. Each uses **QA-test-only-2026!**. To switch, click **Log out** at the bottom of the sidebar, then sign in with the next account.

| Person | Email | Main duties |
|---|---|---|
| QA administrator | qa.admin@example.test | Administration, role settings and all module access; separation rules still apply |
| Amina Rahman | amina.rahman@example.test | Applications, field verification, program preparation and follow-ups |
| Hamza Farooq | hamza.farooq@example.test | Welfare/Qard implementation recommendation |
| Faisal Mahmood | faisal.mahmood@example.test | Chief Coordinator recommendation for Business, Projects and Procurement |
| Sadia Khalid | sadia.khalid@example.test | General Secretary review and authorized Welfare agreement preparation |
| Naveed Aslam | naveed.aslam@example.test | Final committee decisions and beneficiary eligibility approval |
| Hassan Raza | hassan.raza@example.test | Treasurer: disbursements, payments, recoveries and supported refunds |
| Bilal Ahmed | bilal.ahmed@example.test | Independent Procurement receiving |
| Zara Iqbal | zara.iqbal@example.test | Records Officer: organization documents and sharing |
| Maryam Shah | maryam.shah@example.test | Auditor / Reviewer: authorized read-only reports, audit and shared documents |
| Usman Tariq | usman.tariq@example.test | Viewer: limited read-only access |

Amina has several operational roles so the same sample officer can prepare cases across modules. Approval and finance actors remain separate. Two active committee members, Shahid Hussain and Rubina Akhtar, supply distinct committee consents where required. They are not the payer or receiver.

## What was repaired

| Finding | Correction |
|---|---|
| Guest requests could create Members | Members require login and the appropriate view/create/update/delete permission |
| Ordinary users could change role permissions | Both role administration routes require `roles.manage`; sensitive updates retain password confirmation |
| Committee actions lacked consistent access checks | Committee viewing, editing and membership actions require their corresponding permissions |
| Settings preview was publicly accessible | It now requires authentication |
| Typing a Member name raised a JavaScript error | Optional preview elements are checked before use |
| Searching a complete multi-word Member name found no result | Names match across first/last-name tokens, including extra spaces; encrypted phone/CNIC lookups stay exact |
| A fresh role seed omitted the final-decision role and Welfare workflow permissions | The main seeder includes the workflow/agreement seeders in dependency order and declares the missing baseline permissions |
| Records Reports showed zero started businesses | The metric uses the actual recorded start date, including businesses subsequently paused |
| Reusing an upload object during payment replay could delete retained evidence | Business, Project and Procurement upload staging generates a new UUID path for every attempt; replay cleanup only removes its own staged copy |

## Starting sample records

The completed runs leave 12 users, 7 members, 4 committees, 8 beneficiaries, 7 funds, 7 donations, 2 generic expenses and 20 ledger transactions. Programs include two Welfare applications, one assistance case, two Qard loans, one Business case, one Project and one Procurement order. There are six tasks, four meetings, 36 registered documents and ten retained reports covering all eight report types.

Counts will increase when you create records. Audit counts also increase during normal login, logout, exports and sensitive actions. Record-number gaps are expected after rolled-back tests; they do not indicate missing money.

## Step-by-step checks

### 1. Login, logout, roles and account security

1. Sign in as the QA administrator. Check that the header and sidebar display the signed-in identity.
2. Open **Users & Roles**. Open a user and inspect the assigned roles. Open **Manage Roles** to inspect separate approval and finance permissions.
3. Click **Add User**. Enter a fictional name, an `@example.test` email and a password of at least 12 characters. Confirm your current password if prompted. Choose Viewer and save.
4. Log out. Sign in as **Usman Tariq**. Members and authorized read-only pages should open. Add/Edit administration controls should not be available.
5. Try `/users/roles`, `/members/create`, `/committees/create`, `/finance/funds/create` and `/records/audit` directly. Expect **403 Forbidden** for this Viewer.
6. Open **Account Security** using a sample account. A password shorter than 12 characters must fail. A valid password change must require the current password. Use only sample credentials when trying recovery or authenticator enrollment.
7. Log out and confirm the application returns to Login. Protected pages must require a fresh login.

Automated security tests also exercise two-factor challenges/recovery codes, throttling, account status, session expiry/revocation and administrator-assisted recovery. A physical authenticator device was not enrolled during this browser run.

### 2. Members and committees

1. Sign in as administrator. Open **Members** and search **Shahid Hussain**. The full name must return a matching row.
2. Search with extra spaces between the words. The same member must remain findable.
3. Click **Add Member**, enter a fictional full name and contact details, and save. Typing must not produce a JavaScript error; the saved detail page must show the name.
4. Open **Committees**. Inspect Community Implementation Committee and Grants and Finance Decision Committee.
5. Add a new committee. Assign a sample member, change their position, and save. Refresh to verify that the membership is retained.
6. Use Archive only on a newly created disposable committee or user. Do not archive the approval actors if you still want to test the sample workflows.

### 3. Funds, donations, expenses and transactions

1. Open **Funds**. Inspect the cash balance and unpaid commitments separately.
2. Before making more payments, these sample totals should agree:

| Fund | Ledger cash (PKR) | Unpaid commitments (PKR) |
|---|---:|---:|
| General Operations Fund | 232,699.75 | 11,999.75 |
| School Continuity Fund | 82,499.75 | 10,999.75 |
| Family Welfare Fund | 125,000.00 | 0.00 |
| Revolving Livelihood Qard Fund | 56,000.00 | 0.00 |
| Enterprise Development Fund | 115,000.00 | 0.00 |
| Community Infrastructure Fund | 280,999.75 | 45,999.75 |

3. The additional Emergency Community Support fund from browser entry contains PKR 1,010.25: PKR 1,000.00 opening balance plus Huma Saeed's PKR 10.25 donation.
4. Open **Donations**. Inspect the fictional donors and their fund links. Add a new sample donation to an eligible fund.
5. Open **Transactions**. Find that donation and confirm it has exactly one posted credit. Open its source record.
6. Open **Expenses**. The office internet and field transport services are paid. Their approval/paid history must correspond to one debit each.
7. New goods/assets must use Procurement. New Project expenditure must use the approved Project workflow. A restricted fund must reject incompatible spending.
8. Open **Restricted Funds** and **Finance Reports**. Check that recoveries and refunds are shown separately and do not count twice as donations.

### 4. Welfare, verification, agreement and follow-up

1. Open **Beneficiaries**. Find **Zainab Bibi**. Her eligibility is verified and approved.
2. Open her education application in **Applications**. Inspect the completed field verification and three independent decision actors.
3. Open the agreement. Both sample evidence files must download privately. They are visibly labelled synthetic and are not real signatures.
4. Open the linked assistance. Approved total: **PKR 18,500.00**. Paid: **PKR 7,500.25**. Remaining: **PKR 10,999.75**.
5. Try to record **PKR 18,500.00** again. Expect a validation message and no extra ledger debit.
6. A payment within the remaining authorization should succeed and reduce both the remaining allowance and fund cash by exactly that amount.
7. Open **Reviews & Follow-ups**. Inspect the school acknowledgment follow-up. Record a new dated review with notes and a future review date.
8. Open **Noreen Akram's** other Welfare application. It remains submitted, giving the verification queue unfinished work to process.

For a new Welfare case: Amina submits/verifies, Hamza recommends, Sadia reviews, Naveed decides, and an authorized person other than the submitter prepares/verifies the agreement. Administrator access does not bypass that agreement independence rule.

### 5. Qard-e-Hasana

1. Open the **Imran Latif** loan. Approved/disbursed principal is **PKR 500.00**.
2. Its history contains **PKR 10.25** and **PKR 489.75** recoveries. Outstanding principal is **zero**, and status is **Repaid**.
3. Open **Asif Mehmood's** loan. Principal is **PKR 36,000.00**, recovered is **PKR 12,000.00**, and outstanding is **PKR 24,000.00**.
4. Inspect monthly installment allocations, two witnesses, the private signed-copy samples and follow-up notes.
5. As Treasurer, try a repayment above the outstanding principal. It must fail without creating a credit.
6. Record an allowed repayment. It must credit the original Qard fund once and allocate against the oldest unpaid installment first.

The automated and populated-data checks separately verified the exact PKR 500 → 10.25 → reject 500 → accept 489.75 scenario and duplicate-submission handling.

### 6. Small Business

1. Open **Sana Home Stitching** in Small Businesses.
2. Inspect viability findings, the Chief Coordinator/Secretary/Committee decisions, contributions and signed terms.
3. Approved foundation funding is **PKR 40,000.00**. Released funding is **PKR 40,000.00**. Funding is finalized.
4. Principal recovered is **PKR 5,000.00** and outstanding is **PKR 35,000.00**.
5. Inspect the recorded business start, monitoring findings and next review date. Business cash support uses the approved cash-release workflow; supplier/assets use Procurement and applicable handover controls.
6. Generate a fresh Business report. **Businesses started** must be **1**. Retained snapshots generated before the reporting fix preserve their original values; use the newest report to test the corrected calculation.

### 7. Projects

1. Open **Kasso School Access Path Repair**.
2. Inspect the approved **PKR 65,000.00** labor budget and responsible implementation committee.
3. Status is **In Progress**, with **35%** reported completion and a next review date.
4. Gross payments are **PKR 20,000.25**, actual refunds **PKR 1,000.00**, and net expenditure **PKR 19,000.25**.
5. Remaining budget is **PKR 45,999.75**. The refund restores authority to the same item and cash to the same fund.
6. As Treasurer, try an amount above the remaining item budget. It must fail. Approval and monitoring alone must not create debits.
7. When trying amendments, expect payments to pause pending independent approval. Final closure requires settled obligations; it blocks further payments and releases unused commitments.

### 8. Procurement

1. Open **Community office visitor seating**.
2. Inspect two distinct quotations, the independent approval chain and the issued purchase order.
3. Four chairs at **PKR 5,500.00** give an approved total of **PKR 22,000.00**.
4. Inspect Bilal Ahmed's independent receiving record, four accepted chairs and invoice **KOF-2026-1042**.
5. Paid is **PKR 10,000.25** and the invoiced unpaid balance is **PKR 11,999.75**.
6. As Treasurer, try paying more than that unpaid balance. It must fail without a ledger entry.
7. A valid partial payment must create one Procurement movement and one authoritative ledger debit.
8. Open **Procurement Rules** as administrator. Its quotation policy is live and persistent. Project- and Business-linked purchasing are additionally covered by automated workflow tests.

### 9. Tasks and meetings

1. Open **Tasks**. Inspect a completed school acknowledgment task, a cash reconciliation task in progress, and pending field/project tasks.
2. Add a task, assign an active user, set a due date and save. Log in as that assignee and add a progress comment.
3. Try completing a task at 90%. Expect rejection. At 100%, completing should succeed and retain completion notes.
4. Open **October community coordination review** under Meetings. It has finalized minutes, attendance meeting quorum, and an assigned follow-up task.
5. Open a scheduled planning meeting. Record attendance and minutes. Fewer attendees than quorum must fail; sufficient attendance must create the specified action items once.

### 10. Documents, Reports and Audit Trail

1. Open **Documents**. Filter by source module or category. Open a linked agreement, receipt or quotation and download its retained version.
2. Sign in as Zara. Upload a sample organization PDF. It starts private to its owner and administrators. Confirm the current password before sharing it with selected roles.
3. Share it with Auditor / Reviewer. Maryam should see it. Usman should not see its metadata or download it through a guessed URL.
4. Add a new version of an eligible standalone file. The original must remain. Linked signed/financial evidence cannot be replaced through the library.
5. Open **Records Reports** and generate any authorized type. Download XLSX and open Print / Save PDF. Values must match the retained on-screen snapshot.
6. Open **Audit Trail** as administrator or Maryam. Filter by module, actor, outcome or reference. Open a payment event and follow its authorized source link.
7. Export a filtered audit report. Ordinary Viewers cannot open Audit Trail, and audit permission does not bypass private-source access.

### 11. Dashboard, search, notifications and mobile layout

1. Open Dashboard. The active-project count is **1**. Fund bars show actual ledger cash, while commitments remain on fund detail pages.
2. Search **School Access** globally. The sample project should appear. Search the complete sample phone **03000000200** to find Zainab Bibi.
3. Repeat as Viewer. Only authorized result types should appear.
4. Open Notifications. Mark an item read, dismiss it, filter Dismissed, then restore it. These actions must not issue payments or change approval stages.
5. Use the theme button next to Notifications. Reload; the choice must persist. Members, Committees, Finance, Welfare, Programs, Operations and Records should retain readable dark surfaces.
6. At a narrow/mobile browser width, open the menu, navigate, close it, and scroll wide tables inside their container.

### 12. Settings boundary

General **Settings remains a preview**. Organization-wide settings persistence is still deferred; it is not presented as a successful backend test. The separate Procurement Rules page does persist its supported policy. Physical mobile/tunnel-provider access, production deployment and malware scanning were not certified by this run.

## Verification and repeatability

The final evidence is retained locally in ignored `tmp/qa-final-full-tests.txt`, `tmp/qa-fixed-mysql-tests.txt`, `tmp/qa-browser-workflows.json`, `tmp/qa-populated-browser.json`, `tmp/qa-populated-verification.json`, `tmp/qa-exports/`, and the dashboard/mobile screenshots.

- Full feature regression: **383 tests**, with added negative authorization, seeding and report assertions.
- Selected MySQL financial workflows: **42 tests / 327 assertions**.
- Browser entry: **14 checks**, followed by **70 populated checks across 59 screens** with no page/JavaScript errors.
- Populated scenario construction: **42 assertions**, using validated controllers and domain services for workflow/payment operations.
- **11 XLSX files** parsed with PhpSpreadsheet and report metrics compared against their retained snapshots.
- **36 available document versions** checked for original-byte checksums. A synthetic fixture deleted by the discovered replay edge case was restored from byte-identical test bytes; no real document or financial record was altered. Replays are rechecked after the path-uniqueness fix.

The scripted checks verify initial fixture totals. If you deliberately add payments or edit sample records, those starting-total assertions may correctly fail; prepare a new disposable QA schema to repeat the original acceptance scenario.

To build another isolated sample environment, first stop the port-8011 server, then:

```powershell
php scripts/prepare-mysql-tests.php
```

For transactional MySQL tests, use the exact new receipt and a separate test-storage token:

```powershell
$qaTarget = Get-Content tmp/mysql-test-target.json -Raw | ConvertFrom-Json
$env:DB_DATABASE = $qaTarget.database
$env:TEST_TOKEN = 'mysql-qa'
php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml
Remove-Item Env:DB_DATABASE
Remove-Item Env:TEST_TOKEN
```

Only after those tests finish, populate the schema:

```powershell
php scripts/qa-browser-seed.php
php scripts/qa-realistic-data.php
php scripts/qa-verify-populated.php
php -S 127.0.0.1:8011 -t public scripts/qa-browser-router.php
```

`qa-realistic-data.php` refuses the working schema and does nothing when its receipt already marks the scenarios complete. It does not reset databases. Browser scripts require Playwright and an installed Chrome executable supplied through `UI_BROWSER_PATH`. The MySQL PHPUnit guard rejects a populated browser schema; use a newly prepared one for another transactional test run. Use separate `TEST_TOKEN` values if running suites simultaneously so their fake upload directories cannot interfere.
