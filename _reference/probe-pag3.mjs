import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:1280,height:900}});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/depilacao-feminina',{waitUntil:'networkidle'});await p.waitForTimeout(1200);
const r=await p.evaluate(async()=>{
  const navs=[...document.querySelectorAll('nav[aria-label^="Paginação"]')];
  const out=[];
  for(const nav of navs){
    const nums=[...nav.querySelectorAll('button')].filter(b=>/^\d+$/.test(b.textContent.trim()));
    out.push({pages:nums.length, labels:nums.map(n=>n.textContent.trim())});
  }
  // click "2" on the FIRST nav that has >=2 pages, then re-read active classes
  const target=navs.find(nav=>[...nav.querySelectorAll('button')].filter(b=>/^\d+$/.test(b.textContent.trim())).length>=2);
  let detail=null;
  if(target){
    const two=[...target.querySelectorAll('button')].find(b=>b.textContent.trim()==='2');
    two.click();
    await new Promise(r=>setTimeout(r,200));
    detail=[...target.querySelectorAll('button')].filter(b=>/^\d+$/.test(b.textContent.trim())).map(b=>({n:b.textContent.trim(),active:b.className.includes('bg-brand text-white')}));
  }
  return {navsSummary:out, afterClick:detail};
});
console.log(JSON.stringify(r,null,1));
await b.close();
