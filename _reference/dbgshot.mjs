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
p.on('console', (m) => {
    if (m.type() === 'error') console.log('CONSOLE ERROR:', m.text());
});
p.on('pageerror', (e) => console.log('PAGE ERROR:', e.message));

for (const vp of [
    { n: 'desktop', w: 1440, h: 900 },
    { n: 'mobile', w: 390, h: 844 },
]) {
    await p.setViewportSize({ width: vp.w, height: vp.h });
    await p.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded', timeout: 30000 });
    try {
        await p.waitForSelector('h1', { timeout: 8000 });
        const txt = await p.$eval('h1', (el) => el.innerText);
        console.log(vp.n, 'h1=', JSON.stringify(txt));
    } catch {
        console.log(vp.n, 'H1 NAO ENCONTRADO');
        console.log('BODY HTML:', (await p.content()).slice(0, 400));
    }
    await p.waitForTimeout(1500);
    await p.screenshot({ path: `_reference/screenshots/v3-${vp.n}.jpg`, type: 'jpeg', quality: 82, fullPage: false });
}
await b.close();
console.log('DONE');
