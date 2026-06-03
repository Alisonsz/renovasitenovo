import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();const p=await (await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}})).newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(900);
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Conheça nossos preços/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-20;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/pricing-desk-snap.jpg',type:'jpeg',quality:82});
// mobile pricing too
const ctx2=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});const p2=await ctx2.newPage();
await p2.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p2.waitForTimeout(900);
const y2=await p2.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Conheça nossos preços/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-8;});
await p2.evaluate((yy)=>window.scrollTo(0,yy),y2);await p2.waitForTimeout(500);
await p2.screenshot({path:'_reference/screenshots/pricing-mob-snap.jpg',type:'jpeg',quality:82});
await b.close();console.log('DONE');
