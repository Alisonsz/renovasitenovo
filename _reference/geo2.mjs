import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2800);
const t = await p.evaluate(() => {
    const track = document.querySelector('ul[style*="translateX"]');
    const vp = track.parentElement;
    const vr = vp.getBoundingClientRect();
    const cards = [...track.querySelectorAll('li[data-slide]')];
    let partial = 0, inside = 0;
    const tags = cards.map((c, i) => {
        const r = c.getBoundingClientRect();
        const l = r.left - vr.left, rr = r.right - vr.left;
        const vis = rr > 0 && l < vr.width;
        if (!vis) return 'o';
        if (l >= -1 && rr <= vr.width + 1) { inside++; return 'I'; }
        partial++;
        return 'P';
    });
    return tags.join('') + `_in${inside}_pa${partial}_vpw${Math.round(vr.width)}`;
});
await b.close();
console.log('GEO2_' + t);
