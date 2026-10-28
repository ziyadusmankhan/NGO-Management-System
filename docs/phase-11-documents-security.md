# Phase 11 — Documents Library and Security Hardening

## Documents

Records → Documents catalogues existing Welfare application attachments, signatures and verification evidence, Qard, Business, Project and Procurement files, plus standalone organization documents. Original modules remain authoritative. Supplementary attachments cannot satisfy approvals, signatures, quotations or payment evidence.

Standalone access requires the relevant document permission plus ownership or membership in an allowed role. Administrator retains administrative access. Records Officer receives view, download, upload, manage, share, archive and restore permissions. Auditor / Reviewer receives view/download subject to access restrictions. Linked records retain source permissions, including Procurement's additional parent permissions. Visibility checks precede counts, filters and pagination.

File versions retain original bytes, SHA-256 checksums, size, type and known uploader/date information. Unknown historical metadata remains unavailable. Missing files are explicitly marked missing. The library cannot replace or archive authoritative evidence. Standalone archiving retains all versions; there is no permanent-delete action. A recorded one-time upgrade maps old documents.delete grants to archive/restore.

Files are stored privately with generated filenames. Downloads use authorized attachment responses, safe names, private/no-store caching and nosniff. Existing protected source download URLs remain available.

### Validation

Library uploads accept PDF, JPG/JPEG, PNG, WebP, DOCX, XLSX and PPTX, up to 10 MiB each. Existing workflow forms retain their narrower evidence-type rules. Validation checks detected content type, image dimensions, PDF cross-reference structure and Office package contents. Office inspection rejects malformed/encrypted packages, macros, embedded objects, executable parts, unsafe/duplicate paths, XML entities/DTDs and active external template/data relationships. Ordinary external hyperlinks are not followed. Inspection limits: 1,000 entries, 20 MiB per expanded entry, 100 MiB expanded total and 2 MiB content-type metadata.

Files are labelled **validation-only / not malware-scanned**. Validation does not prove a file malware-free. Phase 13 retains mandatory malware scanning and host/backup encryption. See [OWASP's layered file-upload guidance](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html).

## Account security

- Password and second-factor attempts: five per identity/address/purpose per minute, plus 30 per address across security attempts.
- Sessions: 30 minutes without activity and eight hours from sign-in. Remember-me login is removed. Session payloads are encrypted.
- Account Security supports self password change, recent password confirmation, optional authenticator enrollment and signing out other sessions.
- New/changed passwords require at least 12 characters. Existing passwords and hashes remain valid.
- Account/permission changes, document sharing, administrative recovery and two-factor management require password confirmation within 15 minutes.
- Administrator-assisted recovery uses users.password-reset. The temporary password is shown once, expires after 24 hours, revokes sessions and forces a new password before application access. It does not disable enrolled two-factor authentication.
- Forgot-password instructions explain administrator-assisted recovery. Email reset delivery remains deferred.
- Fortify supplies encrypted authenticator secrets, QR generation, enrollment actions and code verification. Its default routes are disabled; the application's Blade controllers enforce active-account checks and completed-login auditing. See [Laravel Fortify](https://laravel.com/framework/docs/13.x/fortify).
- Password acceptance for an enrolled user creates only a five-minute pending challenge. No authenticated application access or successful-login event is granted until verification finishes. Account state/version is rechecked; recovery codes are consumed atomically and once.
- Lost-device recovery is an audited server command requiring an explicit account and reason. There is no ordinary administrator UI bypass.

Trusted proxy addresses/CIDRs come from an explicit environment allowlist. Direct framework serving of private storage is disabled. Response headers restrict framing, MIME sniffing, referrers and device capabilities. Production health checks require HTTPS, secure cookies and disabled debug mode; local HTTP remains supported.

## Encryption

SensitiveFields::TABLES is the explicit field inventory. It covers CNICs, personal phone/email/address fields, beneficiary household/financial narratives, Welfare findings/internal notes, Qard purpose, agreement/application snapshots and corresponding retained domain-history payloads. Names, login emails, record numbers, classifications and money remain queryable.

Protected casts use Laravel authenticated encryption with a version marker. Columns retain their names and receive text capacity where needed. Conversion preserves existing JSON meanings. Once conversion is sealed, plaintext fallback fails closed. [Laravel's encrypted casting guidance](https://laravel.com/framework/docs/13.x/eloquent-mutators#encrypted-casting) explains the query limitations.

Separate HMAC-SHA-256 indexes support complete CNIC/phone searches. Formatting separators are ignored; Pakistani +92 phone forms match their equivalent leading-zero form. Names and record numbers retain partial search. Member CNIC uniqueness includes archived records. Normalization collisions halt conversion and report only record IDs. A stored key fingerprint detects accidental lookup-key replacement.

Conversion is resumable, with verified per-row transactions. Dry-run and verify modes are read-only. Existing ciphertext is authenticated; plaintext is encrypted, round-trip checked and replaced atomically. Sealing follows a complete pass. APP_KEY is preserved; the new lookup key is outside Git.

Audit events retain their allowlist. Free-text Qard purpose is excluded. Protected Welfare/Qard/Business reasons remain in the source history rather than being copied into organization-wide audit details. Document/security events contain identifiers and outcomes, never files, passwords, codes or encryption keys.

## Qard witnesses

New and re-signed agreements require two distinct witness names, ignoring case and redundant spaces. Print views show names/signature lines. Previously signed agreements are marked legacy and remain usable; their financial obligations do not change.

## Delivery and roadmap

See [the beginner testing guide](phase-11-documents-security-testing.md) and [recovery/deployment instructions](phase-11-recovery-deployment.md). Migrations and permission upgrades preserve existing records. No reset, demo records, database-engine change or GitHub publishing is included.

Next: Phase 12 — Records Reports, remaining dashboard widgets, global search and notifications. Phase 13 retains malware scanning, host-level encryption and deployment/integration checks.

## Verified local delivery

Activated on 28 October 2026 after a verified database/upload backup and isolated restoration rehearsal. All 362 tests passed (2,786 assertions). Security and audit health checks passed. All 9 users and their password hashes, retained financial records and 24 original uploaded files were preserved. Four existing documents were catalogued with no missing files. Old sessions were revoked; sign in again. The recovery guide records the exact backup and preservation checks.
