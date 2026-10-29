# Phase 11 — Recovery and deployment

## Preserve keys and backups

Keep APP_KEY unchanged. Keep the separate SECURITY_LOOKUP_KEY with it in restricted offline recovery storage. Never commit either value. Losing the encryption key makes encrypted personal fields, authenticator secrets and recovery codes unreadable. Losing/changing the lookup key breaks exact searches; fingerprint checks deliberately refuse a replacement key.

The pre-upgrade database contains old plaintext personal data. Its upload snapshot contains original confidential files. Restrict access to the operator/backup administrators and keep the backup encrypted at the host or backup-system layer. The Phase 11 field encryption does not encrypt files or old backups.

Do not use migrate:fresh, reset commands or key:generate for this rollout. Do not run the full DatabaseSeeder against an existing installation merely to add Phase 11 permissions: use DocumentSecurityRoleSeeder.

## Local SQLite upgrade sequence

Run commands from the application directory with the configured PHP runtime and required extensions (including OpenSSL, Fileinfo, ZIP, SimpleXML, PDO SQLite and SQLite3). Ensure web PHP permits the intended 10 MB uploads and a larger multipart POST body.

1. Finish the automated tests. Tell active local users that a fresh login will be required.
2. Enter maintenance mode and pause scheduled/background writers.

       php artisan down

3. Create the separate lookup secret once; do not replace an existing key.

       php artisan security:lookup-key
       php artisan config:clear

4. Back up the database and uploaded files. The script uses SQLite's backup API, checks database integrity/foreign keys, and verifies every copied file checksum.

       php scripts/phase11-backup.php

5. Keep the returned backup directory. Copy the matching environment secrets into restricted recovery storage. Rehearse restoration and conversion against an isolated copy, using that exact backup path:

       php scripts/phase11-restore-check.php storage/app/backups/phase11-YYYYMMDD-HHMMSS

   It changes only the restored database/uploads, not the live records. It verifies file bytes, protected values, retained row counts, password hashes and database integrity. The returned verification.json is a local receipt; it contains no decrypted values.

6. Apply the additive migrations and focused permissions, then inventory/import and convert:

       php artisan migrate --force
       php artisan db:seed --class=DocumentSecurityRoleSeeder --force
       php artisan documents:import --dry-run
       php artisan documents:import
       php artisan security:encrypt --dry-run
       php artisan security:encrypt
       php artisan security:encrypt --verify
       php artisan security:health
       php artisan audit:health

   Resolve any normalization collisions before conversion. Reports identify record IDs, not CNIC values. Rerun interrupted import/conversion commands; completed source registrations and encrypted cells are not duplicated. Do not manually set the conversion state to sealed.

7. Verify users/password hashes, original file checksums and retained financial records against the backup. Revoke old sessions and remember tokens as part of activation. New session middleware also rejects sessions without the completed-login policy marker.
8. During controlled maintenance, enable SQLite secure_delete, checkpoint/truncate any WAL and VACUUM the active database after replacing plaintext. Check integrity again. This removes active SQLite free-page/journal remnants; it does not promise forensic erasure from snapshots, SSDs or host backups.
9. Keep the original restricted plaintext backup for recovery; do not upload it to GitHub. Leave maintenance mode only after checks pass:

       php artisan up

The local activation helper performs steps 6–8 with preservation checks:

    php scripts/phase11-activate.php storage/app/backups/phase11-YYYYMMDD-HHMMSS

It requires maintenance mode and a verified matching backup. Do not use it against a different database engine or production host.

## Recovery after interruption

- If conversion stops halfway, leave the app in maintenance mode, retain both keys and rerun security:encrypt followed by --verify. Each row replaces plaintext only after a successful authenticated round trip in its transaction.
- If a key is missing/wrong, restore the original key from restricted recovery storage. Do not generate a new APP_KEY. Clear cached configuration and retry verification.
- If a stored file is missing or its checksum differs, restore the exact original file from the matching upload backup. Do not bypass the checksum or overwrite evidence with an unrelated file. A historical missing-file record remains explicitly missing until an operator repairs and verifies it; there is no ordinary library override.
- For a full rollback, stop all writers, retain a copy of the failed state for diagnosis, restore the matching pre-upgrade code/dependencies, database, uploads and keys together, then verify integrity before reopening. Rehearse in a separate directory first. A pre-upgrade database must not run against code that assumes the new schema.
- Restore to the previous timestamp only with awareness of any later legitimate records. The automated rehearsal does not discard live data.

## Lost authenticator and recovery codes

Normal recovery uses one unused recovery code. If both the device and codes are lost, the trusted server operator verifies the account owner's identity, then runs:

    php artisan security:recover-two-factor person@example.com --reason="Verified identity; recovery ticket SEC-123"

This is an explicit, audited recovery. It removes the second factor and revokes sessions; it leaves the password requirement intact. Do not put passwords, codes or personal identity numbers in the reason. The user can enroll again after signing in.

## Deployment requirements

- Set APP_ENV=production and APP_DEBUG=false, use HTTPS and secure cookies, and configure only actual proxy addresses/CIDRs.
- Keep private storage outside the public web root. Expose only the intended public disk. Verify server-level paths cannot serve private files directly.
- Use a shared, reliable rate-limiter store and synchronized server clocks. Back up database, files and matching keys together.
- Complete Phase 13 malware scanning, host/backup encryption and deployment checks before treating uploads as malware-scanned or host storage as encrypted.
- Run security:health and audit:health after deployments and restoration. A missing historical file or checksum/key failure is actionable, not a successful health result.

## Verification receipts

Initial isolated rehearsal: backup phase11-20261027-221440; 24 original files verified, 81 protected values verified, 9 users/password hashes preserved, 4 referenced documents catalogued, zero missing versions, SQLite integrity and foreign-key checks passed.

Local activation completed on 28 October 2026 at 14:29 Asia/Karachi.

- Verified backup: storage/app/backups/phase11-20261028-142730. Access is restricted to the current Windows operator, SYSTEM and Administrators. The matching environment.recovery contains the original application key and separate lookup key; do not publish it.
- Latest isolated restore: phase11-restore-dd232d37-60f5-4231-8157-e07221ac106e. All 24 files and 81 protected values verified; 9 users/password hashes preserved; 4 document records; no missing versions.
- Live preservation checks compared 641 original database values. All checks passed. Original financial records and password hashes remain unchanged. All 24 file checksums match the backup.
- Additive migrations, focused permission upgrade, document import, conversion and verification, security:health and audit:health all exited successfully. SQLite integrity and foreign-key checks passed; free-page count was zero after VACUUM.
- Previous sessions and remember tokens were revoked. The app left maintenance mode after successful activation. Sign in again with the existing password.
- HTTP smoke check: login returned 200 with the real login form; unauthenticated Documents access redirected to login; private/no-store and nosniff headers were present. The temporary verification server was stopped afterward.
- Final regression suite: 362 tests passed, 2,786 assertions, 229.88 seconds. This includes the existing 336-test baseline and 26 Phase 11 tests. The oversized Office fixture uses a sparse temporary file so the test does not exhaust the default PHP memory limit.
- Composer dependency audit found no advisories or abandoned packages in the locked dependency set.

GitHub publishing remains a separate instruction.
