# Phase 6 — Qard-e-Hasana testing guide

## What is available

Open **Programs → Qard-e-Hasana**.

**Draft → Submitted → Field verification → Implementation Committee → General Secretary → Decision Committee → Approved → Signed → Active → Repaid**

Applications use existing verified, approved beneficiaries and eligible funds. Rejected applications can be corrected and resubmitted. Agreements can be printed or saved as PDF; borrower and foundation signed copies are both required.

One full disbursement is allowed. Early and partial repayments are applied to the oldest unpaid installment. Recovered principal returns to the original fund immediately. Recycling shows those recoveries and does not create a second credit.

The module retains decisions, evidence, agreements, movements, installment allocations, follow-ups, and activity history. There is no interest, compulsory penalty, automatic payment, debt write-off, or repayment rescheduling.

## Installation on another copy

Run these commands from the application folder:

    php artisan migrate --force
    php artisan db:seed --class=QardRoleSeeder --force
    php artisan view:clear
    php artisan route:clear

The migration adds ten Qard tables. Existing business records are retained. The seeder adds permissions and role assignments without detaching other permissions. It does not create users or sample records.

Do not use migrate:fresh or a database-reset command to install this phase.

Daily in-app reminders run at **08:05 Asia/Karachi**. During local testing, keep this running in another terminal:

    php artisan schedule:work

To refresh Qard reminders immediately:

    php artisan qard:refresh-reminders

Reminders appear in the Qard list and loan details. The **Needs attention** filter shows unresolved reminders. Refreshing reminders does not issue payments, add fees, or change loan statuses.

## 1. Prepare five separate accounts

Sign in as Admin. Open **Users & Roles** and create these active accounts with different email addresses:

| Example name | Role | Main action |
|---|---|---|
| Qard Officer Test | Qard Officer | Applications, verification, agreements, follow-ups |
| Implementation Test | Implementation / Administration Committee | First recommendation |
| Secretary Test | General Secretary | Second review |
| Decision Test | Decision-Making Committee | Final decision |
| Treasurer Test | Treasurer | Disbursements and repayments |

The Implementation Committee role has the internal slug implementation-committee.

Use the existing Admin account for correction/void tests. Other users need the explicit **Void Qard Financial Movements** permission to void a movement.

**Important:** The application's creator cannot make any approval decision, even as Admin. The three decision stages also require different people. Log out and log in with the next account at each stage, or use separate browser profiles.

## 2. Prepare the borrower and fund

1. As Admin, open **Beneficiaries** and create **Qard Borrower Test**.
2. Complete its existing verification and approval steps.
3. Confirm the beneficiary is **Verified** and **Approved**.
4. Open **Funds**. Create **Qard Test Fund**, purpose **Qard**, status **Active**.
5. Give it an opening balance of **1,000.00**, OR receive a donation of **1,000.00** through Donations. Use one method, not both.
6. Open the fund and confirm its ledger balance is **PKR 1,000.00**.

Expected: No Qard loan or payment exists yet.

## 3. Create, edit, and submit an application

1. Log in as **Qard Officer Test**.
2. Open **Programs → Qard-e-Hasana → New application**.
3. Select **Qard Borrower Test** and **Qard Test Fund**.
4. Enter principal **500.00**, frequency **Monthly**, and **3** installments.
5. Choose a first repayment date at least one month in the future. This date includes any agreed grace period.
6. Enter a purpose, such as **Tools for work — test application**.
7. Click **Save draft** and write down the new QRD number.
8. Click **Edit draft**, change the purpose, and save. Check that the change appears.
9. Click **Submit for verification**.

Expected: Status is **Submitted**. No money has moved.

If the borrower is missing from the list, complete beneficiary verification and approval. The fund list only offers active unrestricted general funds and Qard-designated funds.

## 4. Verify and obtain three decisions

1. As Qard Officer, open the submitted loan.
2. Under **Field verification**, write findings and upload a small test PDF or image.
3. Click **Record verification**.
4. Log in as **Implementation Test**. Open the loan, choose **Accept and continue**, enter a reason, and click **Record decision**.
5. Log in as **Secretary Test** and accept the next decision.
6. Log in as **Decision Test** and accept the final decision.

Expected:

- Status becomes **Approved**.
- The three installments are **166.66**, **166.66**, and **166.68**: total **500.00**.
- The fund shows **500.00 allocated**, **1,000.00 ledger balance**, and **500.00 available after allocations**.
- No payment exists yet.

Negative checks: The creator cannot decide the application; one person cannot make two decisions; stages cannot be skipped. Choosing **Reject for correction** returns the application for editing. Resubmission starts a new revision requiring fresh verification and all decisions.

## 5. Print and sign the agreement

1. Log in as **Qard Officer Test**.
2. Open the approved loan and click **Print agreement**.
3. Check the borrower, principal, purpose, fund, and installment dates.
4. Click **Print / Save as PDF**. Choose **Save as PDF** in the browser's print dialog.
5. For testing, use clearly marked test copies. For real loans, obtain the actual required signatures.
6. Return to the loan and enter both signatory names.
7. Select a borrower signed copy and a foundation signed copy.
8. Click **Record both signatures**.

Expected: Status becomes **Signed**. Both files appear under **Private documents**. The ledger still has no Qard payment.

Allowed uploads: PDF, JPG, JPEG, PNG; maximum **10 MB each**. PHP upload_max_filesize and post_max_size must also allow the upload; both signed copies are sent together. Files are stored privately and downloaded through permission-checked routes.

