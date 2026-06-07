import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/depilacao-feminina',{waitUntil:'networkidle'});await p.waitForTimeout(1200);
// scroll to first section's pagination and click page "2"
const before=await p.evaluate(()=>{
  const nav=document.querySelector('nav[aria-label^="Paginação"]');
  if(!nav) return {noPag:true};
  const sec=nav.closest('section');
  nav.setAttribute('data-pag','1'); sec.setAttribute('data-sec','1');
  // click page 2 button (number)
  const btns=[...nav.querySelectorAll('button')];
  const two=btns.find(b=>b.textContent.trim()==='2');
  const secTopBefore=Math.round(sec.getBoundingClientRect().top);
  if(two) two.click();
  return {secTopBefore, clicked: !!two};
});
await p.waitForTimeout(700); // let smooth scrollIntoView finish
const after=await p.evaluate(()=>{
  const sec=document.querySelector('[data-sec="1"]');
  const r=sec.getBoundingClientRect();
  // which page is active now?
  const nav=document.querySelector('[data-pag="1"]');
  const active=[...nav.querySelectorAll('button')].find(b=>b.className.includes('bg-brand')&&/^\d+$/.test(b.textContent.trim()));
  return {secTopAfter:Math.round(r.top), activePage: active?active.textContent.trim():'?', firstProduct: document.querySelector('[data-sec="1"] article h2')?.innerText};
});
console.log('clicou pág 2:', before.clicked, '| topo seção antes:', before.secTopBefore, '| depois:', after.secTopAfter, '| página ativa:', after.activePage);
console.log('primeiro produto na pág 2:', after.firstProduct);
console.log('=> seção ficou visível no topo (top entre -10 e 120):', after.secTopAfter>=-10 && after.secTopAfter<=120);
await b.close();
