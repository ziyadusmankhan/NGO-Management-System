# Programs, Operations and Records exports; readable A4 PDFs

## Delivered screens

Use **Export → Download PDF / Download Excel (.xlsx)** on:

| Menu | Screen | Export access |
| --- | --- | --- |
| Programs | Qard-e-Hasana | `qard.view` |
| Programs | Small Business | `business.view` |
| Programs | Projects | `projects.view` |
| Operations | Tasks | `tasks.view` |
| Operations | Meetings | `meetings.view` |
| Operations | Procurement | `procurement.view`; linked parent visibility also applies |
| Records | Documents | `documents.view`; ownership, sharing and source permissions still apply |
| Records | Reports register | `reports.view` and `reports.generate`; report-type permissions also apply |
| Records | Audit Trail | `audit.view` and `audit.export` |

The Small Business, Project and Procurement financial-report screens also have **Download A4 PDF** and branded Excel buttons. These additionally require their existing `*.reports` permissions. A generated Records report has **Download A4 PDF** and **Download XLSX** for its retained snapshot. Generating or downloading a snapshot still requires access to its source report type.

## Layout and data rules

- All newly generated application PDF downloads use standard **A4 portrait: 210 × 297 mm**. Body text is normally **11 pt**. Wide registers use paired, labelled fields in record blocks; short tables retain a table layout. Header/footer and labels have their own smaller type sizes. More pages are used instead of shrinking record text to fit many columns.
- Existing Members, Committees, Users/Roles, Finance and Welfare PDF downloads use the same shared A4 layout. Their Excel layouts remain compatible with the Foundation Register design.
- Agreements, purchase orders and older browser print views share A4 print CSS. A print-only script converts wide on-screen tables into readable record blocks before printing and removes them afterward. Original agreement fields, signatures and stored evidence bytes remain unchanged.
- The saved Foundation Finance and Welfare workbook reference supplies the logo, green masthead, summary bands, styled tables and frozen headings. The retained personal template reference is unchanged.
- Exports include all matching records across pagination, with a 2,500-record limit for ordinary registers/reports and the existing 10,000-event limit for Audit. Excess requests are rejected rather than silently truncated. A download throttle applies.
- Filters on list export links reflect the submitted list filters. Financial-report form buttons submit the current form values, including date, fund and parent filters. Business report dates retain the domain service's current-month defaults.
- Standalone Qard exports cover standalone loans. The Qard screen's repayable-business supplement belongs to the separate Small Business export and is identified in the Qard export note.
- Qard disbursed, recovered and outstanding values use posted movements. Business principal and release balances use the existing business services. Project payments/refunds and commitments use the existing project services. Procurement preserves its authoritative parent movements. Exports do not create payments or ledger entries.
- Financial-report date filters apply to movements. Current budgets, status, unused approval and commitments are identified as current rather than historical period-end amounts. Business reports retain their shared fund-capital appendix. Procurement reports include accepted/returned quantities and refund receivables.
- Excel money uses numeric cells and two decimal places; procurement quantities use three decimals. Record numbers, names and other user text are explicit strings, preventing formula evaluation. Snapshot metric values are native numeric cells where applicable.
- Document exports contain only eligible metadata, never file contents, private storage paths or download URLs. Role revocation applies to the next request. Procurement exports keep the source service's Project/Business visibility restrictions.
- Audit exports retain a fixed maximum event ID before generating the file, so the subsequent export event does not recursively include itself. Full sanitized details remain in a separate worksheet/PDF section, linked by event identifier.
- Responses use genuine PDF/XLSX MIME types, private/no-store caching, attachment filenames and `nosniff`. Successful exports record sanitized audit metadata.
- Existing legacy spreadsheet endpoints remain compatible for callers; the visible financial-report and audit Excel buttons use the new branded export. Records snapshot UI uses the branded download while its original XLSX format remains available for existing integrations.

## Beginner testing guide

