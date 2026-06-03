import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1600);
const r=await p.evaluate(()=>{
  const track=document.querySelector('[data-carousel-item]').closest('div[class*="overflow-x-auto"]');
  const tr=track.getBoundingClientRect();
  // pick the card whose center is nearest the viewport center AND is on screen
  const slides=[...track.querySelectorAll('[data-carousel-item]')];
  const vc=tr.left+tr.width/2; let best=null,bd=1e9;
  slides.forEach(s=>{const card=s.firstElementChild||s;const cr=card.getBoundingClientRect();if(cr.right<tr.left||cr.left>tr.right)return;const cc=cr.left+cr.width/2;const d=Math.abs(cc-vc);if(d<bd){bd=d;best=cr;}});
  if(!best) return {none:true};
  return {leftGap:Math.round(best.left-tr.left),rightGap:Math.round(tr.right-best.right),cardW:Math.round(best.width)};
});
console.log('HERO MOBILE (card visível)', JSON.stringify(r), '(leftGap≈rightGap = centralizado)');
await b.close();
