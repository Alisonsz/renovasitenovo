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
const band = await p.$('section:has(iframe[title*="Avalia"])');
if (!band) { console.log('NOBAND'); await b.close(); process.exit(0); }
await band.scrollIntoViewIfNeeded();
await p.waitForTimeout(8000);
await band.scrollIntoViewIfNeeded();
await p.waitForTimeout(800);
const box = await band.boundingBox();
await p.screenshot({
    path: '_reference/screenshots/band.jpg',
    type: 'jpeg',
    quality: 82,
    clip: { x: 0, y: Math.max(0, box.y), width: 1440, height: Math.min(box.height, 880) },
});
await b.close();
console.log('SHOT_OK_h' + Math.round(box.height));
