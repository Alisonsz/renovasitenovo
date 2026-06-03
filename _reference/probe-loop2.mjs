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
    const pitch=items[1].offsetLeft-items[0].offsetLeft;
    const oneSet=items[items.length/5].offsetLeft-items[0].offsetLeft;
    const out={pitch:Math.round(pitch),oneSet:Math.round(oneSet),start:Math.round(el.scrollLeft),samples:[]};
    // simulate user scrolling forward in chunks, settling between each (like fling+stop)
    for(let k=0;k<10;k++){
      el.scrollLeft += pitch; // advance ~1 card
      el.dispatchEvent(new Event('scroll'));
      await new Promise(r=>setTimeout(r,170)); // let the 140ms settle-timer fire
      out.samples.push(Math.round(el.scrollLeft));
    }
    // is every sample within the safe band [0, 2*oneSet]?
    out.within = out.samples.every(x=>x>=-2 && x<=oneSet*2+2);
    // does each sample sit on a snap point (multiple of pitch offset from item0)?
    const base=items[0].offsetLeft;
    out.onSnap = out.samples.every(x=>Math.abs(((x-base)%pitch+pitch)%pitch)<=2 || Math.abs((((x-base)%pitch+pitch)%pitch)-pitch)<=2);
    return out;
  },hasBtn);
  console.log(label, 'oneSet='+r.oneSet, 'pitch='+r.pitch);
  console.log('  samples='+JSON.stringify(r.samples));
  console.log('  within-band='+r.within+'  on-snap-points='+r.onSnap);
}
await testLoop(false,'DESTAQUES');
await testLoop(true ,'PREÇOS   ');
console.log('CONSOLE_ERRORS', errs.length);
await b.close();
