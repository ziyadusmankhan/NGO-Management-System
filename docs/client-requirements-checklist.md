# Client requirements versus implementation

Source: **Tanzeem Falah Naujawanan Kasso Foundation Software Requirements & Development Specification v1.0, 12 August 2026**. Updated 26 October 2026 for the Phase 9 Procurement implementation.

This is a coverage map, not a claim that the entire client specification is complete. The client's five broad development phases differ from the agreed fifteen-phase delivery roadmap.

| Client sections | Current coverage / remaining work |
|---|---|
| 1–2 Purpose and organization | Existing users, members and configurable committees. Client officer names are not seeded or hardcoded as logged-in users. |
| 3 Reporting chain | Phase 7 follows field verification → Chief Coordinator → Secretary → committee resolution → independent payment. Historical Welfare/Qard decision flows remain as previously delivered; do not silently reinterpret them as the new offline-consent model. |
| 4 Roles | Configurable permissions, dedicated monitoring roles, Qard Officer and Phase 7 Small Business Officer/Chief Coordinator/Auditor-Reviewer. Admin has technical access but cannot bypass business separation rules. |
| 5 Dashboard | Existing organization/finance data and new live business funding, recovery and actual-start totals. Phase 8 adds permission-controlled Active Projects (In Progress) and lifecycle counts. Student, welfare and standalone-Qard dashboard coverage remains Phase 12. Unbuilt widgets remain marked in development. |
| 6 Members | Existing CRUD and archival, committee assignments and statuses. A general member-document library is not delivered by Phase 7. |
| 7 Donations | Existing donation workflow, funds and ledger. Phase 8 links restricted named-project donations through exclusively bound project funds. Shared-purpose donations remain pooled, without donor-to-specific-payment attribution. |
| 8 Restricted funds | Existing received/allocated/spent/recovered/refunded/balance and history. Phase 7 adds business commitments and origin-preserving recoveries. Phase 8 adds approved unpaid project budgets, original-fund supplier refunds and central dedicated-fund exclusivity. Changing used fund designations is restricted; no general donor-restriction waiver workflow is introduced. |
| 9 Small Business | Phase 7 delivers applicant/household snapshots, itemized costs and own contribution, evidence/photos, viability, decisions, signed agreement, staged funding, asset handover, actual start, monitoring, recovery and closure. Each case is grant or fully repayable. |
| 10 Qard | Phase 6 standalone loan workflow and repayments; Phase 7 adds business-originated principal portfolio without duplicate loans. Business agreements include two witnesses. Retrofitting standalone Qard witness fields remains Phase 11. No interest, compulsory donations or penalties. |
| 11, 29 Fund recycling | Posted business recoveries go once to the original fund. Fund history connects donations, cases, beneficiaries, releases and recoveries. Shared capital and approved funding queue support later reuse. No exact donor-lot allocation or automatic approval/payment of the next case. |
| 12 Beneficiaries | Existing identity, family/financial condition, verification, approval and welfare history; Phase 7 adds permission-controlled business history. Wider document library/security work remains Phase 11. |
| 13 Welfare | Phases 1–5 delivered approvals, agreements, exact money, payment integrity, review reminders and explicit closure. Phase 7 preserves legacy livelihood support. |
| 14–15 Projects | Phase 8 delivers proposals, stable item budgets, field review, independent decisions and offline committee consent, direct payments/refunds, amendments, monitoring, final reports, closure, cancellation, reopening and retained history. |
| 16 Expenses | Existing expense and finance controls. Phase 7 blocks new generic expenses from Small Business restricted funds while retaining historical records. Phase 8 adds controlled project payments to Expenses and reports without duplicate expense records. New generic Project spending is blocked; historical posted entries are preserved. Phase 9 adds office procurement payments without duplicate generic expenses; new generic goods requests must use Procurement. |
| 17 Procurement | Phase 9 delivers office/Project/Business purchasing, versioned quotations, configurable quote count, independent approvals, orders, acceptance, invoices, payments, Project/office returns and actual refunds, reservations and retained evidence. No supplier advances, inventory or Business post-payment supplier returns. General approval-value thresholds remain separate scope. |
| 18 Tasks | Phase 14. |
| 19 Meetings | Phase 14, including formal votes and minutes. Phase 7 records offline resolution evidence and consent; it is not an online voting system. |
| 20 Documents | Module-specific private project/business/Qard evidence and controlled downloads exist. Central library and a review of older upload paths remain Phase 11. |
| 21 Audit Trail | Sensitive procurement/project/Welfare/fund/Qard/business events are retained in their modules. A unified viewer and remaining administrative event coverage are Phase 10. |
| 22 Reports | Existing Finance Reports; Phase 7 business report includes invested/recovered/outstanding, actual starts, overdue principal, current shared capital and funding queue. Print-to-PDF and actual XLSX. Phase 8 adds project budget/payment/refund/variance/progress reports, print-to-PDF and XLSX. Phase 9 adds purchasing delivery, invoice, payment, return/refund and commitment reports with PDF print and XLSX. Remaining Records/monthly reports are Phase 12. |
| 23 Visibility | Phase 7 routes, financial actions, reports and documents require explicit business permissions. Ordinary membership grants no business access. Full public/member portals and organization-wide visibility audit remain future work. |
| 24 Notifications | Procurement delivery/invoice/refund and Project/Welfare/Qard/business in-app reminder records and scheduled refresh commands. General event notifications are Phase 12; WhatsApp/SMS/email integrations need separate scope. |
| 25 Global search | Existing search fields are module-specific. Global search remains Phase 12. |
| 26 Security | Authentication, role checks, private new evidence, financial locking, retained history, backups and isolated tests. Optional 2FA, sensitive-field encryption, full retention policy and comprehensive hardening remain Phase 11/13; do not treat Phase 7 as production certification. |
| 27 Technology | Laravel, Blade, responsive layout, SQLite local development, print PDF and XLSX. MySQL/PostgreSQL migration and engine-specific concurrency certification are separate deployment work. |
| 28 Phases | Use the agreed delivery roadmap below; client phase numbers are broad product categories. |
| 30–40 Principles | Independent request/approval/payment, original fund restriction, exact money and retained financial history implemented for business. Categories/committees/funds configurable. General configurable approval-value limits and persistent operational settings require later phase plans; Phase 7 applies full approvals to every business case, without an arbitrary value exemption. |
| 41 Future modules | Contributions, campaigns, Zakat, volunteers, inventory, public website, mobile app, chatbot and online donation portal remain outside current scope. |

