import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(900);
const y=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Conheça nossos preços/i.test(e.innerText));return Math.round(h.closest('section').getBoundingClientRect().top+window.scrollY)-20;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/pricing-desktop-check.jpg',type:'jpeg',quality:82});
await b.close();console.log('DONE');
