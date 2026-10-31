# Finance and Welfare PDF / Excel exports

## What is available

The existing Foundation Register design now covers these twelve reports. Each menu downloads a real PDF or a real `.xlsx` workbook, using the foundation logo, green masthead, summary cards, generated time in Asia/Karachi, readable column headings and alternating row colors.

| Section | Export | Required existing permission |
| --- | --- | --- |
| Finance → Donations | Donation register | `finance.view` |
| Finance → Funds | Fund balances and movement breakdown | `finance.view` |
| Finance → Expenses | Generic expenses, Project Payments and Office Procurement Payments | `finance.view` |
| Finance → Restricted Funds | Active restricted/designated fund balances and movement breakdown | `finance.view` |
| Finance → Transactions | Transaction ledger and per-fund running balances | `finance.view` |
| Finance → Finance Reports | Posted transactions and current fund balances | `finance.reports.view` |
| Welfare → Beneficiaries | Beneficiary register | `welfare.view` |
| Welfare → Applications | Application register | `welfare.assistance.view` |
| Welfare → Field Verification | Verification queue register | `welfare.view` |
| Welfare → Assistance | Assistance register and payment history | `welfare.assistance.view` |
| Welfare → Reviews and Follow Ups | Current approved-assistance review queue | `welfare.assistance.view` and `welfare.reviews.view` |
| Welfare → Applications → Agreement register | Welfare agreement register | `welfare.agreements.view` |

The agreement detail screen also exports its application's register entry. This is a register, not a newly signed legal agreement. Existing printable agreements and protected signed-copy downloads retain their own workflows.

## Simple testing guide

Use a test installation and an account allowed to open the section you are testing.

1. Sign in. Open **Finance → Donations**.
2. Click **Export**, then **Download PDF**. Open the downloaded file. Check the foundation logo, green heading, donor rows, PKR amounts and page numbers.
3. Click **Export**, then **Download Excel (.xlsx)**. Open it in Excel or LibreOffice. Check that the file opens without a repair warning. Amounts should be numbers with two decimal places. Dates should be spreadsheet dates; phone and record numbers should stay text.
4. Apply a search or status filter on the page and submit it. Download both files again. They should contain all matching records, including matches on later pages. The scope line identifies the filters; search text itself is not printed.
5. Repeat steps 2–4 for every row in the table above. On Applications, the ordinary **Export** menu exports applications; **Agreement register** exports agreements. Application filters do not silently carry over to the separate agreement register.
6. Open a Welfare agreement, then use **Export agreement register**. It should include only that application's agreement.
7. Choose a search that matches nothing. The PDF should say **No matching records**. The workbook should remain valid, with zero counts and no formula errors.
8. On a phone-sized window, open Export, select a format and check that the menu is usable. Escape or clicking outside closes the menu.
9. Sign in using an account without the relevant permission. Its export menu should be absent and a directly copied export URL must return access denied. Agreement and review permissions are checked separately.

### Finance amounts to check

1. For Donations, compare **Recorded amount** with donation records and **Posted credits** with their authoritative posted transactions. An unposted record must not increase posted credits. Anonymous donors should appear as **Anonymous**.
2. For Funds, compare **Ledger cash** with the fund's opening balance plus posted credits minus posted debits. **Unpaid commitments** are separate. **After commitments** equals cash less commitments and may be negative. The second sheet/PDF section separates donations, spending, recoveries and refunds.
3. For Expenses, inspect all three source groups. A project procurement payment must appear through its project movement once, rather than again as an office payment. Requested/recorded amounts can include pending or void records; **Posted debits** includes only authoritative posted debits.
4. For Transactions, choose one fund and a date range. An entry's running balance includes that fund's earlier movements outside the selected range. Pending and void transactions do not change ledger cash.
5. For Finance Reports, select a fund and dates. Export all matching posted transactions, even if the screen only previews twenty. The current-fund-balances section is explicitly current; it is not a historical period-end balance. Net movement is credits minus debits and excludes opening balances.
6. Exporting must not create a donation, payment or ledger entry. A successful export creates only a sanitized audit event containing its format and record count.

### Welfare amounts and privacy to check

