import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
const netUrls = [];
p.on('request', (r) => {
    const u = r.url();
    if (/trustindex|reviews|google/i.test(u)) netUrls.push(u);
});
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('https://renovalaserdepilacao.com.br/', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
await p.waitForTimeout(6000);
// rola ate o meio p/ carregar lazy widgets
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.45));
await p.waitForTimeout(5000);

const info = await p.evaluate(() => {
    const out = {};
    // procura scripts trustindex
    out.scripts = [...document.querySelectorAll('script[src]')]
        .map((s) => s.src)
        .filter((s) => /trustindex/i.test(s));
    // procura containers trustindex
    const ti = document.querySelector('[class*="trustindex"],[id*="trustindex"],[class*="ti-widget"],[data-widget-id]');
    out.containerHtml = ti ? ti.outerHTML.slice(0, 300) : 'none';
    // qualquer elemento com 'trustindex' no class
    out.tiClasses = [...new Set([...document.querySelectorAll('*')]
        .map((e) => e.className)
        .filter((c) => typeof c === 'string' && /trustindex|ti-widget/i.test(c)))].slice(0, 10);
    // texto "Google" perto de reviews
    out.hasGoogleReviews = /Google|avalia/i.test(document.body.innerText);
    return out;
});
fs.writeFileSync(
    'C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/trustindex.json',
    JSON.stringify({ info, netUrls: [...new Set(netUrls)].slice(0, 25) }, null, 2),
);
await b.close();
console.log('TI_scripts_' + info.scripts.length + '_classes_' + info.tiClasses.length + '_net_' + [...new Set(netUrls)].length);
