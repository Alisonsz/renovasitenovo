import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1500);
const r=await p.evaluate(()=>{
  const li=document.querySelector('[data-carousel-item]');
  const track=li.closest('div[class*="overflow-x-auto"]');
  const card=li.firstElementChild||li;
  const cr=card.getBoundingClientRect();
  const leftGap=Math.round(cr.left), rightGap=Math.round(window.innerWidth-cr.right);
  return {leftGap,rightGap,cardW:Math.round(cr.width)};
});
console.log('HERO MOBILE', JSON.stringify(r), '(quero leftGap≈rightGap = centralizado)');
await b.close();
