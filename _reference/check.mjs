import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
let err = '';
p.on('pageerror', (e) => { err = e.message; });
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2500);
let h1 = '';
try { h1 = await p.$eval('h1', (el) => el.innerText.replace(/\s+/g, ' ').trim()); } catch {}
const ok = h1.includes('Excel');
await p.screenshot({ path: '_reference/screenshots/final-desktop.jpg', type: 'jpeg', quality: 80, fullPage: true });
fs.writeFileSync('_reference/check.txt', `OK=${ok} ERR=${err || 'none'} H1=${h1}`);
await b.close();
// saida de uma unica palavra
console.log(ok ? 'RESULT_PASS' : 'RESULT_FAIL');
