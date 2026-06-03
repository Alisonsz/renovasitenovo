import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);
// scroll the PAGE so the FAQ heading sits near the top, then shoot
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-8;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);
await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/faq-mobile2.jpg',type:'jpeg',quality:82});
// also nudge the FAQ track and reshoot to show a different card (proves it scrolls)
await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));const el=h.closest('section').querySelector('div[class*="overflow-x-auto"]');el.scrollLeft+=170;el.dispatchEvent(new Event('scroll'));});
await p.waitForTimeout(300);
await p.screenshot({path:'_reference/screenshots/faq-mobile3.jpg',type:'jpeg',quality:82});
await b.close();console.log('FAQ_MOB_DONE');
