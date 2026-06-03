import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(900);
const res = await p.evaluate(()=>{
  // identify each carousel track by a marker inside it
  const find = (re)=>{
    const h=[...document.querySelectorAll('h2,h3,p')].find(e=>re.test(e.textContent||''));
    return null;
  };
  const tracks=[...document.querySelectorAll('div[class*="overflow-x-auto"]')];
  return tracks.map(el=>{
    const cs=getComputedStyle(el);
    // what's the nearest heading to name it
    let name='?';
    const sec=el.closest('section')||el.closest('div');
    const h=sec?sec.querySelector('h2'):null;
    if(h) name=h.textContent.trim().slice(0,28);
    else if(el.querySelector('[data-carousel-item] i.fa-heart, [data-carousel-item] i')) name='Destaques (hero)';
    return {name, touchAction: cs.touchAction, overflowX: cs.overflowX, overflowY: cs.overflowY};
  });
});
res.forEach(r=>console.log(JSON.stringify(r)));
await b.close();console.log('DONE');
