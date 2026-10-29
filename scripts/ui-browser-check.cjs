/* Render synthetic test-response HTML; no live accounts or business writes. */
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
(async () => {
 const root = process.cwd(), previews = path.join(root,'tmp/ui-previews');
 const browser = await chromium.launch({headless:true, ...(process.env.UI_BROWSER_PATH ? {executablePath:process.env.UI_BROWSER_PATH} : {})});
 const page = await browser.newPage();
 if(process.env.UI_THEME) await page.addInitScript(theme=>localStorage.setItem('foundation-theme',theme),process.env.UI_THEME);
 const errors = [], results=[];
 page.on('pageerror',error=>errors.push(page.url()+': '+error.message));
 await page.route('http://localhost/**',async route=>{
  const url=new URL(route.request().url());
  const asset=path.resolve(root,'public',decodeURIComponent(url.pathname).replace(/^\//,''));
  if (!asset.startsWith(path.resolve(root,'public')+path.sep)) return route.abort();
  if (fs.existsSync(asset) && fs.statSync(asset).isFile()) return route.fulfill({path:asset});
  const name=url.pathname.slice(1);
  const preview=path.join(previews,name);
  if (/^[\w.-]+\.html$/.test(name) && fs.existsSync(preview)) return route.fulfill({path:preview,contentType:'text/html'});
  return route.fulfill({status:404,body:'Preview route unavailable'});
 });
 for (const file of fs.readdirSync(previews).filter(f=>f.endsWith('.html') && (!process.env.UI_PAGES || process.env.UI_PAGES.split(',').includes(f)))) {
  for(const width of [1440,1280,1024,768,390]) {
   await page.setViewportSize({width,height:1000});
   await page.goto('http://localhost/'+file,{waitUntil:'networkidle'});
   const check=await page.evaluate(()=>({
    overflow:document.documentElement.scrollWidth>innerWidth+2,
    width:document.documentElement.scrollWidth,
    overflowElements:[...document.querySelectorAll('main *')].filter(e=>e.getBoundingClientRect().right>innerWidth+2 && !e.closest('.table-wrap,.qtable,.audit-table,.table-responsive')).slice(0,5).map(e=>e.tagName+'.'+e.className),
    heading:document.querySelector('h1')?.textContent.trim(),
    tiny:[...document.querySelectorAll('main p,main label,main td,main button')].filter(e=>e.getClientRects().length && parseFloat(getComputedStyle(e).fontSize)<11).length,
    unlabeled:[...document.querySelectorAll('input:not([type=hidden]),select,textarea')].filter(e=>e.getClientRects().length && !e.labels?.length && !e.getAttribute('aria-label') && !e.getAttribute('aria-labelledby')).length,
    tinyElements:[...document.querySelectorAll('main p,main label,main td,main button')].filter(e=>e.getClientRects().length && parseFloat(getComputedStyle(e).fontSize)<11).map(e=>e.outerHTML.slice(0,200)),
    missingLabels:[...document.querySelectorAll('input:not([type=hidden]),select,textarea')].filter(e=>e.getClientRects().length && !e.labels?.length && !e.getAttribute('aria-label') && !e.getAttribute('aria-labelledby')).map(e=>e.outerHTML.slice(0,180)),
    whiteSurfaces:document.documentElement.dataset.theme==='dark'?[...document.querySelectorAll('main *')].filter(e=>{if(!e.getClientRects().length)return false;const c=getComputedStyle(e).backgroundColor.match(/^rgb\((\d+), (\d+), (\d+)\)$/);return c&&c.slice(1).every(v=>+v>225);}).map(e=>e.tagName+'.'+e.className):[]
   }));
   results.push({file,viewport:width,...check});
   if (['dashboard.html','members.create.html','members.index.html','committees.index.html','users.index.html','operations.tasks.index.html','login.html','users.roles.html'].includes(file) && [1440,390].includes(width)) await page.screenshot({path:path.join(root,'tmp',`ui-${file}-${width}-${process.env.UI_THEME||'light'}.png`),fullPage:true});
  }
 }
 await page.setViewportSize({width:390,height:844});
 await page.goto('http://localhost/dashboard.html',{waitUntil:'networkidle'});
 await page.getByRole('button',{name:'Toggle navigation'}).click();
 const drawerOpen=await page.locator('#sidebar').evaluate(e=>e.classList.contains('open')&&!e.inert);
 await page.keyboard.press('Escape');
 const drawerClosed=await page.locator('#sidebar').evaluate(e=>!e.classList.contains('open')&&e.inert);
 const themeToggle=page.getByRole('button',{name:'Switch to dark theme'});
 let themeChecks=null;
 if(!process.env.UI_THEME && await themeToggle.count()) {
  await themeToggle.click();
  await page.reload({waitUntil:'networkidle'});
  const persisted=await page.locator('html').getAttribute('data-theme')==='dark';
  const background=await page.locator('body').evaluate(e=>getComputedStyle(e).backgroundColor);
  await page.screenshot({path:path.join(root,'tmp/ui-dashboard-dark-390.png'),fullPage:true});
  await page.setViewportSize({width:1440,height:1000});
  await page.screenshot({path:path.join(root,'tmp/ui-dashboard-dark-1440.png'),fullPage:true});
  await page.locator('.account-dropdown summary').click();
  const dropdown=await page.locator('.account-dropdown').getAttribute('open')!==null;
  await page.keyboard.press('Escape');
  await page.getByRole('button',{name:'Switch to light theme'}).click();
  themeChecks={persisted,background,dropdown,returnedToLight:await page.locator('html').getAttribute('data-theme')==='light',fundGraph:await page.locator('.fund-graph svg').count()===1};
  if(!Object.values(themeChecks).every(Boolean)) throw new Error('Theme interaction check failed');
 }
 const report={results,errors:[...new Set(errors)],drawerOpen,drawerClosed,themeChecks};
 fs.writeFileSync(path.join(root,'tmp/ui-browser-results.json'),JSON.stringify(report,null,2));
 console.log(JSON.stringify({pages:results.length/5,checks:results.length,overflows:results.filter(r=>r.overflow),whiteSurfaces:results.filter(r=>r.whiteSurfaces.length),tinyText:results.filter(r=>r.tiny),unlabeled:results.filter(r=>r.unlabeled),errors:report.errors,drawerOpen,drawerClosed,themeChecks},null,2));
 await browser.close();
})().catch(e=>{console.error(e);process.exitCode=1});
