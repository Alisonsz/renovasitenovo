import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
// scroll page to pricing
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Conheça nossos preços/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-8;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y); await p.waitForTimeout(300);
// stop clearly crooked (a card edge at center)
await p.evaluate(()=>{const li=[...document.querySelectorAll('[data-carousel-item]')].find(x=>x.querySelector('button'));const el=li.closest('div[class*="overflow-x-auto"]');el.setAttribute('data-t','1');const list=[...el.querySelectorAll('[data-carousel-item]')];const pitch=list[1].offsetLeft-list[0].offsetLeft;el.scrollLeft=Math.round(list[4].offsetLeft - el.clientWidth/2 + pitch*0.5);el.dispatchEvent(new Event('scroll'));});
await p.waitForTimeout(200);
await p.screenshot({path:'_reference/screenshots/center-before.jpg',type:'jpeg',quality:84});
// wait for resume(6000)+center anim, capture just after centering
await p.waitForTimeout(6450);
await p.screenshot({path:'_reference/screenshots/center-after.jpg',type:'jpeg',quality:84});
await b.close();console.log('DONE');
