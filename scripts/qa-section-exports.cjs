const fs=require('node:fs');const path=require('node:path');const {chromium}=require('playwright');
(async()=>{
 const receipt=JSON.parse(fs.readFileSync('tmp/mysql-test-target.json','utf8'));if(!receipt.realistic_seeded||!/^foundation_management_test_/.test(receipt.database))throw Error('Isolated populated QA target required');
 const dir='output/section-exports';fs.mkdirSync(dir,{recursive:true});fs.mkdirSync('tmp/section-exports',{recursive:true});
 const browser=await chromium.launch({headless:true,executablePath:process.env.UI_BROWSER_PATH});const page=await browser.newPage({viewport:{width:1440,height:1000}});const checks=[],errors=[];page.on('pageerror',e=>errors.push(e.message));
 const go=async url=>{const r=await page.goto('http://127.0.0.1:8011'+url,{waitUntil:'networkidle'});if(r.status()!==200)throw Error(url+' HTTP '+r.status());};
 const save=async(kind,format,click)=>{const[d]=await Promise.all([page.waitForEvent('download'),click()]);if(await d.failure())throw Error(await d.failure());await d.saveAs(path.join(dir,kind+'.'+format));checks.push(kind+' '+format);};
 try{
 await go('/login');await page.locator('[name=email]').fill('qa.admin@example.test');await page.locator('[name=password]').fill('QA-test-only-2026!');await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.locator('main form').first().evaluate(f=>f.requestSubmit())]);
 for(const[kind,url]of[['qard','/qard'],['business','/business/applications'],['projects','/projects'],['tasks','/operations/tasks'],['meetings','/operations/meetings'],['procurement','/operations/procurement'],['documents','/records/documents'],['reports','/records/reports'],['audit','/records/audit?reference=WFA-00005']]){
  await go(url);const menu=page.locator('.directory-export-menu').first();
  for(const[format,label]of[['pdf','Download PDF'],['xlsx','Download Excel (.xlsx)']]){if(!await menu.evaluate(e=>e.open))await menu.locator('summary').click();await save(kind,format,()=>menu.getByRole('link',{name:label,exact:true}).click());}
 }
 for(const[kind,url]of[['project-report','/projects/reports'],['business-report','/business/reports'],['procurement-report','/operations/procurement/reports']]){
  await go(url);for(const format of ['pdf','xlsx'])await save(kind,format,()=>page.locator('button[name=format][value='+format+']').click());
 }
 await go('/records/reports');let report=page.locator('a[href*="/records/reports/"]').filter({hasText:/RPT-/}).first();
 if(!await report.count()){await page.locator('select[name=type]').selectOption('operations');await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),page.getByRole('button',{name:'Generate live report'}).click()]);}else await Promise.all([page.waitForNavigation({waitUntil:'networkidle'}),report.click()]);
 await save('retained-snapshot','pdf',()=>page.getByRole('link',{name:'Download A4 PDF',exact:true}).click());await save('retained-snapshot','xlsx',()=>page.getByRole('link',{name:'Download XLSX',exact:true}).click());
 await go('/operations/tasks?priority=high');const menu=page.locator('.directory-export-menu').first();await menu.locator('summary').click();const href=await menu.getByRole('link',{name:'Download PDF',exact:true}).getAttribute('href');if(!href.includes('priority=high'))throw Error('Filter lost');checks.push('Task filter preserved');
 await page.setViewportSize({width:390,height:844});await page.waitForFunction(()=>{const r=document.querySelector('.directory-export-options').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;});await page.screenshot({path:'tmp/section-exports/mobile.png',fullPage:true});checks.push('Mobile menu fits');
 }catch(e){errors.push(e.message);}
 fs.writeFileSync('tmp/section-exports/browser.json',JSON.stringify({checks,errors},null,2));console.log(JSON.stringify({checks,errors},null,2));await browser.close();if(errors.length)process.exitCode=1;
})().catch(e=>{console.error(e.message);process.exitCode=1;});
