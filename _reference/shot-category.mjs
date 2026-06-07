import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/depilacao-feminina',{waitUntil:'networkidle'});await p.waitForTimeout(1200);
await p.screenshot({path:'_reference/screenshots/category-mob.jpg',type:'jpeg',quality:84,fullPage:false});
// info: section h1 font size + a product h2 sample, and whether pagination exists
const r=await p.evaluate(()=>{
  const h1=document.querySelector('h1');
  const h2=document.querySelector('article h2');
  const pag=document.querySelector('nav[aria-label^="Paginação"]');
  return {h1Text:h1?.innerText, h1Size:h1?getComputedStyle(h1).fontSize:null, h2Text:h2?.innerText, h2Size:h2?getComputedStyle(h2).fontSize:null, hasPagination:!!pag};
});
console.log(JSON.stringify(r));
await b.close();console.log('DONE');
