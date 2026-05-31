import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext({ locale: 'pt-BR' })).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
await p.waitForTimeout(2000);
await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await p.waitForTimeout(1000);

const r = await p.evaluate(() => {
    const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();
    const footer = document.querySelector('footer');
    const fcs = getComputedStyle(footer);
    const colTitle = [...footer.querySelectorAll('h4')].find((e) => norm(e.innerText) === 'Menu');
    const colLink = [...footer.querySelectorAll('a')].find((a) => /Depila..o feminina/i.test(norm(a.innerText)));
    const policy = [...footer.querySelectorAll('a')].find((a) => /Pol.tica de Privacidade/i.test(norm(a.innerText)));
    const social = footer.querySelector('a[aria-label="Instagram"]');
    const socialIcon = social ? social.querySelector('i') : null;
    const logo = footer.querySelector('img');
    const copy = [...footer.querySelectorAll('p')].find((e) => /direitos reservados/i.test(norm(e.innerText)));

    // onde estamos
    const ondeH = [...document.querySelectorAll('h2')].find((e) => /Onde estamos/i.test(e.innerText));
    const ondeSec = ondeH ? ondeH.closest('section') : null;
    const ondeIcons = ondeSec ? ondeSec.querySelectorAll('i').length : -1;

    const g = (el, pr) => (el ? getComputedStyle(el)[pr] : 'null');
    return {
        footerBgImg: fcs.backgroundImage.slice(0, 60),
        colTitleColor: g(colTitle, 'color'),
        colTitleSize: g(colTitle, 'fontSize'),
        colLinkColor: g(colLink, 'color'),
        colLinkSize: g(colLink, 'fontSize'),
        policyColor: g(policy, 'color'),
        socialBg: g(social, 'backgroundColor'),
        socialRadius: g(social, 'borderRadius'),
        socialIconColor: g(socialIcon, 'color'),
        logoH: logo ? Math.round(logo.getBoundingClientRect().height) : 0,
        copyColor: g(copy, 'color'),
        ondeTitleAlign: g(ondeH, 'textAlign'),
        ondeTitleColor: g(ondeH, 'color'),
        ondeIcons,
    };
});
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/verify-foot.json', JSON.stringify(r, null, 2));
await b.close();
console.log('VF_DONE');
