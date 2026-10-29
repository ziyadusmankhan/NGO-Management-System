# Application QA — 29 October 2026

The findings below describe the original review before repairs. The issues have since been fixed and realistic-data testing completed; see [the follow-up results and testing guide](realistic-qa-testing.md).

## Original review result (before fixes)

The existing automated suites passed. Additional negative authorization checks failed, and browser data entry identified two Member-module defects. There are four confirmed issues; the application is not ready to be described as defect-free. This was a test/report task; the product defects below have not been changed.

| Verification | Actual result |
|---|---|
| Entire existing feature suite on isolated SQLite | 377 tests passed, 3,092 assertions |
| Qard, Small Business, Projects and Procurement workflows on disposable MySQL | 42 tests passed, 327 assertions |
| Browser form entry on disposable MySQL | 13 checks completed; two application issues recorded |
| New Organization authorization checks on isolated SQLite | Two failed security assertions, confirming guest writes and role escalation |
| Working database contamination check | Zero QA accounts, QA funds or QA members found |

## Automated data-entry coverage

The existing HTTP tests submit applications and forms, upload synthetic documents, switch actors/permissions, perform approval stages, post payments and repayments, and verify resulting models, audit events and ledger entries. They are functional tests, not just page-load checks.

Coverage includes Welfare approval/signing/payment/follow-up flows; fund restrictions and commitments; Qard disbursement and recovery; staged Business funding; Project budgets/amendments/refunds; Procurement quotation/receiving/invoice/payment controls; private Documents and versions; user/security/permission controls; Tasks, Meetings and Reports; Search, Notifications and forwarded HTTPS sessions.

The additional MySQL run exercised actual writes for Qard, Business, Project and Procurement workflows. Examples include PKR 500 authorization, PKR 10.25 payment, PKR 489.75 remaining authority, rejection of excessive payments, duplicate-request handling, repayments, refunds, void ordering, private evidence and separation of duties. MySQL financial concurrency was not newly certified by this run; the concurrent-worker tests in the full suite use SQLite.

The first MySQL attempt lacked the default Procurement quotation-policy row because only the schema had been cloned. After initializing the same default row the migration normally creates, the complete 42-test MySQL selection passed. This was a test-fixture issue, not a defect in the working database.

## Browser checks completed

1. Login and authenticated Dashboard.
2. Create a Member with synthetic name, phone, CNIC and email; saved detail page displays the record.
3. Find the Member through partial-name search.
4. Create an Implementation Committee.
5. Create a general Fund with PKR 1,000.00 opening balance.
6. Record a PKR 10.25 Donation into that Fund.
7. Confirm the Donation appears in Transactions.
8. Create a Task.
9. Confirm the current password for a sensitive operation.
10. Create a User and assign the Viewer role.
11. Switch to dark mode and reload; preference remains.
12. At 390px, open the navigation drawer and close it with Escape.
13. Log out and return to Login.

Browser automation used the actual server, cookies, CSRF tokens, forms and MySQL writes. Custom radio labels were clicked through their visible labels; initial locator ambiguity between Viewer and Auditor / Reviewer was corrected in the test, not counted as an application defect.

## Confirmed defects

### QA-03 — High priority: unauthenticated Member creation

Location: Member resource routes in `routes/web.php`; `StoreMemberRequest::authorize()` returns true and the resource routes have no authentication/permission middleware.

Confirmed in `OrganizationAccessAuditTest::test_guest_cannot_create_a_member`: an unauthenticated POST creates one Member, where zero must be created. The test uses an isolated database. CSRF protection is a separate control and must not be treated as authentication or action authorization.

Correction needed: enforce authentication and the existing members.create permission on the write endpoint, with appropriate view/update/delete permissions across the Member resource. Test unauthenticated and authenticated-but-unauthorized actors.

### QA-04 — High priority: unauthorized role-permission escalation

Location: `users.roles.update` in `routes/web.php` and `RoleController::update()`.

Confirmed in `OrganizationAccessAuditTest::test_user_without_role_management_cannot_grant_themselves_finance_access`: a user with only members.view and a recent password confirmation changes their role to include finance.view. The route requires authentication but lacks roles.manage enforcement. Recent password confirmation proves knowledge of the user's own password; it does not authorize that user to administer roles.

Correction needed: require roles.manage on both role-management endpoints and retain recent-password confirmation on updates. Verify ordinary users cannot change any role, while authorized administrators can. Related older Organization routes warrant the same authorization review.

### QA-01 — Add Member preview JavaScript references missing elements

Location: `resources/views/members/create.blade.php`, name-input preview handler near line 601.

Reproduce: open Add Member and type a name. The handler looks up `previewName` and `previewAvatar`, but those elements are absent from the form. Assigning their textContent raises `Cannot set properties of null`. Saving the Member still succeeded in the browser run; the preview behavior is broken.

Suggested correction: either restore the intended preview elements or remove/guard the obsolete preview handler. Add an interactive browser assertion for typing, not just rendering an empty form.

### QA-02 — Full multi-word Member name search misses saved records

Location: `app/Http/Controllers/Members/MemberController.php`, index search predicate near line 38.

Reproduce: save a Member whose name spans first_name and last_name, such as `QA Browser Member <suffix>`. Search for the complete displayed name in Members. No match is returned, although a partial surname/suffix search returns the saved record.

Cause: the entire search term is matched separately against first_name and last_name; neither column contains the combined displayed name.

Suggested correction: support tokenized/full-name matching while retaining partial record-number search and exact encrypted CNIC/phone lookup. Add a regression case for combined first/last names.

## Isolation and evidence

The working MySQL database remained `foundation_management`. The disposable schema was `foundation_management_test_20261029141545_c143`; it contains only synthetic QA records and required baseline configuration. Only the schema/migration ledger was cloned; real user and financial rows were not copied into it.

PHPUnit's MySQL mode requires an exact disposable database-name pattern plus the local preparation receipt, rejects DB_URL overrides and uses transactions to roll back each test's records. Browser QA used a separate loopback-only server on port 8011 and separate file/session/cache settings. The temporary server was stopped after testing. The working server on port 8000 was left running.

Local evidence is in ignored `tmp/overall-sqlite-tests.txt`, `tmp/overall-mysql-verified.txt`, `tmp/qa-browser-workflows.json` and `tmp/qa-browser-final.png`. The disposable MySQL schema is retained for inspection; no production reset or cleanup was performed.

## Repeating the MySQL checks

1. Run `php scripts/prepare-mysql-tests.php` from the working local application. It creates a new disposable schema and writes `tmp/mysql-test-target.json`.
2. Set the process DB_DATABASE to the exact database in that receipt, then run `php vendor/phpunit/phpunit/phpunit --configuration phpunit.mysql.xml --testdox`. Never point these checks at the working schema.
3. After workflow tests, run `php scripts/qa-browser-seed.php` and start a temporary server with `php -S 127.0.0.1:8011 -t public scripts/qa-browser-router.php`.
4. Run `node scripts/qa-browser-workflows.cjs` with Playwright available and UI_BROWSER_PATH pointing to an installed Chrome executable. QA_RESUME_USER=1 resumes the final user/theme/logout checks after the earlier form checks.
5. Stop only the temporary port-8011 server. Prepare a fresh schema for another transactional MySQL run after browser records have been committed.

Settings persistence and any existing preview-only controls remain deferred features, not newly tested working backends. Physical mobile-device/tunnel-provider access and every conditional form in every browser are outside this run's verification.
