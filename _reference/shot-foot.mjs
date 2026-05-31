import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const vp = process.argv[2] === 'mobile' ? { name: 'mobile', width: 390, height: 844 } : { name: 'desktop', width: 1280, height: 820 };
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR', deviceScaleFactor: vp.name === 'desktop' ? 0.78 : 1 });
const p = await ctx.newPage();
await p.setViewportSize({ width: vp.width, height: vp.height });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
await p.waitForTimeout(2000);
const y = await p.evaluate(() => {
    const h = [...document.querySelectorAll('h2')].find((e) => /Onde estamos/i.test(e.innerText));
    const sec = h ? h.closest('section') : null;
    const r = (sec || document.body).getBoundingClientRect();
    return Math.round(r.top + window.scrollY) - 10;
});
await p.evaluate((yy) => window.scrollTo(0, yy), y);
await p.waitForTimeout(600);
await p.screenshot({ path: `_reference/screenshots/foot-${vp.name}-a.jpg`, type: 'jpeg', quality: 78 });
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await p.waitForTimeout(600);
await p.screenshot({ path: `_reference/screenshots/foot-${vp.name}-b.jpg`, type: 'jpeg', quality: 78 });
await b.close();
console.log('FOOT_SHOT_' + vp.name);
