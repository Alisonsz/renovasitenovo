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
p.on('console', (m) => logs.push(`[${m.type()}] ${m.text()}`.slice(0, 160)));
p.on('pageerror', (e) => logs.push('ERR ' + e.message.slice(0, 160)));
p.on('response', (r) => { if (/trustindex/i.test(r.url())) net.push(`${r.status()} ${r.url().slice(0, 100)}`); });

await p.setViewportSize({ width: 1440, height: 1200 });
await p.goto('http://127.0.0.1:8000/ti-test.html', { waitUntil: 'load', timeout: 30000 }).catch(() => {});
await p.waitForTimeout(12000);
const dom = await p.evaluate(() => {
    const ti = document.querySelectorAll('.ti-widget, .ti-review-item, [class*="ti-widget"]').length;
    const names = [...document.querySelectorAll('.ti-name')].slice(0, 3).map((n) => n.textContent.trim());
    return { ti, names, bodyLen: document.body.innerHTML.length };
});
await p.screenshot({ path: '_reference/screenshots/ti-isolate.png', fullPage: false });
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/ti-isolate.json', JSON.stringify({ net, dom, logs }, null, 2));
await b.close();
console.log(`ISO_ti${dom.ti}_net${net.length}_names_${dom.names.join('|')}`);
