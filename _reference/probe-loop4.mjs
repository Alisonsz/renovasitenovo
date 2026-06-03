import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
const errs=[]; p.on('console',m=>{if(m.type()==='error')errs.push(m.text());});
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1200);

async function testLoop(hasBtn,label){
  const r=await p.evaluate(async(hasBtn)=>{
    const lis=[...document.querySelectorAll('[data-carousel-item]')];
    const li=hasBtn?lis.find(x=>x.querySelector('button')):lis.find(x=>!x.querySelector('button'));
    const el=li.closest('div[class*="overflow-x-auto"]');
    const items=[...el.querySelectorAll('[data-carousel-item]')];
    const SETS=3, perSet=items.length/SETS;
    const pitch=items[1].offsetLeft-items[0].offsetLeft;
    const oneSet=items[perSet].offsetLeft-items[0].offsetLeft;
    const totalW=el.querySelector('ul').scrollWidth;
    let nearEdge=0; const samples=[];
    // advance ~2 cards at a time, settle, 20 times (simulates many swipes)
    for(let k=0;k<20;k++){
      el.scrollLeft += pitch*2;
      el.dispatchEvent(new Event('scroll'));
      await new Promise(r=>setTimeout(r,170));
      const x=el.scrollLeft; samples.push(Math.round(x));
      if(x < el.clientWidth*0.4 || x > totalW - el.clientWidth*1.4) nearEdge++;
    }
    return {count:items.length,oneSet:Math.round(oneSet),pitch:Math.round(pitch),totalW:Math.round(totalW),nearEdge,last6:samples.slice(-6)};
  },hasBtn);
  console.log(label,'count='+r.count,'oneSet='+r.oneSet,'totalW='+r.totalW);
  console.log('  last6='+JSON.stringify(r.last6)+'  nearRealEdge='+r.nearEdge+'/20 (deve ser 0)');
}
await testLoop(false,'DESTAQUES');
await testLoop(true ,'PREÇOS   ');
await testLoop(false,'(faq via no-btn already covered)');
console.log('CONSOLE_ERRORS', errs.length);
await b.close();
