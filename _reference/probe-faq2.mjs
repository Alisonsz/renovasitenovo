import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
// MOBILE
let ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
let p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1400);
const m=await p.evaluate(()=>{
  const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));
  const el=h.closest('section').querySelector('div[class*="overflow-x-auto"]');
  const slides=[...el.querySelectorAll('[data-carousel-item]')];
  const slideW=Math.round(slides[1].offsetLeft-slides[0].offsetLeft);
  const vc=el.scrollLeft+el.clientWidth/2; let bd=1e9;
  slides.forEach(s=>{const c=s.offsetLeft+s.offsetWidth/2;bd=Math.min(bd,Math.abs(c-vc));});
  return {clientW:el.clientWidth, slidePitch:slideW, nearestOffCenter:Math.round(bd)};
});
console.log('MOBILE FAQ', JSON.stringify(m), '(slidePitch≈clientW => 1 por tela; offCenter≈0)');
await ctx.close();
// DESKTOP
ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});
p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(900);
const d=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));const el=h.closest('section').querySelector('div[class*="overflow-x-auto"]');const ul=el.querySelector('ul');return {display:getComputedStyle(ul).display,cards:el.querySelectorAll('[data-carousel-item]').length};});
console.log('DESKTOP FAQ', JSON.stringify(d), '(grid / 4)');
await b.close();
