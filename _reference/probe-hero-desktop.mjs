import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1280,height:820}});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1500);
const r=await p.evaluate(()=>{
  const li=document.querySelector('[data-carousel-item]');
  const track=li.closest('div[class*="overflow-x-auto"]');
  const tr=track.getBoundingClientRect();
  // count how many cards are FULLY inside the track viewport
  const slides=[...track.querySelectorAll('[data-carousel-item]')];
  let fully=0, partial=0;
  slides.forEach(s=>{
    const r=s.getBoundingClientRect();
    if(r.right<=tr.left || r.left>=tr.right) return; // off-screen
    const visibleLeft=Math.max(r.left,tr.left), visibleRight=Math.min(r.right,tr.right);
    const vis=(visibleRight-visibleLeft)/r.width;
    if(vis>0.98) fully++; else if(vis>0.02) partial++;
  });
  return {trackLeft:Math.round(tr.left),trackW:Math.round(tr.width),scrollLeft:Math.round(track.scrollLeft),fullyVisible:fully,partial};
});
console.log('HERO DESKTOP', JSON.stringify(r), '(quero fullyVisible=4, partial=0)');
await b.close();