1. In a test case approved for **PKR 500**, record a posted payment of **PKR 10.25** through the existing authorized workflow.
2. Export Assistance. The case must show **500.00 approved**, **10.25 posted paid** and **489.75 remaining**. Check the payment-history sheet/section and its ledger reference.
3. If you void a payment through the authorized workflow, the history retains the voided row. Paid totals must exclude it and remaining authority must follow the existing payment service.
4. Beneficiaries export includes operational register metadata, including phone numbers for authorized users. CNICs, household narratives and private evidence are excluded. Complete CNIC/phone searches still use the existing exact encrypted lookup; names and record references support partial searches.
5. Verification and review exports show queue metadata and dates, not private findings, medical narratives, evidence files or review notes. Review queue dates follow Asia/Karachi time.

## Implementation and limits

- `FinanceWelfareQuery` supplies the list and export queries so filters do not drift between them. Existing URL and permission contracts are retained.
- `FinanceWelfareReport` builds financial and operational register sections using the existing Money, fund reporting and welfare payment services. It does not change financial workflows.
- `DirectoryExport` retains its existing organization output and accepts financial metrics, notes and the dedicated financial workbook reference. The original Foundation Register reference remains unchanged.
- XLSX files contain native numeric amounts, dates, text-safe identifiers, frozen headings, bounded summary formulas and cached calculations. User-supplied text is not evaluated as an Excel formula. Additional sections have their own sheets.
- PDFs use the existing branded header/footer and repeat table headings on continued pages. Sections with different source groups are separated clearly.
- There is no silent pagination truncation. Up to 2,500 source records and 10,000 total detail rows are supported; larger requests require narrower filters. Detail queries also use the source-record bound. Downloads are limited to twenty requests per minute per throttle identity.
- Both formats use attachment responses with their genuine MIME types, private/no-store caching and `nosniff`. Authorization is checked at both routing and controller boundaries.
- The new blank reusable reference is `resources/export-templates/foundation-finance-welfare.xlsx`. A personal Codex template named **Foundation Finance and Welfare** was also created from that reference. Personal template installation is separate from the deployed Laravel application.

## Verification performed

- Full regression suite: **439 tests, 3,774 assertions passed**.
- New export feature coverage: 39 tests / 470 assertions, plus one real Project/office Procurement integration test / 16 assertions. These are included in the full-suite count above.
- Existing organization export coverage: 16 tests / 171 assertions, also included above.
- Populated MySQL QA browser run: **28 checks passed**, including clicks that downloaded all **24 files** from the real menus, filter preservation, loaded styles/logo and a mobile menu kept within the viewport.
- Independent file checks compared each exported count with its QA database source, validated workbook formulas/logos, parsed PDFs and reconciled ledger credit/debit totals. Rendered all **24 PDF pages** and reviewed all **18 workbook sheets** plus the blank template preview.
- QA used an isolated populated test database; no hosted or working financial data was reset. Generated QA samples, logs and local credentials remain outside tracked application files.

Automated checks are in `tests/Feature/FinanceWelfareExportTest.php` and `tests/Feature/FinanceWelfareIntegrationExportTest.php`. The optional browser helper `scripts/qa-finance-welfare-exports.cjs` deliberately requires the local synthetic-QA receipt and local QA server; it is not a hosted-production test script.

## Deploying the code to Alwaysdata

These changes require no schema migration, data import, key change or database reset. They introduce no new Composer dependency. Publishing to GitHub and updating the hosted checkout are separate actions.

After the commits are published, use SSH and the existing production configuration:

```bash
cd /home/ngosystem/www/foundation
export PHP_VERSION=8.4
git pull --ff-only origin feature/complete-ui-redesign
php artisan view:clear
php artisan config:cache
```

The financial XLSX reference must be present at `resources/export-templates/foundation-finance-welfare.xlsx`; it is part of the tracked changes. Keep the existing `.env`, application/lookup keys, uploaded files, database and private storage permissions. No seeding or reset command belongs in this deployment. Finally, sign in on the hosted site and download one Finance PDF/XLSX and one Welfare PDF/XLSX using the guide above.
