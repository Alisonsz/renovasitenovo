import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();

async function check(label, viewport, isMobile){
  const ctx=await b.newContext({locale:'pt-BR',viewport,isMobile,hasTouch:isMobile});
  const p=await ctx.newPage();
  await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
  await p.waitForTimeout(900);
  const r=await p.evaluate(()=>{
    const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
    const el=li.closest('div[class*="overflow-x-auto"]') || li.closest('div');
    const cs=getComputedStyle(el);
    return {
      overflowX: cs.overflowX, overflowY: cs.overflowY,
      // vertical scroll capture happens when scrollHeight > clientHeight on a y-scrollable box
      scrollH: el.scrollHeight, clientH: el.clientHeight,
      canTrapVertical: (cs.overflowY!=='visible' && cs.overflowY!=='hidden') && el.scrollHeight>el.clientHeight+1,
    };
  });
  console.log(label, JSON.stringify(r));
  await ctx.close();
}
await check('MOBILE ', {width:390,height:844}, true);
await check('DESKTOP', {width:1366,height:900}, false);
await b.close();
