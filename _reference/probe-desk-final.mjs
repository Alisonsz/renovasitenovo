import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(900);
const r=await p.evaluate(()=>[...document.querySelectorAll('div[class*="overflow-x-auto"]')].map(el=>{
  const cs=getComputedStyle(el);const sec=el.closest('section');const h=sec?sec.querySelector('h2'):null;
  const cards=[...el.querySelectorAll('[data-carousel-item]')].length;
  return {name:h?h.textContent.trim().slice(0,20):'Destaques',snap:cs.scrollSnapType,overflowX:cs.overflowX,cards};
}));
r.forEach(x=>console.log('DESKTOP',JSON.stringify(x)));
await b.close();
