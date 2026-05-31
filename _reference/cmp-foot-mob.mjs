import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const target = process.argv[2] === 'live' ? 'https://renovalaserdepilacao.com.br/' : 'http://127.0.0.1:8000/';
const tag = process.argv[2] === 'live' ? 'live' : 'loc';
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR' });
const p = await ctx.newPage();
await p.setViewportSize({ width: 412, height: 900 });
await p.goto(target, { waitUntil: 'networkidle', timeout: 60000 }).catch(() => {});
await p.waitForTimeout(2500);
// localizar a seção "Onde estamos" e capturar dela até o fim do rodapé
const box = await p.evaluate(() => {
    const all = [...document.querySelectorAll('*')];
    const h = all.find((e) => e.children.length === 0 && /^Onde estamos$/i.test((e.innerText || '').trim()));
    let sec = h;
    for (let i = 0; i < 4 && sec && !/section|footer/i.test(sec.tagName); i++) sec = sec.parentElement;
    const top = sec ? Math.round(sec.getBoundingClientRect().top + window.scrollY) : 0;
    return { top: Math.max(0, top - 10) };
});
await p.evaluate((y) => window.scrollTo(0, y), box.top);
await p.waitForTimeout(1200);
await p.screenshot({ path: `_reference/screenshots/cmp-foot-${tag}-1.jpg`, type: 'jpeg', quality: 80 });
await p.evaluate(() => window.scrollBy(0, 760));
await p.waitForTimeout(800);
await p.screenshot({ path: `_reference/screenshots/cmp-foot-${tag}-2.jpg`, type: 'jpeg', quality: 80 });
await b.close();
console.log('CMP_FOOT_' + tag);
