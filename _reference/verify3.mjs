import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const p = await (await b.newContext()).newPage();
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2000);

const r = await p.evaluate(() => {
    const out = {};
    // 1) botao alinhado com o texto do hero
    const h1 = document.querySelector('main section:first-of-type h1');
    const btn = [...document.querySelectorAll('a')].find((a) => /AGENDAR/i.test(a.innerText));
    out.h1Left = Math.round(h1.getBoundingClientRect().left);
    out.btnLeft = Math.round(btn.getBoundingClientRect().left);
    out.aligned = Math.abs(out.h1Left - out.btnLeft) <= 1;

    // 2) linha verde abaixo do "Aposte em quem e referencia"
    const about = document.querySelector('main section:nth-of-type(2)');
    const line = [...about.querySelectorAll('div')].find((d) => {
        const cs = getComputedStyle(d);
        return parseInt(cs.height) <= 6 && parseInt(cs.width) > 50 && parseInt(cs.width) < 200;
    });
    if (line) {
        const cs = getComputedStyle(line);
        out.lineW = Math.round(line.getBoundingClientRect().width);
        out.lineH = cs.height;
        out.lineColor = cs.backgroundColor;
        out.lineMT = cs.marginTop;
        out.lineMB = cs.marginBottom;
    } else out.lineW = 'NOLINE';

    // 3) setas removidas na secao de precos
    const pricing = [...document.querySelectorAll('main section')].find((s) => /Conheça nossos preços/i.test(s.innerText));
    out.pricingArrows = pricing
        ? pricing.querySelectorAll('.fa-chevron-left, .fa-chevron-right, [aria-label="Anterior"], [aria-label="Próximo"]').length
        : 'NOSEC';
    return out;
});
await b.close();
const okAlign = r.aligned;
const okLine = r.lineW === 114 && r.lineColor === 'rgb(10, 186, 181)' && r.lineMT === '35px' && r.lineMB === '35px' && r.lineH === '3px';
const okArrows = r.pricingArrows === 0;
console.log(`ALIGN_${okAlign}(h${r.h1Left}/b${r.btnLeft})_LINE_${okLine}(w${r.lineW}/${r.lineH}/${r.lineColor}/mt${r.lineMT})_ARROWS_${okArrows}(${r.pricingArrows})`);
