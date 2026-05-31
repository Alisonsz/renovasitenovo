import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(1500);

function titles() {
    return p.evaluate(() => {
        const lis = [...document.querySelectorAll('ul[style] li[data-slide]')];
        // pega os cards visiveis dentro do viewport
        const vp = document.querySelector('.overflow-hidden');
        const vr = vp.getBoundingClientRect();
        return lis
            .filter((li) => {
                const r = li.getBoundingClientRect();
                return r.left >= vr.left - 5 && r.right <= vr.right + 5;
            })
            .map((li) => li.querySelector('p')?.innerText);
    });
}

await p.screenshot({ path: '_reference/screenshots/carousel-t0.jpg', type: 'jpeg', quality: 80, clip: { x: 0, y: 300, width: 1440, height: 220 } });
const t0 = await titles();
await p.waitForTimeout(3700);
await p.screenshot({ path: '_reference/screenshots/carousel-t1.jpg', type: 'jpeg', quality: 80, clip: { x: 0, y: 300, width: 1440, height: 220 } });
const t1 = await titles();
await b.close();
console.log('T0::' + t0.join('|'));
console.log('T1::' + t1.join('|'));
