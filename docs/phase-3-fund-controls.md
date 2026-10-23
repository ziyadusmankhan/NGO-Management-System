# Phase 3: fund purpose controls and reporting

## Delivered

- One shared purpose policy for expense and welfare debits. `TransactionService` checks the current source and fund while holding locks, so a controller or reconciliation command cannot skip the restriction.
- Fund detail, fund cards, the restricted-funds list, and the fund edit page use actual ledger/commitment totals with exact decimal arithmetic.
- Fund detail shows receipts, allocations, spending, recoveries, incoming refunds, outgoing refunds, current balance, and available money after allocations.
- The transaction chain includes opening balance, source links, transaction dates, actors, retained void/archived entries, payment void reasons, and running balances. Voided/archived entries remain visible but do not change the current balance.
- Fund creation, editing, and archival use `FundService`. Each successful mutation records its actor, timestamp, and before/after values in `fund_changes`, atomically with the mutation. A failed audit write rolls back the operation; saving unchanged values creates no duplicate audit row.
- Opening balance/date, fund type, purpose, spending restriction, and designation protection cannot be rewritten after any linked financial activity or application/assistance commitment, including archived activity. Create a separate correctly designated fund for new work.
- Names, descriptions and other permitted metadata remain editable. Existing custom purposes can be retained while editing metadata; unknown restricted purposes cannot authorize spending.
- Only funds with zero ledger balance and no unpaid approved welfare allocation can be archived. Archived fund history is accessible through the **Archived** status filter on Funds.
- Server permissions protect fund changes with `finance.funds.manage`; reading requires `finance.view`. Welfare source links additionally respect `welfare.assistance.view`.
- Missing checkbox fields correctly save as unchecked. Fundamental designation checks and audit history remain mandatory even when optional control flags are unchecked.

## Agreed reporting definitions

Only active, posted, non-archived ledger transactions affect totals. All amounts are PKR with two decimal places.

| Display | Calculation |
| --- | --- |
| Original Funding (Opening + Receipts) | Opening balance + posted credits other than recoveries and refunds |
| New Receipts | Posted credits other than `recovery` and `refund` |
| Spent | Posted debits other than `refund` |
| Recoveries Received | Posted `recovery` credits |
| Refunds Returned to Fund | Posted `refund` credits |
| Refunds Paid Out | Posted `refund` debits |
| Current Ledger Balance | Opening + receipts + recoveries + incoming refunds − spending − outgoing refunds |
| Allocated — Unpaid Approvals | Sum of `max(0, approved assistance amount − posted payments)` for non-archived assistance whose status is `approved` |
| Available After Allocations | Current ledger balance − allocated |

Allocation is an unpaid commitment, not another debit or a cash reservation. Pending, rejected, completed, and archived assistance do not contribute. Weekly/monthly assistance uses the same total approved cap; the cap does not reset with time. Negative availability is displayed as a shortfall rather than hidden at zero.

Paying an approved commitment reduces both the ledger balance and the unpaid allocation. Therefore, available money after allocations stays unchanged for that payment. Voiding reverses both effects once. A void is not recorded as a recovery or refund credit.

Running balances represent the retained ledger's current state in transaction-date/ID order, excluding entries that are currently void/archived. They are not snapshots of what the balance displayed before a later void.

Fund list headline totals cover active, non-archived funds. The restricted list covers active restricted/designated funds plus general funds with spending restrictions enabled. Recoveries and refunds may make a card's balance differ from opening + receipts − spending; open the detail page for the full breakdown.

## Purpose rules

General funds with spending restrictions disabled permit all existing expense/welfare purposes. Restricted and designated funds always enforce the following rules, even if their spending-restriction checkbox is unchecked. General funds with that checkbox enabled use the same rules.

