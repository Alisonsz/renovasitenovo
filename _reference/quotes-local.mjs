import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
await p.waitForTimeout(2000);
const band = await p.$('section.bg-brand-soft');
if (band) await band.scrollIntoViewIfNeeded();
await p.waitForTimeout(8000);

const info = await p.evaluate(() => {
    const sec = document.querySelector('section.bg-brand-soft');
    const r = sec.getBoundingClientRect();
    const imgs = [...sec.querySelectorAll('img')].map((im) => {
        const ir = im.getBoundingClientRect();
        return { x: Math.round(ir.left - r.left), y: Math.round(ir.top - r.top), w: Math.round(ir.width) };
    });
    let reviews = 0;
    const fr = sec.querySelector('iframe');
    try { reviews = fr.contentDocument.querySelectorAll('.ti-review-item').length; } catch (e) {}
    return { secW: Math.round(r.width), secH: Math.round(r.height), imgs, reviews };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/quotes-local.json', JSON.stringify(info, null, 2));
if (band) { await band.scrollIntoViewIfNeeded(); await p.waitForTimeout(800); }
const box = await band.boundingBox();
await p.screenshot({ path: '_reference/screenshots/quotes-local.png', clip: { x: 0, y: box.y, width: 1440, height: Math.min(box.height, 900) } });
await b.close();
console.log(`LOCAL_imgs_${info.imgs.length}_reviews_${info.reviews}_pos_${JSON.stringify(info.imgs)}`);
