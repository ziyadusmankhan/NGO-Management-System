# Foundation UI redesign

The redesign is isolated on `feature/complete-ui-redesign`. The original working application remains available on `main`; no database schema, financial service, approval rule or user password was changed.

## Design system

The shared theme uses forest green (#173F35), white cards, subdued neutral surfaces, 12px card corners, 8px controls, a 14px body scale and tabular financial numerals. Inter is self-hosted with its SIL Open Font License. System fonts remain the fallback; Urdu uses the system's Arabic-script font fallback with space for the full organization name.

Application screens load `public/css/foundation-ui.css` after their specialized layout CSS. Authentication and printable documents have their own shared themes. The existing embedded static styles were mechanically extracted into `public/css/views`; this retains specialized form grids, agreement signatures and workflow-specific structures while the central theme defines the common visual language.

Reusable Blade components: `x-ui.metric`, `x-ui.panel`, and `x-ui.empty`. Compatibility adapters cover the existing form, table, button, badge, card and alert markup throughout the modules. They allow existing field names, old input values, validation, routes, CSRF tokens and form methods to remain intact.

## Interface changes

- Sidebar: full Urdu wordmark, collapsible groups, desktop hide/show, mobile drawer, Escape handling, focus trap and active-page indication.
- Header: working global search, mobile search shortcut, notification count and direct account-security link. Removed the inert Help button.
- Dashboard: compact operational cards, proportional fund balance rows, linked project status counts, transaction detail links, bounded recent activity, task priority/status context and consistent Welfare/Qard/Business summaries.
- All source modules inherit consistent financial tables, controls, semantic badge colors, focus rings and responsive card/form layouts.
- Static navigation/action links mirror declared route permissions. Member/Committee links and role management use their existing permission names where routes do not declare them.
- Forms retain native submission and validation. A repeated click during a normal POST is suppressed without disabling named submitter values. Browser back navigation clears the busy state.
- Scrollable tables expose a keyboard-focusable region. Amount columns receive right alignment and tabular numerals. Visible status text remains alongside semantic colors.
- Authentication/recovery and standalone report/agreement printouts use the same palette and type hierarchy.

No React, Vue, SPA router or production JavaScript dependency was introduced. The previously empty package.json is now valid JSON and provides an optional browser-check script. Browser checks use Playwright from the development environment; the application does not need it to run.

## Modules covered

Dashboard; Members; Committees; Users and Roles; Donations; Funds and Restricted Funds; Expenses and Transactions; Finance Reports; Beneficiaries; Welfare applications, verification, agreements, assistance, payments and follow-ups; Qard; Small Business; Projects; Procurement and its policy screen; Tasks; Meetings; Documents; Records Reports; Audit Trail; Search; Notifications; Account Security; Login and recovery; Settings preview.

The complete source inventory is in `ui-redesign-inventory.md`. Settings remains a clearly identified preview; this UI task does not add its deferred persistence backend. Existing unsupported controls are not proof of new functionality.

## Verification and local review

Run the existing Laravel test suite. For isolated browser previews, set `UI_EXPORT_PREVIEWS=1` and run `tests/Feature/UiRedesignTest.php`. This uses an in-memory database and synthetic test records, then saves responses under ignored `tmp/ui-previews`.

Run `node scripts/ui-browser-check.cjs` with Playwright available. Set `UI_BROWSER_PATH` to an installed Chrome executable if a Playwright browser is not installed. `UI_PAGES` can restrict checks to comma-separated HTML filenames. The script serves intercepted local assets and synthetic responses, checks 1440/1280/1024/768/390px widths, and saves local screenshots/results under ignored `tmp`.

`php scripts/ui-lint-views.php` parses every compiled Blade template. `php scripts/ui-inventory.php` prints route/view dependencies without changing application data. The extraction, link authorization and directive-normalization scripts were one-time migration helpers; do not rerun them as part of deployment.

Manual acceptance: compare two different roles; open every sidebar module; exercise a validation failure; use the keyboard to open/close the mobile drawer; add/remove a project budget row; open a long financial table on a phone; verify a printed agreement's signature space; verify a private download and a financial approval using the existing testing guides.

## Limits and follow-up

### Theme and fund graph update

Dark-theme coverage correction: 339 legacy text/surface/border colors in the extracted module CSS and inline styles now use theme variables with the original light values as fallbacks. `foundation-legacy-tokens.css` supplies the dark palette and restores the original palette for printing. This covers the older Member summary cards, Committee cards/toolbar/footer, User filters/role badges, Finance forms, Welfare, Programs, Operations, Records and Settings layouts. The previous generic inline-background override was removed so success/warning/danger meaning is retained. Status labels and row actions no longer break mid-word. Search fields with leading icons keep sufficient input padding.

Correction verification: scanned 63 synthetic pages at five widths in dark mode. Seven pages exposed remaining Bootstrap white table cells, light alerts and a secondary form button; after correction all 35 targeted viewport checks passed with zero white surfaces, overflow, missing labels, tiny text or JavaScript errors. The Members/Committees/Users light-mode checks also passed all 15 viewport checks. All 136 Blade templates parse. The full regression run passed 372 tests; one Qard concurrency case timed out during worker startup. Rerunning the complete QardConcurrencyTest group in isolation passed all four tests / 29 assertions. No financial code or database records changed. The color migration script is a development helper, not a deployment command.

The header theme button beside Notifications switches between light and dark, saves the browser preference and synchronizes other tabs. The preference also applies to login and Account Security; print output stays light. Metric cards have a soft shimmer, buttons and icons have short hover transitions, text links animate their underline, and the account menu uses a keyboard-accessible details dropdown. Reduced-motion preferences disable movement. Effects were inspired by the requested Prismic CSS hover examples (https://prismic.io/blog/css-hover-effects), adapted to the existing forest-green palette.

Fund Overview now uses a dependency-free SVG bar graph of current ledger balances with a zero baseline, negative balance support, fund links and exact PKR values in its legend. Geometry alone uses fractional arithmetic; displayed currency still uses the existing Money helper. This graph does not represent commitments or time trends.

Verification: 18 focused tests / 196 assertions and 2 render tests / 189 assertions passed. The dashboard passed all five viewport sizes with no overflow, missing labels, tiny text or JavaScript errors. Browser checks verified theme persistence, switching back to light, account dropdown, graph presence and mobile drawer behavior.

Verification receipt (29 October 2026): all 135 Blade templates parse. The initial broad browser pass exercised 63 synthetic pages at five widths (315 checks). Follow-up checks corrected missing accessible names, small text, the Members script error and project overflow; the final project run passed all five widths with no overflow, unlabeled controls, small text or JavaScript errors. Mobile drawer open/Escape-close checks passed. The focused final PHP run passed 16 tests with 340 assertions after fixing the empty-fund dashboard calculation. The final full application suite passed **373 tests with 3,074 assertions**, in 221.54 seconds.

Automated render/viewport coverage does not replace manually checking every conditional approval/payment form with populated records. Those templates inherit the shared styles and remain covered by the existing backend tests where available. No claim of complete WCAG certification or every screen-reader/browser combination is made.

During inspection, Member/Committee resource routes and the role-management routes did not declare the same permission middleware as newer modules. This redesign restricts their visible links, but server-side access should receive a separate security review; hiding links cannot enforce access. This pre-existing issue is recorded explicitly rather than silently changing backend authorization during a visual redesign.
