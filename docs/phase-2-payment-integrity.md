# Phase 2: payment and fund integrity

## Delivered

- Welfare payments use the existing controller, permissions, form requests, payment table, and `TransactionService`. The transactional workflow lives in `WelfareAssistancePaymentService`.
- An approved assistance can be paid only from its active authorized fund. Linked applications must remain approved, non-test, and covered by a signed agreement.
- Amounts and balance decisions use exact decimal strings and integer paisa. JSON clients must send amounts as strings, for example `"10.25"`. Inputs with more than two decimal places, scientific notation, or floats are rejected.
- Every new welfare payment must fit the available ledger balance, even when `prevent_negative_balance` is unchecked. For every frequency, the approved amount is a total cap: remaining approval = approved amount minus all posted payments. Weekly/monthly frequency never resets that cap. For example, PKR 500.00 approved minus PKR 10.25 paid leaves PKR 489.75. Voided payments do not count as paid. This follows the user's clarified rule; future recurring scheduling must respect the total authorization.
- One payment creates one debit transaction in the same database transaction. Existing unique payment-key and ledger-source constraints remain the final database guards.
- Reusing a request key with the same details returns the original payment, including after completion or voiding. Reusing it for different details or another assistance returns HTTP 409. A replacement after a void requires a new payment form/key.
- Payment and transaction numbers are assigned from their own inserted IDs, avoiding `MAX(id)+1` races.
- Posting locks assistance and fund records. Voiding locks assistance, payment, fund, and ledger records. SQLite obtains a write reservation before reading financial totals; MySQL uses row locks and current locking reads. Deadlock retries are bounded.
- Voiding keeps the payment and its original ledger entry, sets both to `void`, and records the actor, time, and reason on the payment. Posted-balance calculations then exclude that debit. It does not add a second compensating credit.
- Repeated voids do not alter the original audit or restore money again. A missing, archived, or inconsistent ledger entry blocks the void and requires reconciliation.
- Voiding a payment that leaves a fully paid assistance underpaid reopens it as approved and records a status-history entry. Posting, completion history, voiding, and reopening all roll back together on failure.
- Payment screens show the current available fund balance, remaining approved amount, exact amounts, and validation errors.

## Fund-purpose rules used in this phase

General funds without `restrict_spending` permit all welfare categories. Restricted/designated funds, and general funds with that flag enabled, use this mapping:

| Fund purpose | Permitted application categories |
| --- | --- |
| `general_welfare` | `welfare`, `education`, `emergency` |
| `education` | `education` |
| `emergency` | `emergency` |
| `small_business` | `livelihood` |
| `qard`, `community_development`, or an unknown purpose | No welfare payment; use the corresponding workflow or review the designation |

For older assistance without an application, `education_support` maps to education, `emergency_assistance` to emergency, and `livelihood_assistance` to livelihood. Monthly assistance, food/ration, medical assistance, one-time support, and general welfare map to welfare. Unknown types are blocked for purpose-restricted funds. No existing fund designation is rewritten automatically.

These are explicit defaults for the currently available categories. In particular, a medical assistance is not automatically treated as an emergency. Additional designation rules and the fund breakdown remain Phase 3 work.

## Shared ledger changes

- `TransactionService` locks the source and affected funds for create, sync, and void operations. Ordinary expense/donation adjustments now check their net effect while holding those locks, using their existing `prevent_negative_balance` setting. Welfare debits always require sufficient funds.
- Payment ledger entries cannot be independently edited or voided through the generic service. Corrections go through the payment void action.
- Creating an entry no longer silently restores an archived transaction. Generic donation/expense synchronization still supports its existing explicit restoration flow, subject to balance checks.
- Donation creation and its ledger posting are now atomic; a rejected posting cannot leave a recorded donation without its intended transaction.
- `FundBalanceService` returns fixed-scale decimal strings. SQLite financial reads preserve cents before PDO can shorten floating-point values. Wider report/controller float cleanup remains Phase 5.
- `finance:sync-transactions` now includes welfare payments in its reconciliation and detects even a one-paisa mismatch. It audits welfare links without silently recreating payment transactions. Its existing donation/expense synchronization still mutates those records; it is not a read-only command.

