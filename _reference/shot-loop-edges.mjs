import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1200);
// Pricing: scroll page to it, then set various scrollLeft values and screenshot — looking for blank gaps
const meta=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Conheça nossos preços/i.test(e.innerText));const y=Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-8;return y;});
await p.evaluate((y)=>window.scrollTo(0,y),meta);
await p.waitForTimeout(300);
// drive the track to just-before and just-after a wrap boundary
const probe = await p.evaluate(async()=>{
  const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
  const el=li.closest('div[class*="overflow-x-auto"]');
  const items=[...el.querySelectorAll('[data-carousel-item]')];
  const oneSet=items[items.length/3].offsetLeft-items[0].offsetLeft;
  // put it near far right of middle band, let settle/ wrap run
  el.scrollLeft = Math.round(oneSet*1.35);
  el.dispatchEvent(new Event('scroll'));
  await new Promise(r=>setTimeout(r,200));
  return {oneSet:Math.round(oneSet), after:Math.round(el.scrollLeft)};
});
await p.waitForTimeout(200);
await p.screenshot({path:'_reference/screenshots/loop-edge-pricing.jpg',type:'jpeg',quality:84});
console.log('PRICING after wrap scrollLeft='+probe.after+' (oneSet='+probe.oneSet+')');
await b.close();console.log('DONE');
