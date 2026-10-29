# UI redesign coverage inventory

Branch: `feature/complete-ui-redesign`. Generated from Laravel routes and all existing Blade files; reusable UI components added afterward are listed in the implementation notes.

Every application page inherits `foundation-ui.css` and progressive enhancements from the app shell. Authentication and standalone print documents use dedicated shared themes. Existing page-specific CSS was extracted to `public/css/views` to retain specialized workflow layout; it is compatibility CSS, not a second product design system.

Verification labels distinguish direct HTTP/render coverage from inherited or conditional templates. A passing workflow suite does not establish visual coverage of every possible data/permission combination.

| Template | Direct route(s) | Coverage |
|---|---|---|
| `auth/challenge.blade.php` | `security.challenge` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `auth/confirm.blade.php` | `security.confirm` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `auth/login.blade.php` | `login` | HTTP render + five viewport scan |
| `auth/recovery.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `auth/security-layout.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `auth/security.blade.php` | `security.index` | HTTP render + five viewport scan |
| `auth/temporary.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/applications/agreement.blade.php` | `business.applications.agreement` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/applications/create.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/applications/index.blade.php` | `business.applications.index` | HTTP render + five viewport scan |
| `business/applications/show.blade.php` | `business.applications.viability`, `business.applications.recommendation`, `business.applications.approval`, `business.applications.disbursement`, `business.applications.monitoring`, `business.applications.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/categories.blade.php` | `business.categories` | HTTP render + five viewport scan |
| `business/partials/actions.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/partials/budget-row.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/partials/payment-fields.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/partials/signatures.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/partials/style.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `business/report.blade.php` | `business.reports` | HTTP render + five viewport scan |
| `committees/create.blade.php` | `committees.create` | HTTP render + five viewport scan |
| `committees/edit.blade.php` | `committees.edit` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `committees/index.blade.php` | `committees.index` | HTTP render + five viewport scan |
| `committees/show.blade.php` | `committees.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `components/business-field.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `dashboard/index.blade.php` | `dashboard` | HTTP render + five viewport scan |
| `dashboard/partials/in-development.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `finance/donations/create.blade.php` | `finance.donations.create` | HTTP render + five viewport scan |
| `finance/donations/edit.blade.php` | `finance.donations.edit` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `finance/donations/index.blade.php` | `finance.donations.index` | HTTP render + five viewport scan |
| `finance/donations/show.blade.php` | `finance.donations.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `finance/expenses/create.blade.php` | `finance.expenses.create` | HTTP render + five viewport scan |
| `finance/expenses/edit.blade.php` | `finance.expenses.edit` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `finance/expenses/index.blade.php` | `finance.expenses.index` | HTTP render + five viewport scan |
| `finance/expenses/purchase-kind.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `finance/expenses/show.blade.php` | `finance.expenses.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `finance/funds/create.blade.php` | `finance.funds.create` | HTTP render + five viewport scan |
| `finance/funds/edit.blade.php` | `finance.funds.edit` | HTTP render + five viewport scan |
| `finance/funds/index.blade.php` | `finance.funds.index` | HTTP render + five viewport scan |
| `finance/funds/show.blade.php` | `finance.funds.show` | HTTP render + five viewport scan |
| `finance/reports/index.blade.php` | `finance.reports.index` | HTTP render + five viewport scan |
| `finance/restricted-funds/index.blade.php` | `finance.restricted-funds.index` | HTTP render + five viewport scan |
| `finance/transactions/index.blade.php` | `finance.transactions.index` | HTTP render + five viewport scan |
| `finance/transactions/show.blade.php` | `finance.transactions.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `layouts/app.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `members/create.blade.php` | `members.create` | HTTP render + five viewport scan |
| `members/edit.blade.php` | `members.edit` | HTTP render + five viewport scan |
| `members/index.blade.php` | `members.index` | HTTP render + five viewport scan |
| `members/show.blade.php` | `members.show` | HTTP render + five viewport scan |
| `operations/meetings/create.blade.php` | `operations.meetings.create`, `operations.meetings.edit` | HTTP render + five viewport scan |
| `operations/meetings/index.blade.php` | `operations.meetings.index` | HTTP render + five viewport scan |
| `operations/meetings/show.blade.php` | `operations.meetings.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/action-fields.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/create.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/delivery-forms.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/finance-payments.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/form.blade.php` | `operations.procurement.create`, `operations.procurement.edit` | HTTP render + five viewport scan |
| `operations/procurement/history.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/index.blade.php` | `operations.procurement.index` | HTTP render + five viewport scan |
| `operations/procurement/item-row.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/money-fields.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/order.blade.php` | `operations.procurement.order` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/payment-forms.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/reports.blade.php` | `operations.procurement.reports` | HTTP render + five viewport scan |
| `operations/procurement/settings.blade.php` | `operations.procurement.settings` | HTTP render + five viewport scan |
| `operations/procurement/show.blade.php` | `operations.procurement.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/style.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/procurement/supplier-fields.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `operations/tasks/create.blade.php` | `operations.tasks.create`, `operations.tasks.edit` | HTTP render + five viewport scan |
| `operations/tasks/index.blade.php` | `operations.tasks.index` | HTTP render + five viewport scan |
| `operations/tasks/show.blade.php` | `operations.tasks.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `projects/action-fields.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `projects/budget-line.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `projects/categories.blade.php` | `projects.categories` | HTTP render + five viewport scan |
| `projects/finance-payments.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `projects/form.blade.php` | `projects.create`, `projects.edit` | HTTP render + five viewport scan |
| `projects/index.blade.php` | `projects.index` | HTTP render + five viewport scan |
| `projects/money-fields.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `projects/reports.blade.php` | `projects.reports` | HTTP render + five viewport scan |
| `projects/show.blade.php` | `projects.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `projects/style.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/agreement.blade.php` | `qard.agreement` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/create.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/index.blade.php` | `qard.index` | HTTP render + five viewport scan |
| `qard/partials/feedback.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/partials/payment.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/partials/style.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/repayment.blade.php` | `qard.repayment`, `qard.recovery` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `qard/show.blade.php` | `qard.recycling`, `qard.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `records/audit/index.blade.php` | `records.audit.index` | HTTP render + five viewport scan |
| `records/audit/print.blade.php` | `records.audit.export` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `records/audit/show.blade.php` | `records.audit.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `records/audit/style.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `records/documents/create.blade.php` | `records.documents.create` | HTTP render + five viewport scan |
| `records/documents/index.blade.php` | `records.documents.index` | HTTP render + five viewport scan |
| `records/documents/show.blade.php` | `records.documents.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `records/reports/index.blade.php` | `records.reports.index` | HTTP render + five viewport scan |
| `records/reports/print.blade.php` | `records.reports.export` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `records/reports/show.blade.php` | `records.reports.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `settings/index.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `shared/action-feedback.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `shared/archive-committee.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `shared/archive-user.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `shared/module-pending.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `system/notifications/index.blade.php` | `notifications.index` | HTTP render + five viewport scan |
| `system/search/index.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `users/create.blade.php` | `users.create` | HTTP render + five viewport scan |
| `users/edit.blade.php` | `users.edit` | HTTP render + five viewport scan |
| `users/index.blade.php` | `users.index` | HTTP render + five viewport scan |
| `users/roles.blade.php` | `users.roles` | HTTP render + five viewport scan |
| `users/show.blade.php` | `users.show` | HTTP render + five viewport scan |
| `welfare/agreements/create.blade.php` | `welfare.applications.agreement.create` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/agreements/print.blade.php` | `welfare.applications.agreement.print` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/agreements/show.blade.php` | `welfare.applications.agreement.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/applications/create.blade.php` | `welfare.applications.create` | HTTP render + five viewport scan |
| `welfare/applications/index.blade.php` | `welfare.applications.index` | HTTP render + five viewport scan |
| `welfare/applications/show.blade.php` | `welfare.applications.show` | HTTP render + five viewport scan |
| `welfare/assistance/create.blade.php` | `welfare.assistance.create` | HTTP render + five viewport scan |
| `welfare/assistance/edit.blade.php` | `welfare.assistance.edit` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/assistance/index.blade.php` | `welfare.assistance.index` | HTTP render + five viewport scan |
| `welfare/assistance/partials/monitoring.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/assistance/payments/create.blade.php` | `welfare.assistance.payments.create` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/assistance/payments/show.blade.php` | `welfare.assistance.payments.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/assistance/reviews/create.blade.php` | `welfare.assistance.reviews.create` | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/assistance/reviews/index.blade.php` | `welfare.assistance.reviews.index` | HTTP render + five viewport scan |
| `welfare/assistance/show.blade.php` | `welfare.assistance.show` | HTTP render + five viewport scan |
| `welfare/beneficiaries/create.blade.php` | `welfare.beneficiaries.create` | HTTP render + five viewport scan |
| `welfare/beneficiaries/edit.blade.php` | `welfare.beneficiaries.edit` | HTTP render + five viewport scan |
| `welfare/beneficiaries/index.blade.php` | `welfare.beneficiaries.index` | HTTP render + five viewport scan |
| `welfare/beneficiaries/partials/form.blade.php` | Included, dynamic view, or standalone preview | Shared theme + Blade parse; conditional/data-specific visual review remains |
| `welfare/beneficiaries/show.blade.php` | `welfare.beneficiaries.show` | HTTP render + five viewport scan |
| `welfare/verification/create.blade.php` | `welfare.verification.create` | HTTP render + five viewport scan |
| `welfare/verification/index.blade.php` | `welfare.verification.index` | HTTP render + five viewport scan |
| `welfare/verification/show.blade.php` | `welfare.verification.show` | Shared theme + Blade parse; conditional/data-specific visual review remains |

