import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1366,height:900}});const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(900);
const r=await p.evaluate(()=>{const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));const el=h.closest('section').querySelector('div[class*="overflow-x-auto"]');const ul=el.querySelector('ul');const cards=[...el.querySelectorAll('[data-carousel-item]')];return {display:getComputedStyle(ul).display,count:cards.length,firstW:Math.round(cards[0].getBoundingClientRect().width)};});
console.log('DESKTOP FAQ', JSON.stringify(r));
await b.close();
