import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1200);
// locate the features track box
const box = await p.evaluate(()=>{
  const li=document.querySelector('[data-carousel-item]');
  const el=li.closest('div[class*="overflow-x-auto"]');
  el.scrollLeft = el.querySelector('ul').scrollWidth/3; // middle
  const r=el.getBoundingClientRect();
  return {x:Math.round(r.left+r.width/2), y:Math.round(r.top+r.height/2), before: Math.round(el.scrollLeft)};
});
// simulate a mouse drag leftwards (content moves => scrollLeft increases)
await p.mouse.move(box.x, box.y);
await p.mouse.down();
await p.mouse.move(box.x-200, box.y, {steps:10});
await p.mouse.move(box.x-350, box.y, {steps:10});
await p.mouse.up();
await p.waitForTimeout(150);
const after = await p.evaluate(()=>{
  const li=document.querySelector('[data-carousel-item]');
  const el=li.closest('div[class*="overflow-x-auto"]');
  return Math.round(el.scrollLeft);
});
console.log('DRAG before='+box.before+' after='+after+' moved='+(Math.abs(after-box.before)>50));
await b.close();
