import { chromium } from 'playwright';
async function launch(){try{return await chromium.launch({channel:'chrome',headless:true});}catch{return await chromium.launch({headless:true});}}
const b=await launch();
const ctx=await b.newContext({locale:'pt-BR',viewport:{width:390,height:844},isMobile:true,hasTouch:true});
const p=await ctx.newPage();
await p.goto('http://127.0.0.1:8000/',{waitUntil:'networkidle'});
await p.waitForTimeout(1000);

// confirm computed touch-action on each of the 3 tracks
const ta = await p.evaluate(()=>{
  const out={};
  const tracks=[...document.querySelectorAll('div[class*="overflow-x-auto"]')];
  tracks.forEach((el,i)=>{ out['track'+i]=getComputedStyle(el).touchAction; });
  return out;
});
console.log('TOUCH_ACTION', JSON.stringify(ta));

// Helper: do a vertical swipe starting ON a carousel and check the PAGE scrolled.
async function verticalSwipeOverCarousel(){
  // position over the features strip (first track), near top of page
  const box = await p.evaluate(()=>{
    const el=document.querySelector('div[class*="overflow-x-auto"]');
    const r=el.getBoundingClientRect();
    return {x:Math.round(r.left+r.width/2), y:Math.round(r.top+r.height/2), pageBefore: window.scrollY};
  });
  // a finger swipe upward (to scroll page down) over the carousel
  await p.touchscreen.tap(box.x, box.y).catch(()=>{});
  // Playwright has no fling; emulate with dispatched touch events that the browser treats as a vertical pan
  await p.evaluate(({x,y})=>{
    const el=document.elementFromPoint(x,y);
    function send(type, cy){
      const t=new Touch({identifier:1,target:el,clientX:x,clientY:cy});
      el.dispatchEvent(new TouchEvent(type,{bubbles:true,cancelable:true,touches:type==='touchend'?[]:[t],changedTouches:[t]}));
    }
    send('touchstart', y);
    for(let dy=0; dy<=180; dy+=30) send('touchmove', y-dy);
    send('touchend', y-180);
  }, box);
  await p.waitForTimeout(200);
  const after = await p.evaluate(()=>window.scrollY);
  return {before: box.pageBefore, after};
}
const v = await verticalSwipeOverCarousel();
console.log('VERTICAL_SWIPE pageScroll before='+v.before+' after='+v.after+' page_moved='+(v.after>v.before));
await b.close();console.log('DONE');
