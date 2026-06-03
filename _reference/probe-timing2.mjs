import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
const setup=await p.evaluate(()=>{
  const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
  const el=li.closest('div[class*="overflow-x-auto"]'); el.setAttribute('data-t','1');
  const list=[...el.querySelectorAll('[data-carousel-item]')];
  const pitch=list[1].offsetLeft-list[0].offsetLeft; const idx=4;
  // stop HALF a card off-center (max crookedness)
  const crooked=Math.round(list[idx].offsetLeft - el.clientWidth/2 + list[idx].offsetWidth/2 + pitch*0.5);
  el.scrollLeft=crooked; el.dispatchEvent(new Event('scroll'));
  return {pitch:Math.round(pitch)};
});
const off=()=>p.evaluate(()=>{const el=document.querySelector('[data-t="1"]');const list=[...el.querySelectorAll('[data-carousel-item]')];const vc=el.scrollLeft+el.clientWidth/2;let bd=1e9;list.forEach(it=>{const c=it.offsetLeft+it.offsetWidth/2;bd=Math.min(bd,Math.abs(c-vc));});return Math.round(bd);});
console.log('offCenter ao parar (meio card):', await off(), 'px  (pitch='+setup.pitch+')');
await p.waitForTimeout(6450); // just after resume(6000)+center(~380), before first autoplay step(+4200)
console.log('offCenter após centralizar (pré auto-scroll):', await off(), 'px  (deve ser ~0)');
await b.close();
