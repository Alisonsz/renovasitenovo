import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}}); // desktop: fine pointer
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(900);
const coarse=await p.evaluate(()=>matchMedia('(pointer: coarse)').matches);
// Features carousel scrolls on desktop too; nudge it and ensure no surprise jump from settle-center
const r=await p.evaluate(async()=>{
  const li=document.querySelector('[data-carousel-item]');
  const el=li.closest('div[class*="overflow-x-auto"]');
  const before=Math.round(el.scrollLeft);
  el.scrollLeft += 137; // arbitrary nudge
  el.dispatchEvent(new Event('scroll'));
  await new Promise(r=>setTimeout(r,900));
  return {before, after:Math.round(el.scrollLeft)};
});
console.log('DESKTOP coarsePointer=', coarse);
console.log('DESKTOP nudge: before='+r.before+' after(0.9s)='+r.after+'  (não deve auto-centralizar via settle)');
await b.close();