Negative checks: Missing copies or unsupported file types prevent completion. An account without View Qard cannot download documents.

To change approved terms before disbursement, use **Need to change approved terms? → Request revision**. This releases the commitment and returns the application to draft. Obtain fresh approvals and signatures; the old evidence remains in history.

## 6. Disburse the principal

1. Log in as **Treasurer Test**.
2. Open the signed loan.
3. Under **Disburse approved principal**, confirm **500.00**.
4. Choose today's payment date and **Cash**, or another supported payment method.
5. Enter a reference or receipt if needed.
6. Click **Record disbursement**.
7. Inspect the movement history and Finance Transactions using an authorized account.

Expected:

- Status becomes **Active**.
- Outstanding disbursed principal is **500.00**.
- Exactly one Qard debit of **500.00** exists.
- The fund balance is **500.00** and undisbursed Qard allocation is **0.00**.
- Another disbursement is not available.

The payment date must be on/after signing and before the first installment. If terms expire before disbursement, revise and reapprove them, then sign again.

Unsigned loans, insufficient funds, incompatible fund designations, partial disbursement amounts, and unauthorized accounts must not create payments. Retrying the same successful request must not create a second debit.

## 7. Test 500 → 10.25 → 489.75

1. As Treasurer, click **Record repayment**.
2. Enter **10.25**, today's date, and a method. Save.
3. Confirm remaining principal is **489.75**.
4. Open **Record repayment** again and try **500.00**. It must be rejected.
5. Enter **489.75** instead and save.

| Point | Outstanding principal | Fund balance |
|---|---:|---:|
| After disbursement | 500.00 | 500.00 |
| After repayment of 10.25 | 489.75 | 510.25 |
| After repayment of 489.75 | 0.00 | 1,000.00 |

Expected: Status becomes **Repaid**, all installments are paid, and there are exactly three Qard ledger entries: one debit and two recovery credits. Fund recovery totals show **500.00** without counting it as a new donation.

There is no additional recycling payment. Record voluntary donations independently through Donations.

## 8. Test corrections and voids

1. As Admin, open the repaid loan.
2. Expand **Void movement** on the **489.75 repayment**.
3. Enter a correction reason and click **Void this movement**.
4. Confirm the loan is **Active**, outstanding principal is **489.75**, and fund balance is **510.25**.
5. Confirm the original movement and ledger entry remain visible as **Void**.
6. To reverse the disbursement, first void the remaining **10.25 repayment**.
7. Then void the **500.00 disbursement**.

Expected: Loan status returns to **Signed**, fund balance returns to **1,000.00**, and the **500.00** undisbursed commitment is restored.

Repeated void requests have no additional effect. Reversing a repayment is blocked when removing that recovered money would overdraw the fund.

## 9. Test follow-ups and reminders

1. Use an **Active** loan and log in as Qard Officer.
2. Under **Borrower follow-ups**, enter contact notes and set the next follow-up to today.
3. Save. Confirm **Follow-up is due** appears.
4. Record another follow-up with a future date. The reminder clears if no installment is overdue.
5. Run the reminder command twice and confirm reminders do not duplicate.

An installment becomes overdue after its unpaid due date has passed in Asia/Karachi. The automated tests simulate this using an isolated database. Do not change your computer clock or real records to test overdue dates.

## Automated checks

    php artisan test --compact --filter=Qard
    php artisan test --compact
    node --test tests/Support/money-preview.test.cjs

Tests use in-memory SQLite or dedicated temporary SQLite files. The concurrency checks run separate PHP workers for repeated requests, competing disbursements, same-fund spending, and repayment-cap races.

The existing finance:sync-transactions command now recognizes Qard movements. It reports missing/inconsistent Qard ledger entries without recreating them. It can synchronize other finance sources, so it is not a read-only audit command.

## Technical notes and limits

### Verification record — 22 October 2026

- Full PHP suite: **246 tests passed, 2,036 assertions**.
- Qard subset: **27 tests passed, 184 assertions**, including four separate-process concurrency scenarios.
- JavaScript money preview checks: **10 assertions passed**.
- Qard list and application form inspected in the browser; populated workflow screens exercised through automated HTTP tests.
- SQLite integrity check: OK; foreign-key violations: zero.
- Activation verified: 11 roles, 59 total permissions, 11 Qard permissions. One existing user remains; its identity and password hash match the activation backup. No sample funds, beneficiaries, transactions, or Qard loans were added.
- Verified activation backup: storage/app/backups/before-phase6-activation-20261022-002747.sqlite.

- New domain: app/Domain/Qard; routes: routes/qard.php; migration: 2026_10_22_000001_create_qard_module.
- Finance integration covers TransactionService, purpose controls, allocations, source links, fund lifecycle guards, and reconciliation.
- Posted Qard movements are immutable through the application; corrections require recorded void reasons.
- One original fund per loan. Inactive funds must be reactivated before new movements. Outstanding loans prevent archival of their fund.
- Commitments are reported separately from ledger cash. Approval does not reserve cash against all other workflows.
- At Phase 6 delivery, the global Audit Trail, Settings, Small Business, Projects, Operations, and Records remained for later phases. Small Business is now covered by `phase-7-small-business.md`; the remaining roadmap is maintained there.
- MySQL/PostgreSQL are not certified by these SQLite tests.
- The migration is reversible, but rollback removes Qard tables. Once real loans exist, preserve backups and use forward migrations instead of rolling back business data.
