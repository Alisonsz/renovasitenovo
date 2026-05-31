import { chromium } from 'playwright';
import fs from 'fs';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext();
const p = await ctx.newPage();
const results = [];
const check = (name, cond, got) => results.push(`${cond ? 'PASS' : 'FAIL'} ${name} = ${got}`);

// DESKTOP
await p.setViewportSize({ width: 1440, height: 900 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(2500);

const d = await p.evaluate(() => {
    const cs = (el) => (el ? getComputedStyle(el) : null);
    const video = document.querySelector('section video');
    const overlay = [...document.querySelectorAll('section div')].find(
        (e) => getComputedStyle(e).mixBlendMode === 'color',
    );
    const hero = document.querySelector('section');
    const h1 = document.querySelector('h1');
    const exc = h1?.querySelector('span'); // "Excelência ..."
    const em = exc?.querySelector('span'); // "em"
    const dep = h1?.querySelectorAll('span')[2] || h1?.children[1]; // "depilação a laser"
    const menu = document.querySelector('header nav a');
    const accountOr = [...document.querySelectorAll('header nav a span span span')].find(
        (e) => e.textContent.trim() === 'ou',
    );
    // feature cards
    const cards = [...document.querySelectorAll('ul li')].filter((li) =>
        /Atendimento|Resultados|Tecnologia|Pode ser|Ponteira/.test(li.innerText),
    );
    const card = cards[0];
    const cardTitle = card?.querySelector('p:nth-of-type(1)');
    const cardDesc = card?.querySelector('p:nth-of-type(2)');
    const track = card?.parentElement;
    return {
        videoTag: !!video,
        videoPlaying: video ? video.readyState >= 2 : false,
        blend: overlay ? getComputedStyle(overlay).mixBlendMode : 'none',
        opacity: overlay ? getComputedStyle(overlay).opacity : 'n/a',
        heroMinH: hero ? getComputedStyle(hero).minHeight : 'n/a',
        excFont: exc ? getComputedStyle(exc).fontFamily : 'n/a',
        excSize: exc ? getComputedStyle(exc).fontSize : 'n/a',
        emSize: em ? getComputedStyle(em).fontSize : 'n/a',
        depSize: dep ? getComputedStyle(dep).fontSize : 'n/a',
        menuFont: menu ? getComputedStyle(menu).fontFamily : 'n/a',
        menuSize: menu ? getComputedStyle(menu).fontSize : 'n/a',
        menuWeight: menu ? getComputedStyle(menu).fontWeight : 'n/a',
        orColor: accountOr ? getComputedStyle(accountOr).color : 'n/a',
        cardCount: cards.length,
        cardRadius: card ? getComputedStyle(card).borderTopLeftRadius : 'n/a',
        cardShadow: card ? getComputedStyle(card).boxShadow : 'n/a',
        gap: track ? getComputedStyle(track).columnGap || getComputedStyle(track).gap : 'n/a',
        titleFont: cardTitle ? getComputedStyle(cardTitle).fontFamily : 'n/a',
        titleSize: cardTitle ? getComputedStyle(cardTitle).fontSize : 'n/a',
        descFont: cardDesc ? getComputedStyle(cardDesc).fontFamily : 'n/a',
        descSize: cardDesc ? getComputedStyle(cardDesc).fontSize : 'n/a',
        cardWidth: card ? Math.round(card.getBoundingClientRect().width) : 0,
    };
});

check('video presente', d.videoTag, d.videoTag);
check('overlay blend color', d.blend === 'color', d.blend);
check('overlay opacidade 0.48', d.opacity === '0.48', d.opacity);
check('hero min-h 700px (desktop)', d.heroMinH === '700px', d.heroMinH);
check('Excelencia Montserrat Alternates', /Montserrat Alternates/.test(d.excFont), d.excFont);
check('Excelencia 80px', d.excSize === '80px', d.excSize);
check('em 40px (0.5em)', d.emSize === '40px', d.emSize);
check('depilacao 62px', d.depSize === '62px', d.depSize);
check('menu Source Sans 3', /Source Sans 3/.test(d.menuFont), d.menuFont);
check('menu 17px', d.menuSize === '17px', d.menuSize);
check('menu peso 500', d.menuWeight === '500', d.menuWeight);
check('"ou" cor #4A4A4A', d.orColor === 'rgb(74, 74, 74)', d.orColor);
check('5 cards de destaque', d.cardCount === 5, d.cardCount);
check('card radius 20px', d.cardRadius === '20px', d.cardRadius);
check('card shadow rgba(0,0,0,0.5)', /rgba\(0, 0, 0, 0\.5\)/.test(d.cardShadow), d.cardShadow);
check('gap 15px', d.gap === '15px', d.gap);
check('titulo Poppins 15px', /Poppins/.test(d.titleFont) && d.titleSize === '15px', `${d.titleFont} ${d.titleSize}`);
check('descricao Montserrat 15px', /Montserrat/.test(d.descFont) && d.descSize === '15px', `${d.descFont} ${d.descSize}`);
check('desktop ~4 cards visiveis', d.cardWidth > 250 && d.cardWidth < 320, `card ${d.cardWidth}px`);

// MOBILE: 1 card visivel + hero 100vh
await p.setViewportSize({ width: 390, height: 844 });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
await p.waitForTimeout(1500);
const m = await p.evaluate(() => {
    const hero = document.querySelector('section');
    const cards = [...document.querySelectorAll('ul li')].filter((li) =>
        /Atendimento|Resultados|Tecnologia|Pode ser|Ponteira/.test(li.innerText),
    );
    return {
        heroMinH: hero ? getComputedStyle(hero).minHeight : 'n/a',
        cardWidth: cards[0] ? Math.round(cards[0].getBoundingClientRect().width) : 0,
        viewport: window.innerWidth,
    };
});
check('mobile hero min-h 100vh', /(844px|100vh)/.test(m.heroMinH), m.heroMinH);
check('mobile 1 card (largura ~viewport)', m.cardWidth > m.viewport * 0.7, `card ${m.cardWidth} / vw ${m.viewport}`);

await b.close();
const passCount = results.filter((r) => r.startsWith('PASS')).length;
fs.writeFileSync('C:/Users/aliso/OneDrive/Documentos/Codex/renovasitenovo/_reference/verify.txt', results.join('\n'));
// indices (1-based) das falhas, como token curto
const failIdx = results.map((r, i) => (r.startsWith('FAIL') ? i + 1 : null)).filter(Boolean);
const failNames = results
    .filter((r) => r.startsWith('FAIL'))
    .map((r) => r.slice(5).split(' = ')[0].replace(/[^A-Za-z0-9]/g, ''))
    .join('.');
console.log(`FIDX${failIdx.join('-')}X${failNames}`);
