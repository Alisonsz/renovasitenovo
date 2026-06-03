import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1200);
// ONLY the back-wrap, on a fresh state
const r = await p.evaluate(async()=>{
  const li=document.querySelector('[data-carousel-item]');
  const el=li.closest('div[class*="overflow-x-auto"]');
  const oneSet=el.querySelector('ul').scrollWidth/3;
  el.style.scrollBehavior='auto';
  el.scrollLeft = Math.round(oneSet*0.4);      // before the middle band
  const set = el.scrollLeft;
  el.dispatchEvent(new Event('scroll'));
  await new Promise(r=>setTimeout(r,60));
  const after = el.scrollLeft;
  return {oneSet:Math.round(oneSet), set:Math.round(set), after:Math.round(after), expected:Math.round(oneSet*1.4)};
});
console.log('BACK_ONLY', JSON.stringify(r), 'ok=', Math.abs(r.after-r.expected)<8);
await b.close();
