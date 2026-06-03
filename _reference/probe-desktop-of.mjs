import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(900);
const res = await p.evaluate(()=>[...document.querySelectorAll('div[class*="overflow-x-auto"]')].map(el=>{
  const cs=getComputedStyle(el);const sec=el.closest('section');const h=sec?sec.querySelector('h2'):null;
  return {name:h?h.textContent.trim().slice(0,24):'Destaques',overflowX:cs.overflowX,overflowY:cs.overflowY};
}));
res.forEach(r=>console.log('DESKTOP',JSON.stringify(r)));
await b.close();
