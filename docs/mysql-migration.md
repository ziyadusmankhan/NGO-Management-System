# SQLite to MySQL migration

The local application uses the existing Laravel migrations to build an InnoDB / utf8mb4 MySQL schema. MySQL sessions use UTC. Existing keys, ciphertext, passwords, record IDs, financial amounts and file paths are preserved.

## Compatibility fixes

- Welfare signature-verifier foreign keys have explicit short names within MySQL's identifier limit. The migration can resume after interrupted MySQL DDL.
- Encryption migration removes obsolete indexes on plaintext columns before changing them to TEXT. Exact CNIC/phone lookup indexes remain authoritative, with unique Member CNIC lookup values. Partial schema changes can be resumed without recreating security_state or lookup columns.
- Existing money and locking services already select their SQLite-specific code only for SQLite. MySQL keeps native DECIMAL values and row locking.

## Local transfer tools

Use PHP with pdo_mysql, pdo_sqlite and sqlite3 enabled. The custom tmp/phase8-cli.ini test configuration does not enable pdo_mysql; the regular Herd PHP configuration does.

Temporary migration credentials live in the ignored .env under MYSQL_MIGRATION_HOST/PORT/DATABASE/USERNAME/PASSWORD. Never commit credentials or encryption keys.

1. `php scripts/mysql-transfer.php --check` checks connectivity and reports whether the target has tables.
2. `php scripts/phase11-backup.php` creates a verified database and uploaded-file snapshot while the application still uses SQLite.
3. `php scripts/mysql-transfer.php --rehearse storage/app/backups/<snapshot>` builds a separate rehearsal database and copies the snapshot. The optional third argument names a previously created rehearsal schema to resume.
4. `php scripts/mysql-smoke.php <rehearsal-schema>` renders read-only module entry screens using the imported administrator and an in-memory session. This is a controller/view smoke check, not a replacement for authentication or concurrent-payment testing.
5. Enter maintenance mode and create a fresh verified snapshot before the final transfer.
6. `php scripts/mysql-transfer.php --transfer storage/app/backups/<fresh-snapshot> <verified-rehearsal-schema>` creates the empty final schema from the proven rehearsal definitions, transfers the fresh snapshot and verifies it. The final database must have no tables; existing records will not be overwritten.
7. Only after successful verification, change DB_CONNECTION to mysql and configure DB_HOST/PORT/DATABASE/USERNAME/PASSWORD locally. Keep APP_KEY and SECURITY_LOOKUP_KEY unchanged. Clear configuration, check migration status and security/audit health, then leave maintenance mode.

Verification compares every copied column, canonicalizing JSON key order, DECIMAL formatting and DATE values stored at midnight by SQLite. It rejects non-midnight times in DATE fields. It checks foreign-key references explicitly and preserves ID high-water marks. Reports contain counts and identifiers, never passwords or decrypted personal fields. The target migration ledger comes from the MySQL schema build; it is not replaced by SQLite's migration rows.

## Recovery and continued operation

The original database/database.sqlite and verified snapshots remain available. Rehearsal databases are isolated copies and are retained for diagnosis. Do not use any old SQLite-only reset or activation helpers against MySQL.

Before any new writes on MySQL, rollback can point DB_CONNECTION back to sqlite with DB_DATABASE set to the original SQLite absolute path, then clear configuration and restart the local server if necessary. After new writes occur on MySQL, switching back to an old snapshot would lose those writes; reconcile/restore the newer data first.

Future MySQL backups should include a consistent MySQL export, storage/app/private and public uploads, and matching APP_KEY/SECURITY_LOOKUP_KEY in restricted recovery storage. SQLite backup scripts are intentionally restricted to SQLite. Use MySQL Workbench Data Export or an appropriately configured mysqldump account; do not put passwords on command lines. Keep the MySQL Windows service running while using the application.

The existing PHPUnit suite remains isolated on in-memory SQLite. Engine-specific MySQL controller, migration and data-preservation checks complement that suite; the SQLite concurrency tests do not certify MySQL concurrency.

## Local activation receipt — 29 October 2026

- Live engine: MySQL Community Server 8.0.46, schema `foundation_management`, InnoDB, utf8mb4, session timezone `+00:00`.
- Rehearsal: `foundation_management_rehearsal_1029124721`, 88 data tables, 725 rows and 7,652 column values verified. All 26 module entry controllers/views rendered.
- Final maintenance snapshot: `storage/app/backups/phase11-20261029-180548`; verified database integrity and 28 original files. Retain this restricted recovery snapshot and the original SQLite database.
- Final transfer: 88 data tables, 729 rows, 7,691 values compared, foreign-key references verified, passwords/ciphertext unchanged. The migration bookkeeping table is additional to the 88 data tables.
- Final checks: all 26 module entry screens rendered on the final MySQL schema, nine users retained, all 28 file checksums matched, migration status complete, security health passed and audit health passed with 175 retained events.
- SQLite compatibility checks: 18 tests / 196 assertions; encryption migration checks: six tests / 21 assertions. No MySQL financial concurrency certification is claimed.
- Local `.env` points to MySQL and references the locally entered credentials. Secrets and snapshots are ignored by Git. Maintenance mode was cleared after verification.

Use the same application login accounts and passwords. In MySQL Workbench, refresh Schemas and open `foundation_management`. Keep MySQL80 running. Development code is saved in a local commit; GitHub publishing remains a separate instruction.
