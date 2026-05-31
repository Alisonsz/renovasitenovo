import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext({ locale: 'pt-BR' })).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('https://renovalaserdepilacao.com.br/', { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
await p.waitForTimeout(2500);
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await p.waitForTimeout(2500);
await p.evaluate(() => window.scrollTo(0, 0));
await p.waitForTimeout(1000);

// Coleta seccoes/containers com background gradient ou cor e seu texto-chave
const data = await p.evaluate(() => {
    const want = ['preço', 'preços', 'dúvida', 'dúvidas frequentes', 'onde estamos', 'referência', 'avalia'];
    const out = [];
    const seen = new Set();
    for (const el of document.querySelectorAll('section, div')) {
        const cs = getComputedStyle(el);
        const bg = cs.backgroundImage;
        const bc = cs.backgroundColor;
        const hasGrad = bg && bg.includes('gradient');
        const hasColor = bc && bc !== 'rgba(0, 0, 0, 0)' && bc !== 'transparent';
        if (!hasGrad && !hasColor) continue;
        const r = el.getBoundingClientRect();
        if (r.width < 600 || r.height < 120) continue; // so containers grandes
        const txt = (el.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 60).toLowerCase();
        const key = `${Math.round(r.top + window.scrollY)}-${hasGrad ? bg : bc}`;
        if (seen.has(key)) continue;
        seen.add(key);
        out.push({
            y: Math.round(r.top + window.scrollY),
            h: Math.round(r.height),
            w: Math.round(r.width),
            grad: hasGrad ? bg : null,
            color: hasColor ? bc : null,
            snippet: txt,
        });
    }
    return out.sort((a, b) => a.y - b.y);
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/gradients.json', JSON.stringify(data, null, 2));
await b.close();
console.log('GRAD_COUNT_' + data.length);
