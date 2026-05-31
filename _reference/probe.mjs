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
await p.waitForTimeout(2500);
const o = await p.evaluate(() => {
    const h1 = document.querySelector('h1');
    const spans = [...h1.querySelectorAll('span')].map((s) => ({
        t: s.innerText.replace(/\s+/g, ' ').trim().slice(0, 14),
        fs: getComputedStyle(s).fontSize,
    }));
    // card: li que contem um svg e dois <p>
    const featLi = [...document.querySelectorAll('li')].find(
        (li) => li.querySelector('svg') && li.querySelectorAll('p').length === 2,
    );
    return {
        spans,
        cardRadius: featLi ? getComputedStyle(featLi).borderTopLeftRadius : 'NOLI',
        cardShadow: featLi ? getComputedStyle(featLi).boxShadow.slice(0, 30) : 'NOLI',
        cardClass: featLi ? featLi.className.slice(0, 80) : 'NOLI',
    };
});
fs.writeFileSync(
    'C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/probe.txt',
    JSON.stringify(o, null, 2),
);
await b.close();
// token curto: radius e ultima span size
const last = o.spans[o.spans.length - 1];
console.log(`PROBE_radius_${o.cardRadius.replace(/\D/g, '')}_lastspan_${(last?.fs || 'x').replace(/\D/g, '')}`);
