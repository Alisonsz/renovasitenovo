import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});
const p=await ctx.newPage();
const errs=[]; p.on('console',m=>{if(m.type()==='error')errs.push(m.text());});
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
// scroll to pricing and click a card's CTA button
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Conheça nossos preços/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-20;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y); await p.waitForTimeout(400);
const clicked = await p.evaluate(()=>{
  const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
  const btn=li.querySelector('button'); if(btn){btn.click(); return btn.textContent.trim();} return null;
});
console.log('CTA clicado:', clicked);
await p.waitForTimeout(500);
// read modal content + the link target
const modal=await p.evaluate(()=>{
  const dlg=document.querySelector('[role="dialog"]'); if(!dlg) return {open:false};
  const a=dlg.querySelector('a');
  return {open:true, text:dlg.innerText.replace(/\s+/g,' ').trim().slice(0,160), linkText:a?a.textContent.trim():null, href:a?a.getAttribute('href'):null};
});
console.log('MODAL:', JSON.stringify(modal,null,0));
await p.screenshot({path:'_reference/screenshots/modal-store-off.jpg',type:'jpeg',quality:84});
console.log('ERRORS', errs.length);
await b.close();