| Fund purpose | Permitted expense categories | Permitted welfare application categories |
| --- | --- | --- |
| General welfare | Welfare, medical, education | Welfare, education, emergency |
| Education | Education | Education |
| Emergency | Medical, welfare | Emergency |
| Small business | None (Phase 7: use the business workflow) | Livelihood |
| Community development | Project | None |
| Qard-e-Hasana | None through generic expenses | None through welfare payments |
| Unknown/custom purpose | None until a rule is explicitly defined | None until a rule is explicitly defined |

These rules use the application's existing category vocabulary. They do not verify an individual project's future budget approval; that belongs to the project workflow phase. Qard disbursement/recovery requires its own upcoming workflow. Legacy welfare assistance retains the Phase 2 assistance-type mapping.

## Database and local deployment

New reversible migration:

`database/migrations/2026_10_18_000001_create_fund_changes_table.php`

It adds the fund change audit table with fund/user foreign keys and a fund/time index. It does not rewrite fund designations, balances, payments, or ledger history. Existing funds have no historical audit rows fabricated; audit capture begins with subsequent application changes.

The migration has already been applied to this local workspace. A verified online SQLite backup was created first:

`storage/app/backups/before-phase3-20261018-155431-10bf00.sqlite`

The backup directory is Git-ignored. Before/after row counts remained: 6 funds, 20 transactions, 5 assistance records, and 5 payments. No test fixtures were inserted into the local database.

For another environment, back up its database first, then run:

```powershell
php artisan migrate --path=database/migrations/2026_10_18_000001_create_fund_changes_table.php --force
```

Do not run `migrate:fresh` on application data. The migration's `down()` is covered by an isolated automated test; rolling it back after use would remove the captured fund audit history.

## Step-by-step manual test

Use a separate test copy of the application database for the following exercise. These steps intentionally create donations, approvals, payments and voids. Log in with accounts holding the relevant fund, donation, expense, welfare, and approval permissions. Use a new ordinary application rather than APP-00005 or APP-00010, whose Phase 1 test flags prevent assistance creation.

1. Open **Finance → Funds → Create Fund**. Name it `Phase 3 Education Check`, choose **Restricted**, purpose **Education Support**, opening balance **1,000.00**, and status **Active**. Keep spending restrictions, negative-balance prevention and designation protection enabled. Save.
   - Detail should show original funding, ledger balance and available after allocations of **1,000.00**; all movement/allocation totals should be **0.00**.
   - Fund Change History should show **Created**, your account and the timestamp.
2. Record a donation of **200.00** to that fund using the existing Donation screen.
   - New Receipts = **200.00**; Original Funding = **1,200.00**; Ledger Balance = **1,200.00**.
   - Complete Transaction History should include exactly one donation credit with working source and ledger links.
3. Create a new **education** welfare application using this fund and an approved amount of **500.00**. Complete the normal approval and real signed-agreement workflow, create its assistance, and approve the assistance using the authorized account.
   - Before assistance approval, its allocation is zero.
   - After approval: Allocated = **500.00**; Ledger Balance = **1,200.00**; Available After Allocations = **700.00**.
   - No payment or debit is created just by approving the assistance.
4. Open that assistance and record a **10.25** payment. Return to the fund.
   - Spent = **10.25**; Ledger Balance = **1,189.75**; Allocated = **489.75**; Available After Allocations = **700.00**.
   - The payment/source link should open the matching payment. The next payment maximum should be **489.75**.
5. Try recording **500.00** as the next payment. It must be rejected; totals and transaction count must not change. Then record **489.75**.
   - Total Spent = **500.00**; Ledger Balance = **700.00**; Allocated = **0.00**; Available After Allocations = **700.00**. Assistance becomes completed.
6. Open the **489.75** payment and void it with reason `Phase 3 verification`.
   - Assistance reopens as approved. Ledger Balance = **1,189.75**; Allocated = **489.75**; Spent = **10.25**; Available After Allocations = **700.00**.
   - The voided debit remains in the fund chain as excluded, with your name, reason and time. No extra credit appears; recovery/refund totals stay zero.
   - Refresh or revisit the voided payment: the amount must not be restored a second time.
