import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();

// DESKTOP: FAQ must stay a 4-col grid (4 cards, no triple)
let ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});
let p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
const d=await p.evaluate(()=>{
  const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));
  const sec=h.closest('section');
  const cards=sec.querySelectorAll('[data-carousel-item]');
  const ul=sec.querySelector('ul');
  return {count:cards.length, display:getComputedStyle(ul).display};
});
console.log('DESKTOP_FAQ cards='+d.count+' ul.display='+d.display+' (esperado 4 / grid)');
// screenshot desktop faq
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-10;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);
await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/faq-desktop.jpg',type:'jpeg',quality:82});
await ctx.close();

// MOBILE: FAQ must be a loop carousel (12 cards = 3x4) and never stick at edges
ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
const m=await p.evaluate(async()=>{
  const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));
  const sec=h.closest('section');
  const el=sec.querySelector('div[class*="overflow-x-auto"]');
  el.setAttribute('data-faq','1');
  const cards=sec.querySelectorAll('[data-carousel-item]').length;
  const oneSet=el.querySelector('ul').scrollWidth/3;
  const pos=[];
  for(let k=0;k<8;k++){ el.scrollLeft+=200; el.dispatchEvent(new Event('scroll')); await new Promise(r=>setTimeout(r,30)); pos.push(Math.round(el.scrollLeft)); }
  return {cards, oneSet:Math.round(oneSet), pos, within: pos.every(x=>x>=-2 && x<=oneSet*2+2)};
});
console.log('MOBILE_FAQ cards='+m.cards+' (esperado 12) oneSet='+m.oneSet);
console.log('   positions='+JSON.stringify(m.pos)+' stays-in-band='+m.within);
const my=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-10;});
await p.evaluate((yy)=>window.scrollTo(0,yy),my);
await p.waitForTimeout(400);
await p.screenshot({path:'_reference/screenshots/faq-mobile.jpg',type:'jpeg',quality:82});
await b.close();console.log('FAQ_PROBE_DONE');
