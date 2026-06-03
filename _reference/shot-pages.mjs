import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
// QuemSomos: scroll to the text paragraph
await p.goto('http://127.0.0.1:8000/quem-somos',{waitUntil:'networkidle'});await p.waitForTimeout(1000);
let y=await p.evaluate(()=>{const el=[...document.querySelectorAll('p')].find(e=>/acreditamos que cuidar/i.test(e.innerText));return el?Math.round(el.getBoundingClientRect().top+window.scrollY)-30:0;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);await p.waitForTimeout(400);
await p.screenshot({path:'_reference/screenshots/quem-somos-mob.jpg',type:'jpeg',quality:84});
// NossaTecnologia
await p.goto('http://127.0.0.1:8000/nossa-tecnologia',{waitUntil:'networkidle'});await p.waitForTimeout(1000);
y=await p.evaluate(()=>{const el=[...document.querySelectorAll('p')].find(e=>/levamos a sério/i.test(e.innerText));return el?Math.round(el.getBoundingClientRect().top+window.scrollY)-60:0;});
await p.evaluate((yy)=>window.scrollTo(0,yy),y);await p.waitForTimeout(400);
await p.screenshot({path:'_reference/screenshots/nossa-tec-mob.jpg',type:'jpeg',quality:84});
await b.close();console.log('DONE');
