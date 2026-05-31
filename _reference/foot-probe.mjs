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

const r = await p.evaluate(() => {
    const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();
    const pick = (el, props) => {
        if (!el) return null;
        const cs = getComputedStyle(el);
        const o = {};
        for (const pr of props) o[pr] = cs[pr];
        return o;
    };
    // achar elementos por texto
    const all = [...document.querySelectorAll('*')];
    const byText = (t) => all.find((e) => norm(e.innerText) === t && e.children.length === 0);

    // titulo de coluna "Menu"
    const menuTitle = [...document.querySelectorAll('h1,h2,h3,h4,h5,p,span,div')].find((e) => norm(e.innerText) === 'Menu');
    const linkFem = [...document.querySelectorAll('a')].find((a) => /^Depila..o feminina$/i.test(norm(a.innerText)));
    const policy = [...document.querySelectorAll('a')].find((a) => /Pol.tica de Privacidade/i.test(norm(a.innerText)));
    const copy = [...document.querySelectorAll('*')].find((e) => /Todos os direitos reservados/i.test(norm(e.innerText)) && norm(e.innerText).length < 120);
    // social icon link
    const social = [...document.querySelectorAll('a')].find((a) => /instagram\.com/i.test(a.href));
    const socialInner = social ? social.querySelector('i,svg,span') : null;

    // bloco onde estamos
    const ondeH = [...document.querySelectorAll('h1,h2,h3')].find((e) => /onde estamos/i.test(e.innerText));
    const ondeCS = ondeH ? getComputedStyle(ondeH) : null;
    const ondeParent = ondeH ? (ondeH.closest('section') || ondeH.parentElement) : null;
    const ondeAlign = ondeParent ? getComputedStyle(ondeParent).textAlign : null;

    return {
        footerColTitle: pick(menuTitle, ['color', 'fontSize', 'fontWeight', 'fontFamily']),
        footerLink: pick(linkFem, ['color', 'fontSize', 'fontFamily']),
        policyLink: pick(policy, ['color', 'fontSize']),
        copy: { ...pick(copy, ['color', 'fontSize', 'fontStyle', 'fontFamily']), text: copy ? norm(copy.innerText) : null },
        socialLink: pick(social, ['backgroundColor', 'borderRadius', 'width', 'height']),
        socialIconColor: socialInner ? getComputedStyle(socialInner).color : null,
        ondeTitleAlign: ondeCS ? ondeCS.textAlign : null,
        ondeTitleColor: ondeCS ? ondeCS.color : null,
        ondeTitleSize: ondeCS ? ondeCS.fontSize : null,
    };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/foot-probe.json', JSON.stringify(r, null, 2));
await b.close();
console.log('FP_DONE');
