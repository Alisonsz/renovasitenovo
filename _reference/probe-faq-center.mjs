import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1400);
const r=await p.evaluate(()=>{
  function probe(re,hasBtn){
    let el=null;
    const tracks=[...document.querySelectorAll('div[class*="overflow-x-auto"]')];
    for(const t of tracks){const sec=t.closest('section');const h=sec?sec.querySelector('h2'):null;if(h&&re.test(h.textContent)){el=t;break;}}
    if(!el && hasBtn!==undefined){el=tracks.find(t=>(!!t.querySelector('button'))===hasBtn);}
    if(!el) return null;
    const list=[...el.querySelectorAll('[data-carousel-item]')];
    const vc=el.scrollLeft+el.clientWidth/2;
    let bd=1e9, bestC=null;
    list.forEach(it=>{const c=it.offsetLeft+it.offsetWidth/2;const d=Math.abs(c-vc);if(d<bd){bd=d;bestC=c;}});
    return {clientW:el.clientWidth, scrollLeft:Math.round(el.scrollLeft), nearestOffCenter:Math.round(bd)};
  }
  return {
    faq: probe(/Dúvidas frequentes/i),
    pricing: probe(/Conheça nossos preços/i),
  };
});
console.log('FAQ    ', JSON.stringify(r.faq), ' (nearestOffCenter deve ser ~0)');
console.log('PRICING', JSON.stringify(r.pricing), ' (referência, já estava bom)');
await b.close();
