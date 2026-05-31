import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
await p.waitForTimeout(2000);
// leva a faixa pra viewport
const band = await p.$('section.bg-brand-soft');
if (band) await band.scrollIntoViewIfNeeded();
await p.waitForTimeout(9000);

// conta reviews DENTRO do iframe
let reviews = 0, names = [], frameH = 0;
const fel = await p.$('section.bg-brand-soft iframe');
if (fel) {
    frameH = Math.round((await fel.boundingBox())?.height || 0);
    const fr = await fel.contentFrame();
    if (fr) {
        reviews = await fr.$$eval('.ti-review-item', (e) => e.length).catch(() => 0);
        names = await fr.$$eval('.ti-name', (e) => e.slice(0, 3).map((n) => n.textContent.trim())).catch(() => []);
    }
}
if (band) await band.scrollIntoViewIfNeeded();
await p.waitForTimeout(1000);
await p.screenshot({ path: '_reference/screenshots/ti-final.png', fullPage: false });
await b.close();
console.log(`FINAL_reviews_${reviews}_frameH_${frameH}_names_${names.join('|')}`);
