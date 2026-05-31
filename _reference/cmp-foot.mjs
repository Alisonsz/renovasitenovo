import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const target = process.argv[2]; // 'live' or 'local'
const URL = target === 'live' ? 'https://renovalaserdepilacao.com.br/' : 'http://127.0.0.1:8000/';
const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'mobile', width: 390, height: 844 },
];
const b = await launch();
const out = {};
for (const vp of viewports) {
    const p = await (await b.newContext({ locale: 'pt-BR' })).newPage();
    await p.setViewportSize({ width: vp.width, height: vp.height });
    await p.goto(URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
    await p.waitForTimeout(2500);
    await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await p.waitForTimeout(2500);
    await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await p.waitForTimeout(1500);

    // localizar secao "Onde estamos" e o footer
    const info = await p.evaluate(() => {
        const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();
        // onde estamos
        let onde = null;
        const h = [...document.querySelectorAll('h1,h2,h3')].find((e) => /onde estamos/i.test(e.innerText));
        if (h) {
            let sec = h.closest('section') || h.parentElement;
            const r = sec.getBoundingClientRect();
            onde = { y: Math.round(r.top + window.scrollY), h: Math.round(r.height), text: norm(sec.innerText).slice(0, 160), hasIframe: !!sec.querySelector('iframe') };
        }
        // footer = pega o footer ou ultima secao
        const f = document.querySelector('footer') || [...document.querySelectorAll('section,div')].pop();
        const fr = f.getBoundingClientRect();
        const fcs = getComputedStyle(f);
        return {
            onde,
            footer: { y: Math.round(fr.top + window.scrollY), h: Math.round(fr.height), bg: fcs.backgroundColor, bgImg: fcs.backgroundImage.slice(0, 80), text: norm(f.innerText).slice(0, 300) },
            docH: document.body.scrollHeight,
        };
    });
    out[vp.name] = info;

    // screenshot full bottom: do inicio do "onde estamos" ate o fim
    const startY = info.onde ? info.onde.y - 20 : info.docH - 1400;
    const totalH = info.docH - startY;
    await p.evaluate((y) => window.scrollTo(0, y), startY);
    await p.waitForTimeout(800);
    await p.screenshot({ path: `_reference/screenshots/cmp-${target}-${vp.name}.jpg`, type: 'jpeg', quality: 80, fullPage: false });
    await p.close();
}
fs.writeFileSync(`C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/cmp-${target}.json`, JSON.stringify(out, null, 2));
await b.close();
console.log('CMP_DONE_' + target);
