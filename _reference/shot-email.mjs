import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const p=await (await b.newContext()).newPage();
await p.setViewportSize({width:680,height:900});
await p.goto('file:///' + 'C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/email-preview.html'.replace(/ /g,'%20'));
await p.waitForTimeout(500);
await p.screenshot({path:'_reference/screenshots/email-recovery.jpg',type:'jpeg',quality:82,fullPage:true});
await b.close();
console.log('EMAIL_SHOT');
