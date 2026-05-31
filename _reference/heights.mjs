import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2600);
const r = await p.evaluate(() => {
    const track = document.querySelector('ul[style*="translateX"]');
    const vpDiv = track.parentElement;
    const cards = [...track.querySelectorAll('li[data-slide] > div')];
    const hs = cards.map((c) => Math.round(c.getBoundingClientRect().height));
    const uniq = [...new Set(hs)];
    const mask = getComputedStyle(vpDiv).maskImage || getComputedStyle(vpDiv).webkitMaskImage;
    return { uniqHeights: uniq, count: hs.length, hasMask: mask && mask !== 'none' };
});
await p.screenshot({ path: '_reference/screenshots/carousel-fix.png', clip: { x: 120, y: 600, width: 1200, height: 230 } });
await b.close();
const token = `HEIGHTS_${r.uniqHeights.join('-')}_count${r.count}_mask_${r.hasMask}`;
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/heights.txt', token);
console.log(token);
