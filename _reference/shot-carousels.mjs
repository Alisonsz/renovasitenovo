import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR' });
const p = await ctx.newPage();
p.on('console', (m) => { if (m.type() === 'error') console.log('ERR', m.text()); });
await p.setViewportSize({ width: 1366, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await p.waitForTimeout(1500);

// helper: find a scroll track by a text inside it
async function trackInfo(label) {
    return await p.evaluate(() => {
        const out = {};
        // features track: container with the feature cards
        const featLi = [...document.querySelectorAll('[data-carousel-item]')];
        return featLi.length;
    });
}

// FEATURES: desktop screenshot (layout check)
const features = await p.evaluate(() => {
    const li = document.querySelector('[data-carousel-item]');
    const track = li ? li.closest('div[class*="overflow-x-auto"]') : null;
    if (!track) return null;
    const r = track.getBoundingClientRect();
    return { y: Math.round(r.top + window.scrollY), count: document.querySelectorAll('[data-carousel-item]').length };
});
console.log('FEATURES_ITEMS_TOTAL', features?.count);

await p.evaluate((y) => window.scrollTo(0, y - 30), features.y);
await p.waitForTimeout(700);
await p.screenshot({ path: '_reference/screenshots/carousel-features.jpg', type: 'jpeg', quality: 82 });

// Test infinite wrap on FEATURES: read scrollLeft, push far right, confirm it wraps back into the middle band
const featProbe = await p.evaluate(async () => {
    const li = document.querySelector('[data-carousel-item]');
    const el = li.closest('div[class*="overflow-x-auto"]');
    const oneSet = (el.querySelector('ul').scrollWidth) / 3;
    const before = el.scrollLeft;
    // jump near the end of the last set
    el.scrollLeft = oneSet * 1.6;
    await new Promise(r => setTimeout(r, 60));
    el.dispatchEvent(new Event('scroll'));
    await new Promise(r => setTimeout(r, 120));
    const after = el.scrollLeft;
    return { oneSet: Math.round(oneSet), before: Math.round(before), pushed: Math.round(oneSet*1.6), after: Math.round(after), clientW: el.clientWidth };
});
console.log('FEAT_WRAP', JSON.stringify(featProbe));

// PRICING: scroll to it, screenshot desktop (should be centered, unchanged)
const pricingY = await p.evaluate(() => {
    const h = [...document.querySelectorAll('h2')].find(e => /Conheça nossos preços/i.test(e.innerText));
    const sec = h ? h.closest('section') : null;
    return sec ? Math.round(sec.getBoundingClientRect().top + window.scrollY) : 0;
});
await p.evaluate((y) => window.scrollTo(0, y - 20), pricingY);
await p.waitForTimeout(700);
await p.screenshot({ path: '_reference/screenshots/carousel-pricing-desktop.jpg', type: 'jpeg', quality: 82 });
const pricingCount = await p.evaluate(() => {
    const lis = [...document.querySelectorAll('[data-carousel-item]')];
    // count only pricing cards (have a button)
    return lis.filter(li => li.querySelector('button')).length;
});
console.log('PRICING_CARDS_DESKTOP', pricingCount);

await b.close();
console.log('CAROUSEL_SHOTS_DONE');
