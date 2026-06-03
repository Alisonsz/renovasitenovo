import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1200);
const fy=await p.evaluate(()=>{const li=document.querySelector('[data-carousel-item]');const el=li.closest('div[class*="overflow-x-auto"]');const r=el.getBoundingClientRect();el.setAttribute('data-pf','1');return Math.round(r.top+window.scrollY)-40;});
await p.evaluate((y)=>window.scrollTo(0,y),fy);
await p.waitForTimeout(400);
// land just past the wrap boundary and capture — must show cards, never a blank gap
await p.evaluate(()=>{const el=document.querySelector('[data-pf="1"]');const oneSet=el.querySelector('ul').scrollWidth/3;el.scrollLeft=oneSet*1.48;el.dispatchEvent(new Event('scroll'));});
await p.waitForTimeout(150);
await p.screenshot({path:'_reference/screenshots/mobile-feat-wrap.jpg',type:'jpeg',quality:82});
const sl=await p.evaluate(()=>Math.round(document.querySelector('[data-pf="1"]').scrollLeft));
console.log('after_wrap_scrollLeft='+sl);
await b.close();console.log('FRAMES_DONE');
