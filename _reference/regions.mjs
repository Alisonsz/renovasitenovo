import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2000);

async function shot(sel, name, pad = 30) {
    const el = await p.$(sel);
    if (!el) { console.log('NOSEL ' + name); return; }
    await el.scrollIntoViewIfNeeded();
    await p.waitForTimeout(400);
    const box = await el.boundingBox();
    await p.screenshot({
        path: `_reference/screenshots/${name}.png`,
        clip: { x: Math.max(0, box.x - pad), y: Math.max(0, box.y - pad), width: Math.min(1440, box.width + pad * 2), height: box.height + pad * 2 },
    });
    console.log('OK ' + name);
}

// hero (botao) — primeira section
await shot('main section:first-of-type', 'r-hero', 0);
// about (linha verde)
await shot('main section:nth-of-type(2)', 'r-about');
// pricing (sem setas)
await shot('main section:nth-of-type(4)', 'r-pricing');
await b.close();
console.log('DONE');