## Principal files

- `app/Domain/Welfare/Assistance/Services/WelfareAssistancePaymentService.php`
- `app/Http/Controllers/Welfare/WelfareAssistancePaymentController.php`
- `app/Http/Requests/Welfare/StoreWelfareAssistancePaymentRequest.php`
- `app/Domain/Finance/Transactions/Services/TransactionService.php`
- `app/Domain/Finance/Funds/Services/FundBalanceService.php`
- `app/Domain/Finance/Funds/Services/FundPurposeService.php`
- `app/Domain/Finance/Support/Money.php` and `ReadsDecimalMoney.php`
- Fund, transaction, and payment models; payment create/show views
- `app/Http/Controllers/Finance/DonationController.php`
- `app/Console/Commands/SyncFinancialTransactions.php`
- `tests/Feature/WelfarePaymentIntegrityTest.php`, `TransactionIntegrityTest.php`, and `WelfarePaymentConcurrencyTest.php`

## Database / deployment

No new migration or seeder is required for Phase 2. Phase 1 migrations must already be applied. No production or local application data was changed by these tests. Refresh the application to load the updated views/code; restart long-running application workers if used.

## Automated verification

Full suite result: **149 tests, 1143 assertions passed** on PHP 8.4 / SQLite.

Coverage includes insufficient balance (including the unchecked overdraft flag), purpose restrictions, inactive/archived funds, permissions, validation, exact cents and maximum supported amount, partial and recurring payments, idempotent retries/conflicts, database uniqueness, completion/reopening audit, corrupt/missing ledger entries, rollback on posting/void failure, donation atomicity, shared-ledger adjustments, reconciliation, and the earlier application/agreement/account tests.

Seven independent-process concurrency cases cover the same payment key, different keys against one assistance, the cumulative cap for monthly assistance, separate assistances sharing one fund, an expense racing a welfare payment, simultaneous voids, and a void racing a new payment. Tests use isolated in-memory or guarded temporary SQLite databases. Temporary concurrency databases use WAL and disabled durability syncing for speed; the application database settings are unchanged. MySQL locking behavior has not been integration-tested against a live MySQL server.

Run the suite from the project directory:

```powershell
php vendor/bin/phpunit
```

If Herd's normal PHP extensions slow or stall the run, the tested clean-runtime command on this workstation is:

```powershell
& 'C:\Users\Ziyad Usman Khan\.config\herd\bin\php84\php.exe' -n -d 'extension_dir=C:\Users\Ziyad Usman Khan\.config\herd\bin\php84\ext' -d extension=mbstring -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo -d extension=openssl vendor\bin\phpunit
```

## Manual checklist

Use an approved assistance created from a real signed application, not APP-00005 or APP-00010. Sign in with `welfare.assistance.view` and `welfare.assistance.manage`; use an authorized finance account to inspect fund/ledger pages.

1. Open Record Payment. Confirm the displayed available balance reflects posted credits/debits rather than only the opening balance, and the amount defaults to the smaller of remaining approval and available fund balance.
2. Record a partial payment, such as PKR 10.25. Confirm one payment and one matching debit, with the fund reduced by exactly PKR 10.25.
3. Resubmit that same form/request. Confirm the original payment is shown and no second debit appears.
4. Attempt a payment above the payable balance/approval, and attempt a purpose-mismatched restricted-fund payment. Confirm validation blocks posting and leaves balances unchanged.
5. Finish paying an assistance of any frequency. Confirm it becomes completed. Void one payment with a reason: confirm the original debit is void, its amount returns to the fund, the actor/reason/time are visible, and the assistance reopens as approved.
6. Submit the same void again. Confirm there is no further balance adjustment or audit overwrite. Open a new payment form if a replacement payment is needed.
7. Sign in with a view-only account. Confirm posting and voiding are unavailable and direct requests are denied.

Browser visual acceptance remains a manual check; the suite renders the payment pages and exercises the HTTP actions but does not drive a browser.

Phase 2 stops here. Phase 3 has not been started.
