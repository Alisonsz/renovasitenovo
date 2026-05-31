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
await p.waitForTimeout(2000);
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await p.waitForTimeout(2500);
const src = await p.evaluate(() => {
    // logo dentro do footer (proximo ao "Siga a gente"/colunas Menu)
    const menu = [...document.querySelectorAll('*')].find((e) => e.innerText && e.innerText.trim() === 'Menu');
    let scope = menu;
    for (let i = 0; i < 6 && scope; i++) scope = scope.parentElement;
    const imgs = [...(scope ? scope.querySelectorAll('img') : [])].map((im) => im.currentSrc || im.src);
    // fallback: qualquer logo perto do fim
    const allLogos = [...document.querySelectorAll('img')].map((im) => im.currentSrc || im.src).filter((s) => /logo/i.test(s));
    return { scoped: imgs, allLogos: [...new Set(allLogos)] };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/foot-logo.json', JSON.stringify(src, null, 2));
await b.close();
console.log('LOGO_' + JSON.stringify(src.scoped));
