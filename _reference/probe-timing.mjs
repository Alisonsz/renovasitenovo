import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
const setup=await p.evaluate(()=>{
  const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));
  const el=li.closest('div[class*="overflow-x-auto"]');
  el.setAttribute('data-t','1');
  const list=[...el.querySelectorAll('[data-carousel-item]')];
  const pitch=list[1].offsetLeft-list[0].offsetLeft;
  const idx=4;
  const crooked=Math.round(list[idx].offsetLeft - el.clientWidth/2 + list[idx].offsetWidth/2 + pitch*0.42);
  el.scrollLeft=crooked; el.dispatchEvent(new Event('scroll'));
  // expected centered target for the nearest card right now
  const vc=el.scrollLeft+el.clientWidth/2; let best=null,bd=1e9;
  list.forEach(it=>{const c=it.offsetLeft+it.offsetWidth/2;const d=Math.abs(c-vc);if(d<bd){bd=d;best=it;}});
  return {crooked, centerTarget:Math.round(best.offsetLeft+best.offsetWidth/2-el.clientWidth/2)};
});
const off = ()=>p.evaluate(()=>{const el=document.querySelector('[data-t="1"]');const list=[...el.querySelectorAll('[data-carousel-item]')];const vc=el.scrollLeft+el.clientWidth/2;let bd=1e9;list.forEach(it=>{const c=it.offsetLeft+it.offsetWidth/2;bd=Math.min(bd,Math.abs(c-vc));});return {scrollLeft:Math.round(el.scrollLeft),offCenter:Math.round(bd)};});
const t0=await off();
console.log('t=0.2s (parou torto):', JSON.stringify(t0));
await p.waitForTimeout(6800); // > autoplayResumeMs(6000) + center anim
const t1=await off();
console.log('t=7s (após retomada+centralização):', JSON.stringify(t1));
console.log('centerTarget esperado ~', setup.centerTarget);
await b.close();
