import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1400);
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-8;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);
await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/faq-fixed.jpg',type:'jpeg',quality:84});
await b.close();console.log('DONE');
