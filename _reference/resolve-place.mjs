import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext({ locale: 'pt-BR' })).newPage();
await p.goto('https://share.google/3ctrckduEOgPVs3KW', { waitUntil: 'domcontentloaded', timeout: 45000 }).catch(() => {});
await p.waitForTimeout(5000);
const url1 = p.url();
// tenta seguir ate maps
await p.waitForTimeout(3000);
const url2 = p.url();
const title = await p.title().catch(() => '');
const html = await p.content().catch(() => '');

function find(re) { const m = html.match(re); return m ? m[0] : null; }
const out = {
    url1, url2, title,
    cid: find(/0x[0-9a-f]{8,}:0x[0-9a-f]{8,}/i),
    placeId: find(/ChIJ[A-Za-z0-9_-]{18,}/),
    ratingHint: find(/[0-9],[0-9]\s*(estrela|★)/i) || find(/"ratingValue"[^}]{0,30}/),
    nameHint: title,
};
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/place.json', JSON.stringify(out, null, 2));
await b.close();
console.log(`URL2_${url2.slice(0, 90)} CID_${out.cid} PID_${out.placeId} TITLE_${title.slice(0,60)}`);
