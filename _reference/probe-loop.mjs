import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();

// ---- DESKTOP: features loop wrap (clean, no smooth) ----
let ctx = await b.newContext({ locale: 'pt-BR', viewport: { width: 1366, height: 900 } });
let p = await ctx.newPage();
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await p.waitForTimeout(1200);

const feat = await p.evaluate(async () => {
    const li = document.querySelector('[data-carousel-item]');
    const el = li.closest('div[class*="overflow-x-auto"]');
    const ul = el.querySelector('ul');
    const oneSet = ul.scrollWidth / 3;
    el.style.scrollBehavior = 'auto';
    const start = el.scrollLeft;
    // push beyond the middle band (simulates user dragging right past the boundary)
    el.scrollLeft = oneSet * 1.6;
    el.dispatchEvent(new Event('scroll'));
    await new Promise(r => setTimeout(r, 50));
    const afterFwd = el.scrollLeft;
    // push before the band (drag left)
    el.scrollLeft = oneSet * 0.4;
    el.dispatchEvent(new Event('scroll'));
    await new Promise(r => setTimeout(r, 50));
    const afterBack = el.scrollLeft;
    return { oneSet: Math.round(oneSet), start: Math.round(start), afterFwd: Math.round(afterFwd), afterBack: Math.round(afterBack) };
});
// expectation: afterFwd wrapped down by ~oneSet (1.6 -> 0.6), afterBack wrapped up (0.4 -> 1.4)
console.log('DESKTOP_FEATURES_WRAP', JSON.stringify(feat));
console.log('  fwd_wrapped =', Math.abs(feat.afterFwd - feat.oneSet * 0.6) < 8);
console.log('  back_wrapped=', Math.abs(feat.afterBack - feat.oneSet * 1.4) < 8);
await ctx.close();

// ---- MOBILE: pricing becomes a loop carousel (tripled) ----
ctx = await b.newContext({ locale: 'pt-BR', viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
p = await ctx.newPage();
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await p.waitForTimeout(1200);
const pricing = await p.evaluate(() => {
    const cards = [...document.querySelectorAll('[data-carousel-item]')].filter(li => li.querySelector('button'));
    return { count: cards.length };
});
console.log('MOBILE_PRICING_CARDS', pricing.count, '(esperado 9 = 3x3)');

// screenshot mobile pricing
const y = await p.evaluate(() => {
    const h = [...document.querySelectorAll('h2')].find(e => /Conheça nossos preços/i.test(e.innerText));
    return h ? Math.round(h.closest('section').getBoundingClientRect().top + window.scrollY) : 0;
});
await p.evaluate((yy) => window.scrollTo(0, yy - 10), y);
await p.waitForTimeout(500);
await p.screenshot({ path: '_reference/screenshots/carousel-pricing-mobile.jpg', type: 'jpeg', quality: 82 });
await ctx.close();

await b.close();
console.log('PROBE_LOOP_DONE');