1. Sign in with an account allowed to open the section. Open each screen in the table above.
2. Click **Export → Download PDF**. Open the downloaded file. Check the logo, green heading, record names and amounts. In the PDF viewer's document properties, page size should be **210 × 297 mm** (approximately **8.27 × 11.69 inches**), portrait.
3. Read the PDF at **100% / Actual size**. Wide records should appear as labelled blocks with readable values. Page numbering should continue correctly. Do not use a viewer's multiple-pages-per-sheet option when comparing text size.
4. Click **Export → Download Excel (.xlsx)**. Open it in Excel or LibreOffice. Check the foundation logo, summary counts, all worksheets, dates and amounts. The file should open without a repair warning.
5. Apply a filter and submit it on the screen, then export again. For example, filter Tasks by High priority. Only matching rows should appear, including matches on later screen pages. Try an empty result and check that both files remain valid.
6. Open **Small Business → Reports**, **Projects → Reports** and **Procurement → Reports**. Select a period and fund, then use their PDF/Excel buttons. Compare payments, refunds and net balances with the corresponding detail records. Current commitments must not be mistaken for actual cash payments.
7. In a test project approved for PKR 500, pay PKR 10.25. Export Projects and its financial report: net spending should be 10.25 and remaining authority 489.75. For a fully received/invoiced PKR 500 procurement purchase paid 10.25, its report should show unpaid 489.75. Exporting must not create another ledger entry.
8. Open **Records → Reports**, generate a permitted report and open it. Download its PDF and Excel. Change a source record in the test installation and download that same retained report again: its snapshot totals must remain unchanged. Generate a new report to see the new source values.
9. Open **Records → Documents** using two accounts with different sharing permissions. A private document must be absent from both the unauthorized user's export and count. Sharing it should make it visible; removing the shared role should hide it on the next export.
10. Open **Audit Trail**, select an actor/module/date/reference and export. Compare the summary and details sections by event ID. The event recording this export should appear on a later audit search, not inside the file being generated.
11. Try a copied export URL using an account without permission. It must return access denied. Procurement view alone must not expose Project/Business purchasing that requires the missing parent permission.
12. Repeat a PDF download in Members, Committees, Users/Roles, Finance and Welfare. They should now use the same portrait A4 page size. Print an agreement or purchase order, select **A4**, **one page per sheet**, and **100%** scale; check signature spaces and readability.
13. On a narrow mobile window, open an Export menu and verify it stays on screen. Escape or clicking outside should close it.

## Verification

The complete suite passed **466 tests / 3,984 assertions**. After the final PDF JSON-wrapping adjustment, the 26 export tests passed again with 206 assertions. The MySQL QA browser run passed 28 checks covering all new registers, detailed reports, retained snapshots, filtering and mobile positioning. Sixteen existing PDF download types and a browser print layout also passed. Final samples comprise 30 A4 PDF files and 13 branded workbooks.

The automated export tests cover genuine PDF/XLSX files, A4 dimensions on every generated page, permissions, all-page filtering, text injection prevention, document sharing/revocation and exact Project/Procurement balances. The existing complete regression suite is also run.

The populated local MySQL QA browser checks exercise every new export menu, the three financial-report forms, retained snapshot downloads, filter retention and mobile positioning. Existing organization/Finance/Welfare PDFs and the browser print layout are checked separately. PDF pages are parsed and rendered, and every generated workbook worksheet is rendered for visual inspection.

Regression checking also exposed an existing final-day-of-month Donations summary issue in SQLite. The monthly range now uses an inclusive first day and exclusive first day of the next month, retaining payments with midnight time suffixes. Its regression test is fixed at a month-end date.

## Alwaysdata update

No database migration, import, reset, seeding, key change or new Composer dependency is required. The hosted QA data remains in **`ngosystem_demo`**. Publishing commits and updating the hosted checkout are separate actions.

After publication, connect by SSH and run:

```bash
cd /home/ngosystem/www/foundation
export PHP_VERSION=8.4
git status --short
```

If modified files are listed, reconcile them before pulling. With a clean checkout on `feature/complete-ui-redesign`:

```bash
git pull --ff-only origin feature/complete-ui-redesign
php artisan route:clear
php artisan view:clear
DB_DATABASE=ngosystem_demo php artisan config:cache
php artisan tinker --execute='dump(DB::connection()->getDatabaseName());'
```

The final output must identify `ngosystem_demo`. Keep `DB_DATABASE=ngosystem_demo` in the existing server `.env` and any applicable Alwaysdata site environment setting so future cache rebuilds use the same database. Preserve existing passwords, application/lookup keys and private attachments. Hard-refresh the browser to load the new print assets, then test one PDF/XLSX from each menu group.
