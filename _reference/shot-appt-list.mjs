import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();const p=await (await b.newContext({locale:'pt-BR'})).newPage();
await p.setViewportSize({width:1366,height:950});
await p.goto('http://127.0.0.1:8000/ovodepapagaio',{waitUntil:'networkidle'});await p.waitForTimeout(500);
await p.evaluate(()=>{const s=(x,v)=>{const e=document.querySelector(x);if(e){e.value=v;e.dispatchEvent(new Event('input',{bubbles:true}));}};s('input[type=email]','admin@renovalaser.local');s('input[type=password]','admin123');});
await p.evaluate(()=>{const b=[...document.querySelectorAll('button')].find(e=>/entrar|acessar/i.test(e.innerText));b&&b.click();});
await p.waitForTimeout(2000);
await p.goto('http://127.0.0.1:8000/admin/appointments/list',{waitUntil:'networkidle'});await p.waitForTimeout(900);
await p.screenshot({path:'_reference/screenshots/appt-list.jpg',type:'jpeg',quality:84});
await b.close();console.log('APPT_LIST_SHOT');
