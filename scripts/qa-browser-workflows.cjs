const fs=require('node:fs');
const {chromium}=require('playwright');
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.UI_BROWSER_PATH});
 const page=await browser.newPage({viewport:{width:1440,height:1000}});
 const prior=process.env.QA_RESUME_USER&&fs.existsSync('tmp/qa-browser-workflows.json')?JSON.parse(fs.readFileSync('tmp/qa-browser-workflows.json','utf8')):null;
 const checks=prior?.checks||[],errors=(prior?.errors||[]).filter(e=>!e.message.startsWith('locator.'));page.on('pageerror',e=>errors.push({url:page.url(),message:e.message}));
 const run=Date.now().toString(36),memberName='Nadia Rahman Khan '+run,committeeName='Community Health Outreach '+run,fundName='Emergency Community Support '+run;
 const go=async path=>{const response=await page.goto('http://127.0.0.1:8011'+path,{waitUntil:'networkidle'});if(response.status()!==200)throw Error('GET '+path+' returned '+response.status());};
 const submit=async()=>{const form=page.locator('main form[method=post],main form[method=POST]').first();await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),form.evaluate(f=>f.requestSubmit())]);};
 const contains=async text=>{if(!(await page.locator('main').innerText()).includes(text))throw Error('Missing expected text: '+text+' on '+page.url());};
 try{
  await go('/login');await page.locator('[name=email]').fill('qa.admin@example.test');await page.locator('[name=password]').fill('QA-test-only-2026!');await submit();await contains('Dashboard');checks.push('Browser login and authenticated dashboard');
  if(!process.env.QA_RESUME_USER){
  await go('/members/create');await page.locator('[name=full_name]').fill(memberName);await page.locator('[name=phone]').fill('03001234567');await page.locator('[name=cnic]').fill('12345-'+Date.now().toString().slice(-7)+'-1');await page.locator('[name=email]').fill('qa.member.'+run+'@example.test');await submit();await contains(memberName);checks.push('Create member through form');
  await go('/members?search='+encodeURIComponent(memberName));await contains(memberName);checks.push('Complete multi-word member name search');
  await go('/members?search='+run);await contains(memberName);checks.push('Partial member-name search');
  await go('/committees/create');await page.locator('[name=name]').fill(committeeName);await page.locator('label[for=typeImplementation]').click();await page.locator('[name=description]').fill('Arrange family health visits and coordinate community awareness sessions. Synthetic QA scenario.');await submit();await contains(committeeName);checks.push('Create committee through form');
  await go('/finance/funds/create');await page.locator('[name=name]').fill(fundName);await page.locator('[name=purpose]').selectOption('general_welfare');await page.locator('[name=opening_balance]').fill('1000.00');await submit();await contains(fundName);checks.push('Create unrestricted fund with PKR 1,000 opening balance');
  await go('/finance/donations/create');await page.locator('[name=donor_name]').fill('Huma Saeed');await page.locator('[name=amount]').fill('10.25');const option=page.locator('[name=fund_id] option').filter({hasText:fundName});await page.locator('[name=fund_id]').selectOption(await option.getAttribute('value'));await submit();await contains('Huma Saeed');checks.push('Record PKR 10.25 donation');
  await go('/finance/transactions');await contains('10.25');checks.push('Donation appears in Finance ledger');
  await go('/operations/tasks/create');await page.locator('[name=title]').fill('Confirm family health visit appointments');await page.locator('[name=description]').fill('Call the sample households and confirm the planned field visit times. Synthetic QA scenario.');await submit();await contains('Confirm family health visit appointments');checks.push('Create task through form');
  }
  await go('/account/security/confirm');await page.locator('[name=password]').fill('QA-test-only-2026!');await submit();checks.push('Recent-password confirmation');
  await go('/users/create');await page.locator('[name=name]').fill('Noman Akbar');await page.locator('[name=email]').fill('noman.akbar.'+run+'@example.test');await page.locator('[name=password]').fill('QA-viewer-only-2026!');await page.locator('[name=password_confirmation]').fill('QA-viewer-only-2026!');await page.locator('.role-option').filter({has:page.getByText('Viewer',{exact:true})}).locator('label').click();await submit();await contains('Noman Akbar');await contains('Viewer');checks.push('Create user with Viewer role after password confirmation');
  await go('/dashboard');await page.getByRole('button',{name:'Switch to dark theme'}).click();await page.reload({waitUntil:'networkidle'});if(await page.locator('html').getAttribute('data-theme')!=='dark')throw Error('Theme not retained');checks.push('Dark theme persists');
  await page.setViewportSize({width:390,height:844});await page.getByRole('button',{name:'Toggle navigation'}).click();if(!(await page.locator('#sidebar').evaluate(e=>e.classList.contains('open'))))throw Error('Mobile menu failed');await page.keyboard.press('Escape');checks.push('Mobile drawer opens and closes');
  await page.setViewportSize({width:1440,height:1000});await page.locator('.sidebar-bottom form[action$="/logout"] button').click();await page.waitForURL('**/login');checks.push('Logout returns to login');
 }catch(e){errors.push({url:page.url(),message:e.message});}
 await page.screenshot({path:'tmp/qa-browser-final.png',fullPage:true});
 const report={checks:[...new Set(checks)],errors};fs.writeFileSync('tmp/qa-browser-workflows.json',JSON.stringify(report,null,2));console.log(JSON.stringify(report,null,2));await browser.close();if(errors.length)process.exitCode=1;
})().catch(e=>{console.error(e);process.exitCode=1});
