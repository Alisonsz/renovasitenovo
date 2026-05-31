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
await p.goto('https://renovalaserdepilacao.com.br/', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
await p.waitForTimeout(2500);
const y = await p.evaluate(() => {
    const h = [...document.querySelectorAll('*')].find((e) => e.children.length === 0 && /Onde estamos/i.test(e.innerText || ''));
    const sec = h ? h.getBoundingClientRect() : null;
    return (sec ? Math.round(sec.top + window.scrollY) : document.body.scrollHeight - 1400) - 20;
});
await p.evaluate((yy) => window.scrollTo(0, yy), y);
await p.waitForTimeout(800);
await p.screenshot({ path: `_reference/screenshots/foot-live-${vp.name}-a.jpg`, type: 'jpeg', quality: 78 });
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await p.waitForTimeout(800);
await p.screenshot({ path: `_reference/screenshots/foot-live-${vp.name}-b.jpg`, type: 'jpeg', quality: 78 });
await b.close();
console.log('FOOT_LIVE_' + vp.name);
