import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
const errs=[]; p.on('console',m=>{if(m.type()==='error')errs.push(m.text());});
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1400);
const setup=await p.evaluate(()=>{
  const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));
  const el=h.closest('section').querySelector('div[class*="overflow-x-auto"]');
  el.setAttribute('data-s','1');
  const slides=[...el.querySelectorAll('[data-carousel-item]')];
  const pitch=slides[1].offsetLeft-slides[0].offsetLeft;
  el.scrollLeft += Math.round(pitch*0.45);
  el.dispatchEvent(new Event('scroll')); // triggers onScroll settle (coarse => center)
  const vc=el.scrollLeft+el.clientWidth/2;let bd=1e9;slides.forEach(s=>{const c=s.offsetLeft+s.offsetWidth/2;bd=Math.min(bd,Math.abs(c-vc));});
  return {pitch:Math.round(pitch), off:Math.round(bd)};
});
console.log('coarsePointer=', await p.evaluate(()=>matchMedia('(pointer: coarse)').matches));
console.log('FAQ off ao parar:', setup.off, 'px (pitch='+setup.pitch+')');
await p.waitForTimeout(500);
const t500=await p.evaluate(()=>{const el=document.querySelector('[data-s="1"]');const s=[...el.querySelectorAll('[data-carousel-item]')];const vc=el.scrollLeft+el.clientWidth/2;let bd=1e9;s.forEach(x=>{const c=x.offsetLeft+x.offsetWidth/2;bd=Math.min(bd,Math.abs(c-vc));});return Math.round(bd);});
console.log('FAQ off @0.5s (antes de 0.7s, deve continuar torto):', t500);
await p.waitForTimeout(500);
const t1000=await p.evaluate(()=>{const el=document.querySelector('[data-s="1"]');const s=[...el.querySelectorAll('[data-carousel-item]')];const vc=el.scrollLeft+el.clientWidth/2;let bd=1e9;s.forEach(x=>{const c=x.offsetLeft+x.offsetWidth/2;bd=Math.min(bd,Math.abs(c-vc));});return Math.round(bd);});
console.log('FAQ off @1.0s (após 0.7s, deve ~0):', t1000);
console.log('ERRORS', errs.length);
await b.close();
