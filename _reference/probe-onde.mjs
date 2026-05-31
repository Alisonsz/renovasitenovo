import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext({ locale: 'pt-BR' })).newPage();
await p.setViewportSize({ width: 412, height: 900 });
await p.goto('https://renovalaserdepilacao.com.br/', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
await p.waitForTimeout(2500);
const r = await p.evaluate(() => {
    const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();
    const h = [...document.querySelectorAll('*')].find((e) => e.children.length === 0 && /^Onde estamos$/i.test(norm(e.innerText)));
    if (!h) return { found: false };
    const cs = getComputedStyle(h);
    // procurar divider/separator proximo (irmaos do heading ou do wrapper)
    let wrap = h;
    for (let i = 0; i < 5 && wrap && !/section|column|widget/i.test(wrap.className || ''); i++) wrap = wrap.parentElement;
    const scope = wrap || h.parentElement;
    const cands = [...scope.querySelectorAll('*')].filter((e) => {
        const r = e.getBoundingClientRect();
        const s = getComputedStyle(e);
        const thin = r.height > 0 && r.height <= 6 && r.width >= 20 && r.width <= 320;
        const hasBg = s.backgroundColor && s.backgroundColor !== 'rgba(0, 0, 0, 0)';
        const hasBorder = parseFloat(s.borderTopWidth) > 0 || parseFloat(s.borderBottomWidth) > 0;
        return thin && (hasBg || hasBorder);
    }).map((e) => {
        const s = getComputedStyle(e);
        const r = e.getBoundingClientRect();
        return { tag: e.tagName, cls: (e.className || '').toString().slice(0, 40), w: Math.round(r.width), h: Math.round(r.height), bg: s.backgroundColor, bt: s.borderTopColor + ' ' + s.borderTopWidth };
    });
    return {
        found: true,
        headingAlign: cs.textAlign,
        headingColor: cs.color,
        headingSize: cs.fontSize,
        dividers: cands,
    };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/probe-onde.json', JSON.stringify(r, null, 2));
await b.close();
console.log('PROBE_ONDE_' + (r.dividers ? r.dividers.length : 'NF'));
