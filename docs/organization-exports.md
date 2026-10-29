# Organization PDF and Excel exports

Members, Committees, Users & Roles, and Manage Roles now have working **Export → Download PDF / Download Excel (.xlsx)** menus. The application generates real downloadable files; PDF does not depend on the browser print dialog.

## What is included

| Screen | Export contents | Required permission |
|---|---|---|
| Members | Contact details, committee positions, membership status and joining date | `members.view` |
| Committees | Committee register and a separate member/position roster | `committees.view` |
| Users & Roles | User names, email, every assigned role, committees, status, last activity and joining date | `users.view` |
| Manage Roles | Role summary and a separate assigned-permissions listing | `roles.manage` |

Exports use all matching records across pagination, with the same server-side search, status, committee type and user-role filters as the list. A user's secondary roles participate in the filter and export. Archived accounts, members and committees remain excluded by the existing model scopes. Manage Roles exports active roles, matching that screen; selecting a role limits its export.

The export records the generating user, Asia/Karachi timestamp, applied-filter summary and record counts. Search text is not repeated in metadata or audit entries. Passwords, authentication secrets, CNICs and dates of birth are excluded. Existing module access governs exported contact information; `users.view` does not grant permission-list export access. Downloads use private/no-store headers and are audited without storing row contents.

Requests above 2,500 primary records or 10,000 total detail rows ask for narrower filters. There is no silent truncation. Empty exports are valid, readable files. Export routes are authenticated and rate limited to 20 requests per minute.

## Design and template

The foundation's existing logo, dark green masthead, pale green count bands, readable striped tables and page numbering form the shared design. PDF headers include the organization name in English and Urdu. PDF is A4 landscape and repeats table headings over page breaks. Committee rosters and role permissions continue in the same PDF and appear as additional Excel sheets.

The user-selected Sales Pipeline spreadsheet supplied the starting layout. Its masthead, count bands, metadata area, table layout and frozen headers were adapted into an organization register. Sales forecast controls, sales data, helper formulas, validations and names were removed. The installed source template was not changed. The owned, blank workbook is `resources/export-templates/foundation-register.xlsx`; the application fills it using PhpSpreadsheet. A reusable personal Foundation Register template is packaged separately through Template Creator.

Excel exports contain native filterable tables, frozen headers, numeric counts, date values and wrapped text. Phone numbers and all untrusted text are written explicitly as strings, preserving leading zeroes and preventing formula injection. Count formulas cover the actual exported rows. No sample records are inserted into the working application.

## Implementation and installation

- Shared queries: `app/Domain/Organization/Services/DirectoryQuery.php`.
- Report composition: `DirectoryReport.php`; PDF/XLSX rendering: `DirectoryExport.php` in the same directory.
- Literal export routes precede resource wildcards in `routes/organization-exports.php`, loaded by `routes/web.php`.
- PDF views: `resources/views/organization/exports`; branding: `config/organization.php`.
- Added mPDF for server-side PDF generation with Unicode/Urdu support. The lockfile also includes a compatible Symfony Mime security patch.

Other installations should run `composer install` with the committed lockfile. PHP needs the existing application extensions, including GD, ZIP and mbstring, and writable `storage/framework/cache/organization-pdf` for the mPDF font cache. No schema migration, account reset, key change or database-engine change is needed. If deployment caches views/configuration, rebuild them using the normal deployment process. Keep the workbook and logo paths available to PHP.

## Beginner test checklist

1. Log in as Administrator. Open **Members** from the left menu.
2. Click **Export**, then **Download PDF**. Open the downloaded file. Check the logo, foundation name, green headings, names and total. It should open directly as a PDF.
3. Click **Export → Download Excel (.xlsx)**. Open it in Excel or another compatible spreadsheet application. Check the same people and total. Try the small filter arrows on the table headings.
4. Search for one member and click **Search**. Download both formats again. Only matching members should appear. Clear the search afterward.
5. If there are several pages of members, export without filters. Check that records from the other pages are included too. Export always includes all matching pages.
6. Open **Committees**. Try the type and status filters. Download both files. In the PDF, look below the committee summary for its membership roster. In Excel, open the **Committee Members** tab and check names and positions.
7. Open **Users & Roles**. Download both files. Check each person's complete role list. Compare the last-active time with that person's record; the exported timestamp is displayed in Pakistan time. Users who have never been active show an empty value or dash.
8. Choose a user role in the filter and export again. A user with that role as a secondary assignment must still be included.
9. Click **Manage Roles**, then **Export**. Check the role summary and permission listing. In Excel, open **Assigned Permissions**. Select a role on the page and export again to check that role alone. Administrator is labelled as having full system access.
10. Search for a name that does not exist and export. The files should say **No matching records**, with a zero total.
11. Switch the application to dark mode and repeat opening the menus. Try a phone-width window. Links should remain readable; Escape closes the menu. Files retain the foundation's printable green design in either theme.
12. Sign out and try a saved export URL: login must be required. With a restricted account, try a module it cannot view: access must be denied. An account that only has `users.view` must not export role permissions.

Use synthetic data in an isolated test database when testing changes to roles or accounts. Simply downloading an export does not change the directory records; it records an export audit event.

## Verification tools

`tests/Feature/OrganizationExportTest.php` covers permissions, actual file formats, private headers, audit attribution, counts, all-page filtering, secondary roles, encrypted contact display, typed dates, leading-zero phone values, formula injection, archived records, rosters, permission details, empty exports and invalid formats.

On this Windows workstation, the complete suite can be run with the supplied SQLite-capable PHP configuration:

```powershell
$env:TEST_TOKEN='organization-regression'
php -c tmp/phase8-cli.ini vendor/bin/phpunit --no-progress
Remove-Item Env:TEST_TOKEN
```

The local `tmp/phase8-cli.ini` is an ignored workstation configuration; other installations should enable SQLite for testing and allow 512 MB for the complete suite. Calling PHPUnit directly preserves that command-line configuration.

`scripts/qa-organization-exports.cjs` exercises all eight download buttons against the guarded disposable MySQL browser QA environment, verifies filters and mobile-menu behavior, and writes only synthetic sample files under ignored `output/organization-exports`. `scripts/qa-organization-export-files.php` independently compares their counts with the QA database and checks workbook structure, formulas and PDF page counts. These tools require the existing local QA receipt/server and do not target the working database.

Verified on 29 October 2026: **399 tests / 3,288 assertions passed**, including **16 export tests / 171 assertions**; **12 browser checks** passed without browser errors. The eight downloaded files were independently checked against the QA database (7 members, 4 committees, 12 users and 19 active roles; 6 committee assignments and 311 permission assignments). All PDF pages and all spreadsheet sections were rendered and inspected. Composer audit reported no advisories. These counts describe synthetic test records, not the working database.
