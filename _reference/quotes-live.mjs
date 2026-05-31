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
await p.waitForTimeout(3000);
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight * 0.42));
await p.waitForTimeout(4000);

// localiza imagens de aspas (aspas-avaliacoes) e o widget trustindex
const info = await p.evaluate(() => {
    const imgs = [...document.querySelectorAll('img')]
        .filter((im) => /aspas/i.test(im.currentSrc || im.src || ''))
        .map((im) => {
            const r = im.getBoundingClientRect();
            const cs = getComputedStyle(im);
            return {
                src: (im.currentSrc || im.src).split('/').pop(),
                x: Math.round(r.left), y: Math.round(r.top + window.scrollY),
                w: Math.round(r.width), h: Math.round(r.height),
                transform: cs.transform,
                rotated: cs.transform !== 'none',
            };
        });
    // tambem procura aspas como background-image
    const bgQuotes = [];
    for (const el of document.querySelectorAll('*')) {
        const bi = getComputedStyle(el).backgroundImage;
        if (/aspas/i.test(bi)) {
            const r = el.getBoundingClientRect();
            bgQuotes.push({ x: Math.round(r.left), y: Math.round(r.top + window.scrollY), w: Math.round(r.width), h: Math.round(r.height) });
        }
    }
    return { imgs, bgQuotes };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/quotes.json', JSON.stringify(info, null, 2));

// screenshot da regiao das aspas/depoimentos
await p.evaluate(() => {
    const im = [...document.querySelectorAll('img')].find((i) => /aspas/i.test(i.currentSrc || i.src || ''));
    if (im) im.scrollIntoView({ block: 'center' });
});
await p.waitForTimeout(1500);
await p.screenshot({ path: '_reference/screenshots/quotes-live.png', fullPage: false });
await b.close();
console.log(`IMGS_${info.imgs.length}_BG_${info.bgQuotes.length}`);
