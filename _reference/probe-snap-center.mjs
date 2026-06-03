import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(900);
const r=await p.evaluate(async()=>{
  // pricing track
  const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
  const el=li.closest('div[class*="overflow-x-auto"]');
  const items=[...el.querySelectorAll('[data-carousel-item]')];
  const pitch=items[1].offsetLeft-items[0].offsetLeft;
  // land on a "crooked" position: half a card past a snap point
  const target=items[3].offsetLeft - el.clientWidth/2 + items[3].offsetWidth/2 + pitch*0.45;
  el.scrollTo({left:target, behavior:'auto'});
  await new Promise(r=>setTimeout(r,80));
  const before=el.scrollLeft;
  // a tiny user-ish scroll nudge triggers snap settle in real browsers; here we
  // emulate "rest" by reading the nearest snap point math instead.
  // distance from the nearest centered card:
  const center = before + el.clientWidth/2;
  // find nearest item center
  let best=1e9, bestIdx=-1;
  items.forEach((it,idx)=>{const c=it.offsetLeft+it.offsetWidth/2; const d=Math.abs(c-center); if(d<best){best=d;bestIdx=idx;}});
  return {pitch:Math.round(pitch), restedAt:Math.round(before), nearestCardCenterOffsetPx:Math.round(best)};
});
console.log('PRICING_SNAP', JSON.stringify(r));
console.log('  (com snap-mandatory o navegador encaixa esse ~'+r.nearestCardCenterOffsetPx+'px restante ao soltar o dedo)');
await b.close();
