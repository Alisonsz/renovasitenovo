import { chromium } from 'playwright';
async function launch() {
    try {
        return await chromium.launch({ channel: 'chrome', headless: true });
    } catch {
        return await chromium.launch({ headless: true });
    }
}
const b = await launch();
const p = await (await b.newContext({ deviceScaleFactor: 1 })).newPage();
for (const vp of [
    { n: 'desktop', w: 1440, h: 900 },
    { n: 'mobile', w: 390, h: 844 },
]) {
    await p.setViewportSize({ width: vp.w, height: vp.h });
    await p.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    await p.waitForTimeout(2500);
    await p.screenshot({ path: `_reference/screenshots/v2-${vp.n}.jpg`, type: 'jpeg', quality: 80, fullPage: false });
    console.log('ok', vp.n);
}
await b.close();
