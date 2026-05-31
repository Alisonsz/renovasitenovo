import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext({ locale: 'pt-BR' })).newPage();
const reqs = [];
p.on('request', (r) => { const u = r.url(); if (/maps|gstatic|googleapis|kh\.|vt\?|maps\/vt/i.test(u)) reqs.push(u); });
await p.setViewportSize({ width: 1280, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await p.waitForTimeout(6000);
const info = await p.evaluate(() => {
    const f = [...document.querySelectorAll('iframe')].find((e) => /maps/i.test(e.src));
    if (!f) return { found: false };
    const r = f.getBoundingClientRect();
    return { found: true, src: f.src, w: Math.round(r.width), h: Math.round(r.height) };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/verify-map.json',
    JSON.stringify({ iframe: info, mapRequests: reqs.length, sample: reqs.slice(0, 5) }, null, 2));
await b.close();
console.log('MAP_REQS_' + reqs.length + '_IFRAME_' + info.found);