## Prior deliveries reviewed

- Phases 1–2: Welfare workflow and cumulative payment limits. Reused money and transaction services.
- Phase 3: Fund purpose controls and commitment-versus-ledger distinction. Added business commitments and intentionally narrowed generic Small Business expenses.
- Phase 4: Monitoring and reminders. Business monitoring keeps operational outcome separate from debt settlement and closure.
- Phase 5: Role controls, decimal handling and reset tooling. No reset belongs to Phase 7 deployment.
- Phase 6: Qard, principal recovery and financial concurrency. Reused its exact schedule rules through a shared helper; kept staged business funding in its own domain.

## Remaining roadmap

9 Procurement · 10 Audit Trail · 11 Documents/security/standalone-Qard witnesses · 12 Records Reports/dashboard/search/notifications · 13 cleanup/integration/deployment · 14 Tasks/Meetings · 15 persistent Settings.

See `phase-7-small-business.md` for the current workflow, deployment procedure and beginner testing checklist. Earlier delivery documents retain their historical test counts; these are not the current aggregate verification result.

## Phase 8 integration review

Projects reuse the existing Money, TransactionService, fund balance/commitment controls and Phase 7 separation pattern. All prior domains are retained. New permissions are additive and do not change existing passwords or users. Tasks and general Recent Activity remain in development. See `phase-8-projects.md` for implementation and beginner test steps.


## Phase 9 integration review

Procurement preserves the authoritative Project and Business financial movements, adds office expense/refund movements, and does not double-count parent commitments. Legacy approved goods retain compatibility; new goods approvals require purchasing acceptance/invoicing before payment. Parent closure/funding finalization, amendments, refunds and voids respect procurement obligations. The upgrade adds Procurement Officer/Receiving Officer and explicit action permissions without changing user accounts or passwords.

See `phase-9-procurement.md` for implementation and rollout notes, and `phase-9-procurement-testing.md` for the beginner walkthrough. Phase 10 remains the unified Audit Trail; inventory and automated supplier communication are not delivered.
