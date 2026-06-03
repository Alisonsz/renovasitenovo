import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();

async function run(label, viewport, isMobile){
  const ctx=await b.newContext({locale:'pt-BR',viewport,isMobile,hasTouch:isMobile});
  const p=await ctx.newPage();
  await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
  await p.waitForTimeout(900);
  const data=await p.evaluate(()=>{
    return [...document.querySelectorAll('div[class*="overflow-x-auto"]')].map(el=>{
      const cs=getComputedStyle(el);
      const sec=el.closest('section'); const h=sec?sec.querySelector('h2'):null;
      const items=el.querySelectorAll('[data-carousel-item]');
      // exact set width check: distance item[0] -> item[perSet]
      let exact=null, viaScroll=null;
      if(items.length%3===0 && items.length>=3){
        const perSet=items.length/3;
        exact=Math.round(items[perSet].offsetLeft-items[0].offsetLeft);
        viaScroll=Math.round(el.querySelector('ul').scrollWidth/3);
      }
      // card pitch (card width + gap) from first two items
      let pitch=null;
      if(items.length>=2) pitch=Math.round(items[1].offsetLeft-items[0].offsetLeft);
      return {
        name: h?h.textContent.trim().slice(0,22):'Destaques',
        snap: cs.scrollSnapType,
        exactSet: exact, viaScroll,
        pitch,
        setIsMultipleOfPitch: (exact!=null && pitch) ? (exact % pitch === 0) : null,
      };
    });
  });
  data.forEach(d=>console.log(label, JSON.stringify(d)));
  await ctx.close();
}
await run('MOBILE ',{width:390,height:844},true);
await run('DESKTOP',{width:1366,height:900},false);
await b.close();
