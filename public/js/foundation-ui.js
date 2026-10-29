/* Progressive enhancement only. The server owns permissions, validation and posting. */
(() => {
 'use strict';
 const sidebar = document.getElementById('sidebar');
 const toggle = document.getElementById('mobileMenuBtn');
 const backdrop = document.querySelector('.sidebar-backdrop');
 const mobile = () => matchMedia('(max-width:1023px)').matches;
 const closeDrawer = () => {
  sidebar?.classList.remove('open'); backdrop?.classList.remove('open');
  document.body.style.overflow = ''; toggle?.setAttribute('aria-expanded', 'false');
  if (mobile() && sidebar) sidebar.inert = true;
 };
 const sync = () => { closeDrawer(); if (sidebar) { sidebar.inert = mobile() || document.body.classList.contains('nav-collapsed'); toggle?.setAttribute('aria-expanded',String(!sidebar.inert)); } };
 if (sidebar && toggle) {
  toggle.addEventListener('click', () => {
   if (mobile()) {
    const opening = !sidebar.classList.contains('open');
    if (!opening) { closeDrawer(); return; }
    sidebar.inert = false; sidebar.classList.add('open'); backdrop?.classList.add('open');
    document.body.style.overflow = 'hidden'; toggle.setAttribute('aria-expanded','true');
    sidebar.querySelector('a,button,summary')?.focus();
   } else {
    document.body.classList.toggle('nav-collapsed'); sidebar.inert = document.body.classList.contains('nav-collapsed');
    toggle.setAttribute('aria-expanded',String(!sidebar.inert));
   }
  });
  backdrop?.addEventListener('click', () => { closeDrawer(); toggle.focus(); });
  document.addEventListener('keydown', event => {
   if (!mobile() || !sidebar.classList.contains('open')) return;
   if (event.key === 'Escape') { closeDrawer(); toggle.focus(); }
   if (event.key === 'Tab') {
    const targets = [...sidebar.querySelectorAll('a,button,summary,input')].filter(el => el.getClientRects().length && !el.disabled);
    const first = targets[0], last = targets.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
   }
  });
  matchMedia('(max-width:1023px)').addEventListener('change',sync); sync();
 }
 // Keep all existing links and server permissions; make navigation groups collapsible.
 document.querySelectorAll('.sidebar-nav .nav-section').forEach(heading => {
  const group=document.createElement('details'); group.className='nav-group'; group.open=true;
  const summary=document.createElement('summary'); summary.textContent=heading.textContent.trim();
  heading.before(group); group.append(summary);
  let next=heading.nextElementSibling;
  while(next && !next.classList.contains('nav-section')) { const following=next.nextElementSibling; group.append(next); next=following; }
  heading.remove(); if(!group.querySelector('a')) group.remove();
 });
 document.querySelectorAll('.nav-item.active').forEach(el => el.setAttribute('aria-current','page'));
 document.querySelectorAll('.page-content table').forEach(table => {
  if (!table.parentElement.matches('.table-wrap,.qtable,.audit-table,.items-table-wrap,.table-responsive')) {
   const region=document.createElement('div'); region.className='table-wrap'; table.before(region); region.append(table);
  }
  const region=table.parentElement; region.tabIndex=0; region.setAttribute('role','region'); region.setAttribute('aria-label','Scrollable data table');
  table.querySelectorAll('thead th').forEach((th,index)=>{
   th.setAttribute('scope','col');
   if (/amount|balance|principal|paid|budget|received|recovered|outstanding|allocated|spent|refund|total|quantity|price/i.test(th.textContent)) {
    th.classList.add('ui-number'); table.querySelectorAll('tbody tr').forEach(row=>{const cell=row.children[index]; if(cell && !cell.hasAttribute('colspan')) cell.classList.add('ui-number');});
   }
  });
 });
 document.querySelectorAll('.badge,.status-badge,.qbadge,.audit-tag').forEach(badge=>{
  const status=badge.textContent.trim().toLowerCase();
  const tone=/rejected|overdue|suspended|blocked|void/.test(status)?'danger':/pending|review|awaiting|due/.test(status)?'warning':/^(active|approved|completed|posted|received|repaid|signed)$/.test(status)?'success':/progress|submitted/.test(status)?'info':'neutral';
  badge.dataset.tone=tone;
 });
 document.querySelectorAll('a,button').forEach(el=>{
  if(!el.textContent.trim() && !el.getAttribute('aria-label')) {
   const icon=el.querySelector('i'); const label=el.title || (icon?.className.includes('eye')?'View details':icon?.className.includes('pencil')?'Edit record':null);
   if(label) el.setAttribute('aria-label',label);
  }
 });
 document.querySelectorAll('input,select,textarea').forEach((field,index)=>{
  if(field.type==='hidden') return;
  if(!field.id) field.id=`ui-field-${index}`;
  if(!field.labels?.length) {
   const label=field.closest('.form-group,.field,.mb-3,.group')?.querySelector('label') || field.parentElement.querySelector('label');
   if(label && !label.htmlFor) label.htmlFor=field.id;
  }
  if(!field.labels?.length && !field.hasAttribute('aria-label')) {
   const visible=field.parentElement.querySelector('.label,.field-label');
   if(visible) { if(!visible.id) visible.id=`ui-label-${index}`;field.setAttribute('aria-labelledby',visible.id); }
   else {
    const text=field.getAttribute('title') || field.getAttribute('placeholder') || (field.tagName==='SELECT' ? field.options[0]?.textContent : null) || field.name?.replace(/[_\[\]]+/g,' ').trim();
    if(text) field.setAttribute('aria-label',text);
   }
  }
  if(field.classList.contains('is-invalid')) field.setAttribute('aria-invalid','true');
 });
 document.querySelectorAll('[role=alert],.alert-danger,.qerror,.error').forEach(el=>{ el.setAttribute('role','alert');el.tabIndex=-1; });
 const invalid=document.querySelector('[aria-invalid=true]'); if(invalid) invalid.focus();
 // A second click is ignored while a normal POST navigates. Submitter values remain in the request.
 document.addEventListener('submit',event=>{
  const form=event.target;
  if(form.method.toLowerCase()==='get' || event.defaultPrevented || form.target==='_blank') return;
  if(form.dataset.uiSubmitting==='true'){event.preventDefault();return;}
  queueMicrotask(()=>{
   if(event.defaultPrevented) return;
   form.dataset.uiSubmitting='true';form.setAttribute('aria-busy','true');
   if(event.submitter){event.submitter.setAttribute('aria-disabled','true');event.submitter.dataset.uiBusy='true';}
  });
 });
 addEventListener('pageshow',()=>document.querySelectorAll('[data-ui-submitting]').forEach(form=>{
  delete form.dataset.uiSubmitting;form.removeAttribute('aria-busy');form.querySelectorAll('[data-ui-busy]').forEach(button=>{button.removeAttribute('aria-disabled');delete button.dataset.uiBusy;});
 }));
})();
