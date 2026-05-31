import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
const tiUrls = [];
p.on('request', (r) => { if (/trustindex/i.test(r.url())) tiUrls.push(r.url()); });

await p.setViewportSize({ width: 1440, height: 1000 });
await p.goto('https://renovalaserdepilacao.com.br/', { waitUntil: 'load', timeout: 60000 }).catch(() => {});
// rola devagar ate o fim p/ disparar lazy-load
for (let y = 0; y <= 1; y += 0.1) {
    await p.evaluate((f) => window.scrollTo(0, document.body.scrollHeight * f), y);
    await p.waitForTimeout(800);
}
await p.waitForTimeout(4000);

const data = await p.evaluate(() => {
    const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();
    // iframes
    const iframes = [...document.querySelectorAll('iframe')].map((f) => f.src).filter(Boolean);
    // qualquer no com trustindex no class/id/data
    const tiNodes = [...document.querySelectorAll('*')].filter((e) => {
        const a = (e.className && e.className.toString()) + ' ' + (e.id || '') + ' ' + [...e.attributes].map((x) => x.name).join(' ');
        return /trustindex|ti-widget|ti-reviews/i.test(a);
    });
    const tiSample = tiNodes.slice(0, 3).map((e) => e.tagName + '.' + (e.className?.toString().slice(0, 60)));
    // acha o heading "preços" e o "Dúvidas frequentes" p/ delimitar a secao de depoimentos
    const heads = [...document.querySelectorAll('h1,h2,h3')].map((h) => norm(h.innerText));
    // procura estrelas / google
    const starNodes = [...document.querySelectorAll('[class*="star"],[class*="rating"]')].length;
    const bodyHasReviewWords = /excelente|atendimento|recomendo|nota|estrelas|avalia/i.test(document.body.innerText);
    return { iframes, tiSample, tiCount: tiNodes.length, starNodes, bodyHasReviewWords, heads };
});

// localizar a faixa turquesa (depoimentos) pela posicao: entre "preços" e "Dúvidas frequentes"
const box = await p.evaluate(() => {
    const all = [...document.querySelectorAll('h2,h3')];
    const precos = all.find((h) => /Conheça nossos preços/i.test(h.innerText));
    const faq = all.find((h) => /Dúvidas frequentes/i.test(h.innerText));
    if (!precos || !faq) return null;
    const top = precos.getBoundingClientRect().bottom + window.scrollY;
    const bottom = faq.getBoundingClientRect().top + window.scrollY;
    return { top: Math.max(0, Math.round(top)), height: Math.round(bottom - top) };
});

if (box && box.height > 50) {
    await p.evaluate((t) => window.scrollTo(0, t - 60), box.top);
    await p.waitForTimeout(1500);
    await p.screenshot({ path: '_reference/screenshots/ti-live.png', fullPage: false });
}

fs.writeFileSync(
    'C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/ti2.json',
    JSON.stringify({ data, tiUrls: [...new Set(tiUrls)], box }, null, 2),
);
await b.close();
console.log('TI2_iframes' + data.iframes.length + '_ti' + data.tiCount + '_urls' + [...new Set(tiUrls)].length + '_boxH' + (box ? box.height : 0));
