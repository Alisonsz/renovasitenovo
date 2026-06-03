import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1200);
const y=await p.evaluate(()=>{const el=[...document.querySelectorAll('p')].find(e=>/Você livre dos pelos/i.test(e.innerText));const sec=el.closest('section');const r=sec.getBoundingClientRect();return {y:Math.round(r.top+window.scrollY)-10, h:Math.round(r.height)};});
console.log('SECTION_HEIGHT='+y.h);
await p.evaluate((yy)=>window.scrollTo(0,yy),y.y);await p.waitForTimeout(400);
await p.screenshot({path:'_reference/screenshots/freedom-before.jpg',type:'jpeg',quality:84});
await b.close();console.log('DONE');
