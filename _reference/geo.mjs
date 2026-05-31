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
await p.waitForTimeout(2800);
const g = await p.evaluate(() => {
    const track = document.querySelector('ul[style*="translateX"]');
    const vp = track.parentElement;
    const vr = vp.getBoundingClientRect();
    const cs = getComputedStyle(vp);
    const cards = [...track.querySelectorAll('li[data-slide]')].slice(0, 7).map((c) => {
        const r = c.getBoundingClientRect();
        return { l: Math.round(r.left - vr.left), r: Math.round(r.right - vr.left), w: Math.round(r.width) };
    });
    return {
        vpLeft: Math.round(vr.left),
        vpRight: Math.round(vr.right),
        vpWidth: Math.round(vr.width),
        padL: cs.paddingLeft,
        padR: cs.paddingRight,
        transform: track.style.transform,
        cards,
    };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/geo.txt', JSON.stringify(g, null, 2));
await b.close();
// token: largura vp, largura card0, right do card3 vs vpWidth
console.log(`VPW${g.vpWidth}_CW${g.cards[0].w}_C3R${g.cards[3].r}_C4L${g.cards[4].l}_TX_${g.transform.replace(/[^0-9-]/g,'')}`);
