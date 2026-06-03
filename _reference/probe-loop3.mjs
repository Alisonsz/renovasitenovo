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
    const SETS=5, perSet=items.length/SETS;
    const pitch=items[1].offsetLeft-items[0].offsetLeft;
    const oneSet=items[perSet].offsetLeft-items[0].offsetLeft;
    const base=items[0].offsetLeft;
    const out={count:items.length,pitch:Math.round(pitch),oneSet:Math.round(oneSet),samples:[],snapMiss:0,nearEdge:0};
    const totalW=el.querySelector('ul').scrollWidth;
    for(let k=0;k<24;k++){ // many advances — must keep looping forever
      el.scrollLeft += pitch;
      el.dispatchEvent(new Event('scroll'));
      await new Promise(r=>setTimeout(r,170));
      const x=el.scrollLeft;
      out.samples.push(Math.round(x));
      // distance to nearest snap point (multiple of pitch from base)
      const m=(((x-base)%pitch)+pitch)%pitch;
      if(Math.min(m,pitch-m)>3) out.snapMiss++;
      // real-edge proximity: within half a viewport of 0 or end => visible blank risk
      if(x < el.clientWidth*0.5 || x > totalW - el.clientWidth*1.5) out.nearEdge++;
    }
    out.lastSamples=out.samples.slice(-6);
    return out;
  },hasBtn);
  console.log(label,'count='+r.count,'oneSet='+r.oneSet,'pitch='+r.pitch);
  console.log('  last6='+JSON.stringify(r.lastSamples));
  console.log('  snapMisses='+r.snapMiss+'/24   nearRealEdge='+r.nearEdge+'/24');
}
await testLoop(false,'DESTAQUES');
await testLoop(true ,'PREÇOS   ');
console.log('CONSOLE_ERRORS', errs.length);
await b.close();
