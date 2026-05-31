import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR', deviceScaleFactor: 0.78 });
const p = await ctx.newPage();
await p.setViewportSize({ width: 1280, height: 820 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
const y = await p.evaluate(() => {
    const h = [...document.querySelectorAll('h2')].find((e) => /Onde estamos/i.test(e.innerText));
    const sec = h ? h.closest('section') : null;
    const r = (sec || document.body).getBoundingClientRect();
    return Math.round(r.top + window.scrollY) - 10;
});
await p.evaluate((yy) => window.scrollTo(0, yy), y);
await p.waitForTimeout(5000);
await p.screenshot({ path: `_reference/screenshots/foot-desktop-a.jpg`, type: 'jpeg', quality: 78 });
await b.close();
console.log('MAP_SHOT');
