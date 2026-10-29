const fs=require('node:fs');
const path=require('node:path');
const {chromium}=require('playwright');
(async()=>{
 const receipt=JSON.parse(fs.readFileSync('tmp/mysql-test-target.json','utf8'));
 if(!receipt.realistic_seeded||!/^foundation_management_test_/.test(receipt.database))throw Error('Populated QA database required.');
 const browser=await chromium.launch({headless:true,executablePath:process.env.UI_BROWSER_PATH});
 const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[],checks=[];
 page.on('pageerror',e=>errors.push(e.message));
 fs.mkdirSync('output/organization-exports',{recursive:true});
 const go=async url=>{const response=await page.goto('http://127.0.0.1:8011'+url,{waitUntil:'networkidle'});if(response.status()!==200)throw Error(url+' returned '+response.status());};
 const download=async(kind,format,folder='output/organization-exports')=>{
  const menu=page.locator('.directory-export-menu');if(!await menu.evaluate(e=>e.open))await menu.locator('summary').click();
  const link=menu.getByRole('link',{name:format==='pdf'?'Download PDF':'Download Excel (.xlsx)',exact:true});
  const [file]=await Promise.all([page.waitForEvent('download'),link.click()]);
  if(await file.failure())throw Error(await file.failure());
  await file.saveAs(path.join(folder,'foundation-'+kind+'.'+format));checks.push(kind+' '+format+' downloaded through Export button');
 };
 try{
  await go('/login');await page.locator('[name=email]').fill('qa.admin@example.test');await page.locator('[name=password]').fill('QA-test-only-2026!');
  await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('main form').first().evaluate(f=>f.requestSubmit())]);
  for(const [kind,url] of [['members','/members'],['committees','/committees'],['users','/users'],['roles','/users/roles']]){
   await go(url);await download(kind,'pdf');await download(kind,'xlsx');
  }
  await go('/users?role=qard-officer');
  if(!await page.locator('main tbody').innerText().then(t=>t.includes('Amina Rahman')&&!t.includes('Usman Tariq')))throw Error('User role filter differs from expected source.');checks.push('Users filter matches secondary roles');
  await go('/committees?type=decision');if(!await page.locator('.committee-grid').innerText().then(t=>t.includes('Decision Committee')&&!t.includes('Community Implementation Committee')))throw Error('Committee type filter failed');checks.push('Committee type filter applies on server');
  await go('/members?search=Shahid%20Hussain&status=active');
  await page.locator('.directory-export-menu summary').click();
  const href=await page.getByRole('link',{name:'Download Excel (.xlsx)',exact:true}).getAttribute('href');if(!href.includes('search=Shahid')||!href.includes('status=active'))throw Error('Export lost filters');checks.push('Export links retain search and status filters');
  await page.setViewportSize({width:390,height:844});if(!await page.locator('.directory-export-menu').evaluate(e=>e.open))await page.locator('.directory-export-menu summary').click();
  await page.screenshot({path:'tmp/organization-exports/export-menu-mobile.png',fullPage:true});await page.keyboard.press('Escape');if(await page.locator('.directory-export-menu').evaluate(e=>e.open))throw Error('Escape did not close export menu');checks.push('Export menu works at mobile width and closes with Escape');
 }catch(e){errors.push(e.message);}
 fs.writeFileSync('tmp/organization-exports/browser-checks.json',JSON.stringify({checks,errors},null,2));console.log(JSON.stringify({checks,errors},null,2));await browser.close();if(errors.length)process.exitCode=1;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
