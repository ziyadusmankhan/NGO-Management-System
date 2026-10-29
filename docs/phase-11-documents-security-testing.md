# Phase 11 — Beginner testing guide

Use your local test installation and existing administrator login. This upgrade does not change that password. Everyone must sign in again after activation. A previously short password still works; the 12-character rule applies when creating or changing one.

## 1. Confirm your password

1. Sign in and click **Account Security** at the bottom of the left menu.
2. Click **Confirm password for sensitive changes**.
3. Enter your current password and click **Confirm password**.
4. Return to the page where you wanted to make a change.

**Pass:** your password stays unchanged. Confirmation lasts 15 minutes.

## 2. Prepare two document users

1. As Administrator, confirm your password.
2. Open **Users & Roles → Add User**.
3. Create **Document Tester A**, with a unique email and a password of at least 12 characters. Assign **Records Officer**.
4. Create **Document Tester B**, with another email and the same role.
5. Use separate browser profiles/private windows to test separate accounts.

**Pass:** short passwords fail validation; valid accounts can sign in.

## 3. Upload and download

1. Sign in as Tester A.
2. Open **Records → Documents → Upload document**.
3. Title it **Office policy test**, select **Policy**, and leave the linked module/record fields empty.
4. Choose a real small PDF, image or ordinary DOCX/XLSX/PPTX file and upload it.
5. Open its details and download version 1.

**Pass:** the entry has real file metadata and the file opens. The assurance label says validation-only / not malware-scanned. No private storage path is displayed.

## 4. Check sharing and hidden metadata

1. As Tester B, search for **Office policy test**. Try its details/download URL copied from A.
2. B must not see the unshared document, its count or its file.
3. As A, confirm your password. Return to the document, select B's role under **Allowed roles**, and save.
4. Reload B's library and download the file.
5. Remove the allowed role again and retry B's old URL.

**Pass:** B gains access only while the role is allowed and B has the document permission. Revocation takes effect immediately. Sharing permission alone does not reveal another user's inaccessible record. Administrator retains administrative access.

## 5. Keep original versions

1. As A, add a changed file under **New file version**.
2. Download both versions.
3. Click **Archive document**, then return to the library.
4. Tick **Include archived**, open the document and click **Restore document**.

**Pass:** both files remain retained. Archiving hides the record from the default list; restoration brings it back. There is no permanent-delete button.

## 6. Check existing evidence and supplementary uploads

1. Open an existing Welfare, Qard, Business, Project or Procurement record and download its evidence from that screen.
2. Find the same evidence in Documents using the source record reference.
3. Open its library details.
4. Repeat with an account that has Documents view but lacks the source permission.

**Pass:** the evidence appears once for an eligible account. Its source is correct; unknown historical metadata says unavailable. The library cannot replace/archive it. An ineligible account cannot see metadata or download it.

5. For Procurement-linked Project/Business payment evidence, remove Procurement view while retaining parent view.
6. Try both the library and original evidence URL.

**Pass:** access is denied through both routes.

7. As an account with Documents upload and parent management access, upload a supplementary file using that module and its numeric record ID.

**Pass:** it is supplementary only. It cannot complete signatures, approvals, quotations or payment evidence.

## 7. Reject unsafe uploads

1. Try an executable file.
2. Rename a text file to PDF and upload it.
3. Try a file larger than 10 MB.
4. Try a password-protected/macro-enabled Office file.
5. If you have harmless prepared fixtures, try malformed Office packages, embedded executables and external data connections.

**Pass:** each is rejected visibly and creates no valid document record. Do not open suspected malicious files to perform this check.

## 8. Search personal numbers

1. Create a Member with a test CNIC and phone.
2. Search using the complete CNIC, then repeat without hyphens.
3. Search using the complete phone and then part of the member's name.
4. Try creating another Member with the same CNIC in a different format.
5. Archive the first Member and try the duplicate CNIC again.

**Pass:** complete-number and partial-name searches work. Duplicate CNICs fail, including archived members. Short fragments do not match encrypted numbers. Authorized displays still show the correct original values.

An operator verifies encrypted storage with: php artisan security:encrypt --verify. Never put real personal numbers or encryption keys in screenshots/test reports.

## 9. Session controls

1. Sign in to the same test account in two browser profiles.
2. In the first, confirm your password and click **Sign out other sessions**.
3. Refresh a protected page in the second profile.
4. Separately, leave a test session untouched for more than 30 minutes and then request a protected page.

**Pass:** the other/expired session returns to login. The current session stays signed in after signing out others. Remember-me is absent. Automated tests also cover the eight-hour absolute limit and deactivated accounts.

## 10. Change a password

1. On Account Security, try an incorrect current password.
2. Then enter the correct current password and a new password of at least 12 characters twice.
3. Save, sign out and sign in with the new password.

**Pass:** incorrect/short passwords fail; the new password works and other sessions end.

## 11. Administrator-assisted recovery

1. As Administrator, confirm your password.
2. Open the test user's details in Users & Roles.
3. Click **Generate temporary password** and confirm.
4. Copy the displayed password privately now. This is its only display.
5. Sign in as that user, complete their enrolled second factor if applicable, and choose a new password.

**Pass:** ordinary pages are blocked until the new password is saved. The temporary password expires after 24 hours. Recovery does not remove two-factor protection. Forgot password explains contacting the administrator.

## 12. Authenticator and recovery codes

1. As a test user, confirm your password and click **Set up authenticator**.
2. Scan the QR code with an authenticator app and enter its six-digit code.
3. Save the recovery codes privately immediately.
4. Sign out and sign in again with email/password.
5. Enter the authenticator code. If you just used that same time-based code for enrollment, wait for the next one.
6. Sign out, sign in again, and use one unused recovery code instead.
7. Sign out and try that same recovery code again.

**Pass:** the password alone never grants application access. A recovery code works only once. Replacing codes invalidates the old set; disabling/regenerating requires recent password confirmation.

If the device and recovery codes are both lost, a server operator uses the documented audited recovery command after verifying the owner.

## 13. Qard witnesses

1. Follow the Qard guide through independent approval.
2. Try signing with no witness names, one name or the same name twice.
3. Enter two different names and upload both real signed copies.
4. Open the printable agreement.

**Pass:** incomplete/duplicate witnesses fail; valid names and signature lines appear. Existing signed agreements stay usable and are marked legacy without changing repayment obligations.

## 14. Audit and operator checks

1. Open Audit Trail as Administrator or an authorized auditor.
2. Find document upload/share/version/archive/download and account-security events.
3. Inspect details.

**Pass:** actors, identifiers and outcomes are present. Passwords, codes, keys and file contents are absent.

Operator commands:

    php artisan documents:import --dry-run
    php artisan documents:import
    php artisan documents:import
    php artisan security:encrypt --verify
    php artisan security:health
    php artisan audit:health
    php artisan test

**Pass:** repeated imports do not duplicate evidence; encryption and health checks pass; tests pass. Missing historical files are reported explicitly and should be recovered from a verified backup.

For a failed check, record the screen, role, action and visible error. Do not include passwords, codes, keys or real personal numbers.
