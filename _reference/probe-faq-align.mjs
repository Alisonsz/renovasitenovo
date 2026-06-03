import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});await p.waitForTimeout(1500);
const r=await p.evaluate(()=>{
  const h=[...document.querySelectorAll('h2')].find(e=>/Dúvidas frequentes/i.test(e.innerText));
  const track=h.closest('section').querySelector('div[class*="overflow-x-auto"]');
  // the centered card = the inner div of the slide nearest viewport center
  const slides=[...track.querySelectorAll('[data-carousel-item]')];
  const vc=track.getBoundingClientRect().left + track.clientWidth/2;
  let best=null,bd=1e9;
  slides.forEach(s=>{const card=s.firstElementChild||s;const cr=card.getBoundingClientRect();const cc=cr.left+cr.width/2;const d=Math.abs(cc-vc);if(d<bd){bd=d;best=card;}});
  const tr=track.getBoundingClientRect();
  const cr=best.getBoundingClientRect();
  return {
    viewportW: window.innerWidth,
    trackLeft: Math.round(tr.left), trackRight: Math.round(window.innerWidth - tr.right), trackW: Math.round(tr.width), clientW: track.clientWidth, scrollLeft: Math.round(track.scrollLeft),
    cardLeftGap: Math.round(cr.left), cardRightGap: Math.round(window.innerWidth - cr.right), cardW: Math.round(cr.width),
  };
});
console.log(JSON.stringify(r,null,2));
await b.close();
