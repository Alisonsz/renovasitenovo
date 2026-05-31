import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2500);

const r = await p.evaluate(() => {
    const out = {};
    // "E" Montserrat normal
    const h1 = document.querySelector('h1');
    const eSpan = h1.querySelector('span span'); // primeiro span aninhado = "E"
    const ecs = getComputedStyle(eSpan);
    out.eText = eSpan.textContent.trim();
    out.eFont = ecs.fontFamily;
    out.eWeight = ecs.fontWeight;
    // botao margens
    const btn = [...document.querySelectorAll('a')].find((a) => /AGENDAR/i.test(a.innerText));
    const bcs = getComputedStyle(btn);
    out.btnM = `${bcs.marginTop}/${bcs.marginBottom}/${bcs.marginLeft}/${bcs.marginRight}`;
    // icone conta cor
    const ic = document.querySelector('header i.fa-circle-user');
    out.iconColor = ic ? getComputedStyle(ic).color : 'noicon';
    out.iconW = ic ? Math.round(ic.getBoundingClientRect().width) : 0;
    // carrossel: cards visiveis totalmente dentro do viewport (sem corte)
    const track = document.querySelector('ul[style*="translateX"]');
    const vp = track.parentElement; // overflow-hidden
    const vr = vp.getBoundingClientRect();
    const cards = [...track.querySelectorAll('li[data-slide]')];
    let inside = 0, partial = 0;
    for (const c of cards) {
        const cr = c.getBoundingClientRect();
        const visible = cr.right > vr.left && cr.left < vr.right;
        if (!visible) continue;
        if (cr.left >= vr.left - 1 && cr.right <= vr.right + 1) inside++;
        else partial++;
    }
    out.cardsInside = inside;
    out.cardsPartial = partial;
    return out;
});

fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/verify2.txt', JSON.stringify(r, null, 2));
await b.close();

const eOk = /Montserrat/.test(r.eFont) && !/Alternates/.test(r.eFont) && r.eWeight === '400' && r.eText === 'E';
const btnOk = r.btnM === '15px/15px/24px/24px';
const iconOk = r.iconColor === 'rgb(255, 255, 255)' && r.iconW > 5;
const clipOk = r.cardsPartial === 0 && r.cardsInside >= 1;
console.log(`E_${eOk}_BTN_${btnOk}_ICON_${iconOk}_CLIP_${clipOk}_inside_${r.cardsInside}_partial_${r.cardsPartial}`);
