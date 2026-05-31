import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
let loaderReq = false;
p.on('request', (r) => { if (/cdn\.trustindex\.io\/loader\.js/.test(r.url())) loaderReq = true; });
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
// rola ate a faixa de depoimentos p/ disparar lazy + dar tempo do widget montar
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
await p.waitForTimeout(8000);

const r = await p.evaluate(() => {
    const band = [...document.querySelectorAll('section')].find((s) =>
        s.className.includes('bg-brand-soft'),
    );
    const widgets = document.querySelectorAll('.ti-widget, .ti-review-item, [class*="ti-widget"]').length;
    const reviews = document.querySelectorAll('.ti-review-item').length;
    const names = [...document.querySelectorAll('.ti-name')].slice(0, 3).map((n) => n.textContent.trim());
    const bandH = band ? Math.round(band.getBoundingClientRect().height) : 0;
    return { widgets, reviews, names, bandH };
});

// scroll de volta e screenshot da faixa
const band = await p.$('section.bg-brand-soft');
if (band) { await band.scrollIntoViewIfNeeded(); await p.waitForTimeout(1500); }
await p.screenshot({ path: '_reference/screenshots/ti-rendered.png', fullPage: false });
await b.close();
console.log(`LOADER_${loaderReq}_widgets_${r.widgets}_reviews_${r.reviews}_bandH_${r.bandH}_names_${r.names.join('|')}`);