7. Open **Finance → Expenses → Create Expense**. Choose the education fund, category **Medical**, amount **50.00**, and complete required fields. Submit.
   - The purpose mismatch must be rejected without a new ledger debit or balance change.
   - Change the category to **Education**, save, and approve with the authorized account. Supply the supporting-document information if the fund requires it.
   - Under the existing expense workflow, approval posts the debit: Spent = **60.25**; Ledger Balance = **1,139.75**; Allocated = **489.75**; Available After Allocations = **650.00**.
8. Open **Funds**, **Restricted Funds**, the fund detail and **Edit Fund**.
   - The fund's balances/allocations must agree on all four screens. Click its name from Restricted Funds to open the full breakdown.
9. In **Edit Fund**, change only its description and save.
   - Fund Change History should show your account, time, old description and new description.
   - Try changing purpose to **Community Development**, opening balance, or disabling spending restriction. Each attempt must fail once activity exists. Restore unchanged values when trying another edit.
10. Try **Archive** on this funded/allocated fund. It must fail. Create another fund with opening **0.00**, no linked assistance and no transactions; archive it.
    - Select **Archived** in the Funds status filter. Open the empty archived fund and confirm its history is retained and editing controls are absent.
11. Log out and sign in with a finance-view-only account.
    - Fund history remains readable; create/edit/archive actions are denied on the server. An account without finance access must not access these pages. A finance user without welfare view permission sees payment references without welfare source links.

### Recoveries and refunds

Phase 3 reports real ledger entries typed `recovery` and `refund`, with incoming/outgoing refunds separated. It does **not** introduce new recovery/refund entry screens. Do not substitute a donation or edit database rows merely to make those cards nonzero. The automatic tests exercise both refund directions, recoveries, voids and archived entries; their entry workflows will be provided in the relevant later phases.

The automated mixed-ledger example starts at **1,000.00**, receives **200.25**, spends **100.10**, recovers **25.35**, receives a refund of **5.15**, and pays out a refund of **10.05**. Its expected balance is **1,120.60**. Voided/archived credits do not inflate that figure.

## Automated tests

Final verification: **173 tests, 1,448 assertions passed**, including **24 Phase 3 tests** and the existing payment concurrency tests. `git diff --check` also passed. A read-only check against the local database rendered all four non-archived funds' detail/edit pages and both fund lists, with reporting balances matching the balance service. Application feature tests also exercise the routes. A visual browser review was not performed.

The Phase 3 tests are in `tests/Feature/FundControlsTest.php`. They run against isolated in-memory SQLite, covering calculations, large exact amounts, source links, shortfalls, payment/void allocation behavior, final settlement/reopening, purpose checks at HTTP and service layers, stale-source rereads, idempotent posting, permissions, validation, audit atomicity, protected designations, archived history, and reversible migration.

Run only Phase 3:

```powershell
php vendor/bin/phpunit tests/Feature/FundControlsTest.php
```

Run the complete regression suite:

```powershell
php vendor/bin/phpunit
```

If the default Herd runtime stalls while loading extensions, use the verified minimal runtime:

```powershell
& 'C:\Users\Ziyad Usman Khan\.config\herd\bin\php84\php.exe' -n -d 'extension_dir=C:\Users\Ziyad Usman Khan\.config\herd\bin\php84\ext' -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo -d extension=openssl vendor/bin/phpunit
```

The test bootstrap refuses to run against the application database. Existing concurrency tests use guarded disposable SQLite files. MySQL-specific concurrency was not exercised in this SQLite environment.

## Scope decisions

- Allocation means approved unpaid welfare assistance, as confirmed by the user. It is informational; payment checks still enforce cash balance and the total assistance cap. No additional cash-reservation system was introduced.
- Audits start with Phase 3 application mutations; a general audit viewer and audit coverage for other domains remain Phase 10.
- Recovery/refund posting screens, lending schedules, project approvals and broader finance float cleanup remain their planned phases.
- Existing financial records and previously incorrect purposes/payments were not silently rewritten.
- No open business-rule decision is required to use Phase 3 with the rules above.
