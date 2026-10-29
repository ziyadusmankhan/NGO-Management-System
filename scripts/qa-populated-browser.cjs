// Browser acceptance checks against the guarded, populated QA server only.
const fs = require('node:fs');
const { chromium } = require('playwright');
const base = 'http://127.0.0.1:8011';

(async () => {
  const seeded = JSON.parse(fs.readFileSync('tmp/qa-realistic-data.json', 'utf8'));
  if (!seeded.synthetic_only || !/^foundation_management_test_/.test(seeded.database)) throw Error('Synthetic QA receipt required.');
  const browser = await chromium.launch({ headless: true, executablePath: process.env.UI_BROWSER_PATH });
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  const report = { checks: [], errors: [], exports: [], darkSurfaces: [], pages: [] };
  fs.mkdirSync('tmp/qa-exports', { recursive: true });
  page.on('pageerror', e => report.errors.push({ page: page.url(), message: e.message }));
  page.on('dialog', d => d.accept());
  const save = () => fs.writeFileSync('tmp/qa-populated-browser.json', JSON.stringify(report, null, 2));
  const check = async (name, work) => {
    try { await work(); report.checks.push(name); }
    catch (e) { report.errors.push({ page: page.url(), check: name, message: e.message }); }
    save();
  };
  const go = async path => {
    const response = await page.goto(base + path, { waitUntil: 'networkidle' });
    if (response.status() !== 200) throw Error(path + ' returned HTTP ' + response.status());
    if (new URL(page.url()).pathname === '/login' && path !== '/login') throw Error('Unexpected login redirect for ' + path);
    if (/Internal Server Error|SQLSTATE\[|Undefined variable|Attempt to read property/.test(await page.locator('body').innerText())) throw Error('Application error rendered on ' + path);
  };
  const contains = async text => {
    if (!(await page.locator('main').innerText()).includes(text)) throw Error('Missing text: ' + text);
  };
  const submit = async form => {
    form = form || page.locator('main form[method=post],main form[method=POST]').first();
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), form.evaluate(f => f.requestSubmit())]);
  };
  const login = async email => {
    await go('/login');
    await page.locator('[name=email]').fill(email);
    await page.locator('[name=password]').fill('QA-test-only-2026!');
    await submit(); await contains('Dashboard');
  };
  const logout = async () => {
    await page.locator('.sidebar-bottom form[action$="/logout"] button').click(); await page.waitForURL('**/login');
  };
  try {
    await login('qa.admin@example.test');
    const lists = ['/dashboard', '/members', '/committees', '/users', '/users/roles', '/finance/donations', '/finance/funds', '/finance/expenses',
      '/finance/restricted-funds', '/finance/transactions', '/finance/reports', '/welfare/beneficiaries', '/welfare/applications', '/welfare/verification',
      '/welfare/assistance', '/welfare/assistance/reviews', '/qard', '/business/applications', '/business/categories', '/business/reports', '/projects', '/projects/categories',
      '/projects/reports', '/operations/tasks', '/operations/procurement', '/operations/procurement/settings', '/operations/procurement/reports',
      '/operations/meetings', '/records/documents', '/records/reports', '/records/audit', '/settings', '/account/security', '/notifications'];
    await page.evaluate(() => localStorage.setItem('foundation-theme', 'dark'));
    await go('/dashboard');
    const themeButton = page.getByRole('button', { name: 'Switch to dark theme' });
    if (await themeButton.count()) await themeButton.click();
    for (const path of [...lists, ...seeded.detail_paths]) {
      await check('Populated screen ' + path, async () => {
        await go(path); report.pages.push(path);
        const white = await page.locator('main .card,main .audit-card,main input:not([type=hidden]),main select,main textarea').evaluateAll(elements => elements.filter(el => {
          const r = el.getBoundingClientRect(), c = getComputedStyle(el).backgroundColor.match(/\d+/g);
          return r.width > 100 && r.height > 25 && c && c.slice(0,3).every(x => +x > 230);
        }).map(el => ({ tag: el.tagName, class: el.className, text: el.textContent.trim().slice(0,60) })));
        if (white.length) report.darkSurfaces.push({ path, white });
      });
    }
    await check('Dashboard displays actual project, task and fund data', async () => {
      await go('/dashboard');
      const active = page.locator('.ui-metric').filter({ has: page.getByText('Active Projects', { exact: true }) });
      if ((await active.locator('.ui-metric-value').innerText()).trim() !== '1') throw Error('Active project count differs from In Progress records.');
      await contains('Reconcile October cash receipts');
      await contains('General Operations Fund'); await page.screenshot({ path: 'tmp/qa-dashboard-dark.png', fullPage: true });
    });
    await check('Global search finds a real saved project', async () => { await go('/search?q=School%20Access'); await contains('Kasso School Access Path Repair'); });
    await check('Global search finds an exact encrypted beneficiary phone', async () => { await go('/search?q=03000000200'); await contains('Zainab Bibi'); });
    await check('Notifications can be marked read and dismissed', async () => {
      await go('/notifications');
      if (!await page.locator('form[action$="/notifications/change"]').count()) {
        await go('/notifications?scope=dismissed');
        await submit(page.locator('form[action$="/notifications/change"]').filter({ has: page.locator('input[name=action][value=restore]') }).first());
        await go('/notifications');
      }
      if (!await page.locator('input[name=action][value=read]').count()) await submit(page.locator('form[action$="/notifications/change"]').filter({ has: page.locator('input[name=action][value=unread]') }).first());
      const form = page.locator('form[action$="/notifications/change"]').filter({ has: page.locator('input[name=action][value=read]') }).first();
      if (!await form.count()) throw Error('Expected seeded follow-up notification.');
      await submit(form);
      if (!await page.locator('form input[name=action][value=unread]').count()) throw Error('Read state did not persist.');
      const dismiss = page.locator('form[action$="/notifications/change"]').filter({ has: page.locator('input[name=action][value=dismiss]') }).first();
      await submit(dismiss); await go('/notifications?scope=dismissed');
      if (!await page.locator('input[name=action][value=restore]').count()) throw Error('Dismissed state did not persist.');
    });
    await check('Schedule a meeting through the browser', async () => {
      await go('/operations/meetings/create'); await page.locator('[name=title]').fill('Volunteer outreach briefing');
      await page.locator('[name=agenda]').fill('Confirm volunteer routes, safeguarding reminders and next week field visits.');
      await page.locator('[name=location]').fill('Community office, Kasso');
      const options = await page.locator('[name="attendees[]"] option').evaluateAll(es => es.slice(0,2).map(e => e.value));
      await page.locator('[name="attendees[]"]').selectOption(options); await page.locator('[name=quorum_required]').fill('2');
      await submit(); await contains('Volunteer outreach briefing');
    });
    let privatePath;
    await check('Upload and download a private document through the browser', async () => {
      await go('/records/documents/create'); await page.locator('[name=title]').fill('Volunteer outreach briefing agenda');
      await page.locator('[name=category]').selectOption('Minutes');
      await page.locator('[name=document]').setInputFiles('tmp/qa-sample-document.pdf');
      await submit(); await contains('Volunteer outreach briefing agenda'); privatePath = new URL(page.url()).pathname;
      const href = await page.getByRole('link', { name: 'Download v1' }).getAttribute('href');
      const response = await context.request.get(href);
      if (response.status() !== 200 || !(await response.body()).subarray(0,5).equals(Buffer.from('%PDF-'))) throw Error('Private PDF download failed.');
      if (!(response.headers()['cache-control'] || '').includes('no-store')) throw Error('Private evidence missing no-store.');
    });
    await check('All Records snapshots produce printable and genuine XLSX exports', async () => {
      await go('/records/reports');
      await page.locator('select[name=type]').selectOption('business'); await submit();
      const startedCard = page.locator('main .audit-card').filter({ has: page.getByText('Businesses started', { exact: true }) });
      if ((await startedCard.locator('h2').innerText()).trim() !== '1') throw Error('Started business missing from newly generated snapshot.');
      await go('/records/reports');
      const paths = await page.locator('main a[href]').evaluateAll(es => [...new Set(es.map(e => new URL(e.href).pathname).filter(p => /^\/records\/reports\/\d+$/.test(p)))]);
      if (paths.length < 8) throw Error('Expected all eight report types; found ' + paths.length);
      fs.mkdirSync('tmp/qa-exports', { recursive: true });
      for (const path of paths) {
        const print = await context.request.get(base + path + '/export?format=print');
        if (print.status() !== 200 || !(await print.text()).includes('Retained snapshot')) throw Error('Print report failed: ' + path);
        const xlsx = await context.request.get(base + path + '/export?format=xlsx'); const bytes = await xlsx.body();
        if (xlsx.status() !== 200 || bytes[0] !== 80 || bytes[1] !== 75) throw Error('XLSX report failed: ' + path);
        const file = 'tmp/qa-exports/report-' + path.split('/').pop() + '.xlsx'; fs.writeFileSync(file, bytes); report.exports.push(file);
      }
    });
    await check('Audit list details and filtered exports work with retained events', async () => {
      await go('/records/audit');
      const detail = await page.locator('main a[href]').evaluateAll(es => es.map(e => new URL(e.href).pathname).find(p => /^\/records\/audit\/\d+$/.test(p)));
      if (!detail) throw Error('Audit events are missing.'); await go(detail);
      const response = await context.request.get(base + '/records/audit/export?format=xlsx&module=qard');
      const bytes = await response.body();
      if (response.status() !== 200 || bytes[0] !== 80 || bytes[1] !== 75) throw Error('Audit export failed.');
      fs.writeFileSync('tmp/qa-exports/audit-qard.xlsx', bytes); report.exports.push('tmp/qa-exports/audit-qard.xlsx');
    });
    await check('Populated mobile navigation, search and dark theme work', async () => {
      await page.setViewportSize({ width: 390, height: 844 }); await go('/members');
      await page.getByRole('button', { name: 'Toggle navigation' }).click();
      if (!await page.locator('#sidebar').evaluate(e => e.classList.contains('open'))) throw Error('Mobile navigation did not open.');
      await page.keyboard.press('Escape'); await page.screenshot({ path: 'tmp/qa-members-mobile.png', fullPage: true });
      await page.setViewportSize({ width: 1440, height: 1000 });
    });
    await logout();
    await check('Viewer cannot open administration or write forms', async () => {
      await login('usman.tariq@example.test');
      for (const path of ['/members/create', '/committees/create', '/users/roles', '/users/create', '/finance/funds/create', '/operations/tasks/create', '/records/audit']) {
        const response = await page.goto(base + path, { waitUntil: 'networkidle' });
        if (response.status() !== 403) throw Error('Viewer received ' + response.status() + ' for ' + path);
      }
      await go('/search?q=Zainab');
      if ((await page.locator('main').innerText()).includes('APP-')) throw Error('Viewer search leaked protected application.');
      if (privatePath) { const r = await page.goto(base + privatePath); if (r.status() !== 404) throw Error('Private standalone metadata leaked to Viewer.'); }
    });
    await go('/dashboard'); await logout();
    await check('Auditor can read audit and shared policy but cannot read unshared document', async () => {
      await login('maryam.shah@example.test'); await go('/records/audit'); await go('/records/documents');
      await contains('Safeguarding and confidentiality policy');
      if (privatePath) { const r = await page.goto(base + privatePath); if (r.status() !== 404) throw Error('Unshared document leaked to Auditor.'); }
    });
    await go('/dashboard'); await logout();
  } finally {
    save(); console.log(JSON.stringify({ checks: report.checks.length, pages: report.pages.length, exports: report.exports.length, errors: report.errors, darkSurfaces: report.darkSurfaces }, null, 2));
    await browser.close();
  }
  if (report.errors.length) process.exitCode = 1;
})().catch(e => { console.error(e.message); process.exitCode = 1; });
