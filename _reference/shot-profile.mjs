import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const p=await (await b.newContext({locale:'pt-BR'})).newPage();
await p.setViewportSize({width:1366,height:850});
await p.goto('http://127.0.0.1:8000/login',{waitUntil:'networkidle'});
await p.waitForTimeout(500);
await p.evaluate(()=>{const s=(x,v)=>{const e=document.querySelector(x);if(e){e.value=v;e.dispatchEvent(new Event('input',{bubbles:true}));}};s('input[type=email]','admin@renovalaser.local');s('input[type=password]','admin123');});
await p.evaluate(()=>{const b=[...document.querySelectorAll('button')].find(e=>/entrar|acessar|login/i.test(e.innerText));b&&b.click();});
await p.waitForTimeout(2000);
await p.goto('http://127.0.0.1:8000/admin/minha-conta',{waitUntil:'networkidle'});
await p.waitForTimeout(800);
await p.screenshot({path:'_reference/screenshots/admin-profile.jpg',type:'jpeg',quality:82});
await b.close();console.log('PROFILE_SHOT');
