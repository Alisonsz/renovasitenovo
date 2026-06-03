import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();const p=await (await b.newContext({locale:'pt-BR'})).newPage();
const errs=[]; p.on('console',m=>{if(m.type()==='error')errs.push(m.text());});
await p.setViewportSize({width:1366,height:950});
await p.goto('http://127.0.0.1:8000/ovodepapagaio',{waitUntil:'networkidle'});
await p.waitForTimeout(500);
await p.evaluate(()=>{const s=(x,v)=>{const e=document.querySelector(x);if(e){e.value=v;e.dispatchEvent(new Event('input',{bubbles:true}));}};s('input[type=email]','admin@renovalaser.local');s('input[type=password]','admin123');});
await p.evaluate(()=>{const b=[...document.querySelectorAll('button')].find(e=>/entrar|acessar/i.test(e.innerText));b&&b.click();});
await p.waitForTimeout(2000);
await p.goto('http://127.0.0.1:8000/admin/blog-posts/create',{waitUntil:'networkidle'});
await p.waitForTimeout(900);
// type some content into the editor and apply formatting
await p.evaluate(()=>{
  const ed=document.querySelector('.rte-content');
  ed.focus();
  ed.innerHTML='<h2>Como funciona a depilação a laser</h2><p>A tecnologia <strong>Triple Wave</strong> combina três comprimentos de onda.</p><ul><li>Indolor</li><li>Resultados rápidos</li><li>Todos os tons de pele</li></ul>';
  ed.dispatchEvent(new Event('input',{bubbles:true}));
});
await p.waitForTimeout(400);
await p.screenshot({path:'_reference/screenshots/editor-blog.jpg',type:'jpeg',quality:84});
console.log('CONSOLE_ERRORS',errs.length);
await b.close();console.log('EDITOR_SHOT');
