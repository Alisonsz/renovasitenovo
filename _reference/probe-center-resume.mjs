import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
const errs=[]; p.on('console',m=>{if(m.type()==='error')errs.push(m.text());});
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
// Lower the resume delay at runtime is not possible; we replicate by reading geometry.
const r=await p.evaluate(async()=>{
  const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
  const el=li.closest('div[class*="overflow-x-auto"]');
  const list=[...el.querySelectorAll('[data-carousel-item]')];
  const pitch=list[1].offsetLeft-list[0].offsetLeft;
  // scroll to a deliberately "crooked" spot: a card + 40% of the next
  const baseIdx=4;
  const crooked=Math.round(list[baseIdx].offsetLeft - el.clientWidth/2 + list[baseIdx].offsetWidth/2 + pitch*0.4);
  el.scrollLeft=crooked;
  el.dispatchEvent(new Event('scroll'));
  await new Promise(r=>setTimeout(r,150));
  const before=Math.round(el.scrollLeft);
  // compute what "centered nearest" would resolve to
  const vc=el.scrollLeft+el.clientWidth/2;
  let best=null,bd=1e9;
  list.forEach(it=>{const c=it.offsetLeft+it.offsetWidth/2;const d=Math.abs(c-vc);if(d<bd){bd=d;best=it;}});
  const centerTarget=Math.round(best.offsetLeft+best.offsetWidth/2-el.clientWidth/2);
  const offCenterPx=Math.round(bd);
  return {pitch:Math.round(pitch),crooked:before,centerTarget,offCenterBeforePx:offCenterPx};
});
console.log('CROOKED_STOP', JSON.stringify(r));
console.log('  -> ao retomar, centerNearest() levaria scrollLeft de '+r.crooked+' para '+r.centerTarget+' (corrige '+r.offCenterBeforePx+'px)');
console.log('CONSOLE_ERRORS', errs.length);
await b.close();
