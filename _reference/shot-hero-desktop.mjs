import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1280,height:820}});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1500);
// frame the hero bottom where the cards sit
const y=await p.evaluate(()=>{const li=document.querySelector('[data-carousel-item]');const t=li.closest('div[class*="overflow-x-auto"]');return Math.round(t.getBoundingClientRect().top+window.scrollY)-120;});
await p.evaluate((yy)=>window.scrollTo(0,Math.max(0,yy)),y);await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/hero-cards-desktop.jpg',type:'jpeg',quality:84});
await b.close();console.log('DONE');
