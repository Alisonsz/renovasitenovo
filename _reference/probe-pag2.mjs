import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/depilacao-feminina',{waitUntil:'networkidle'});await p.waitForTimeout(1200);
const r=await p.evaluate(async()=>{
  // find a section that actually has >1 page
  const navs=[...document.querySelectorAll('nav[aria-label^="Paginação"]')];
  let target=null;
  for(const nav of navs){const nums=[...nav.querySelectorAll('button')].filter(b=>/^\d+$/.test(b.textContent.trim()));if(nums.length>=2){target=nav;break;}}
  if(!target) return {noMulti:true};
  const sec=target.closest('section');
  const firstBefore=sec.querySelector('article h2')?.innerText;
  const two=[...target.querySelectorAll('button')].find(b=>b.textContent.trim()==='2');
  two.click();
  await new Promise(r=>setTimeout(r,650));
  const active=[...target.querySelectorAll('button')].find(b=>b.className.includes('bg-brand')&&/^\d+$/.test(b.textContent.trim()))?.textContent.trim();
  const firstAfter=sec.querySelector('article h2')?.innerText;
  const top=Math.round(sec.getBoundingClientRect().top);
  return {firstBefore,firstAfter,active,top,changed:firstBefore!==firstAfter};
});
console.log(JSON.stringify(r,null,0));
await b.close();
