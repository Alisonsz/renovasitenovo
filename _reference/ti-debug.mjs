import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
const logs = [];
const net = [];
p.on('console', (m) => logs.push(`[${m.type()}] ${m.text()}`.slice(0, 200)));
p.on('pageerror', (e) => logs.push('PAGEERR ' + e.message.slice(0, 200)));
p.on('response', async (r) => {
    if (/trustindex/i.test(r.url())) net.push(`${r.status()} ${r.url().slice(0, 120)}`);
});

await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'load', timeout: 30000 }).catch(() => {});
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.5));
await p.waitForTimeout(14000);

const dom = await p.evaluate(() => {
    const any = [...document.querySelectorAll('*')].filter((e) =>
        (e.tagName && e.tagName.toLowerCase().startsWith('trustindex')) ||
        (typeof e.className === 'string' && /ti-widget|ti-review|trustindex/i.test(e.className)),
    ).length;
    const iframes = [...document.querySelectorAll('iframe')].map((f) => f.src).filter((s) => /trustindex/i.test(s));
    const host = [...document.querySelectorAll('section.bg-brand-soft div')].map((d) => d.innerHTML.length);
    return { anyTiNodes: any, tiIframes: iframes, hostInnerLens: host.slice(0, 6) };
});

fs.writeFileSync(
    'C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/ti-debug.json',
    JSON.stringify({ net, dom, logs: logs.slice(0, 40) }, null, 2),
);
await b.close();
console.log(`NET${net.length}_TINODES${dom.anyTiNodes}_IFR${dom.tiIframes.length}_LOGS${logs.length}`);
