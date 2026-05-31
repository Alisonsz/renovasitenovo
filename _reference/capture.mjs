import { chromium } from 'playwright';
import fs from 'fs';

const URL = process.argv[2] || 'https://renovalaserdepilacao.com.br/';
const OUT = '_reference';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

async function launch() {
    try {
        return await chromium.launch({ channel: 'chrome', headless: true });
    } catch (e) {
        console.error('chrome channel indisponivel, usando chromium bundled:', e.message);
        return await chromium.launch({ headless: true });
    }
}

async function autoScroll(p) {
    await p.evaluate(async () => {
        await new Promise((res) => {
            let total = 0;
            const dist = 500;
            const timer = setInterval(() => {
                window.scrollBy(0, dist);
                total += dist;
                if (total >= document.body.scrollHeight + 1000) {
                    clearInterval(timer);
                    res();
                }
            }, 80);
        });
    });
    await p.waitForTimeout(700);
    await p.evaluate(() => window.scrollTo(0, 0));
    await p.waitForTimeout(300);
}

const browser = await launch();
const context = await browser.newContext({ deviceScaleFactor: 1 });
const page = await context.newPage();

await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 45000 });
await page.waitForTimeout(3000);
await autoScroll(page);

const data = await page.evaluate(() => {
    const norm = (s) => (s || '').replace(/\s+/g, ' ').trim();
    const bgTally = {}, colorTally = {}, fontTally = {};
    const els = Array.from(document.querySelectorAll('*'));
    for (const el of els) {
        const cs = getComputedStyle(el);
        const bg = cs.backgroundColor;
        if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') bgTally[bg] = (bgTally[bg] || 0) + 1;
        if (cs.color) colorTally[cs.color] = (colorTally[cs.color] || 0) + 1;
        if (cs.fontFamily) fontTally[cs.fontFamily] = (fontTally[cs.fontFamily] || 0) + 1;
    }
    const top = (o, n) => Object.entries(o).sort((a, b) => b[1] - a[1]).slice(0, n);

    const imgs = Array.from(document.querySelectorAll('img')).map((im) => ({
        src: im.currentSrc || im.src,
        alt: im.alt,
        nat: `${im.naturalWidth}x${im.naturalHeight}`,
        disp: `${Math.round(im.getBoundingClientRect().width)}x${Math.round(im.getBoundingClientRect().height)}`,
    })).filter((i) => i.src);

    const bgImgs = [];
    for (const el of els) {
        const bi = getComputedStyle(el).backgroundImage;
        if (bi && bi.includes('url(')) {
            const m = bi.match(/url\(["']?([^"')]+)["']?\)/);
            if (m) bgImgs.push(m[1]);
        }
    }

    const headings = Array.from(document.querySelectorAll('h1,h2,h3,h4')).map((h) => ({
        tag: h.tagName,
        text: norm(h.innerText),
        size: getComputedStyle(h).fontSize,
        weight: getComputedStyle(h).fontWeight,
        color: getComputedStyle(h).color,
        font: getComputedStyle(h).fontFamily,
    })).filter((h) => h.text);

    const buttons = Array.from(document.querySelectorAll('a,button'))
        .filter((b) => norm(b.innerText))
        .slice(0, 80)
        .map((b) => ({
            text: norm(b.innerText),
            href: b.getAttribute('href') || '',
            bg: getComputedStyle(b).backgroundColor,
            color: getComputedStyle(b).color,
            radius: getComputedStyle(b).borderRadius,
        }));

    return {
        bodyFont: getComputedStyle(document.body).fontFamily,
        bodyColor: getComputedStyle(document.body).color,
        bodyBg: getComputedStyle(document.body).backgroundColor,
        topBg: top(bgTally, 18),
        topColor: top(colorTally, 18),
        topFont: top(fontTally, 10),
        images: imgs,
        bgImages: [...new Set(bgImgs)],
        headings,
        buttons,
        fullText: document.body.innerText,
    };
});

fs.writeFileSync(`${OUT}/site-data.json`, JSON.stringify(data, null, 2));
fs.writeFileSync(`${OUT}/home-text.txt`, data.fullText);
console.log('Fonte body:', data.bodyFont);
console.log('Top backgrounds:', JSON.stringify(data.topBg.slice(0, 8)));

for (const vp of viewports) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.reload({ waitUntil: 'domcontentloaded', timeout: 45000 });
    await page.waitForTimeout(2500);
    await autoScroll(page);
    await page.screenshot({ path: `${OUT}/screenshots/live-${vp.name}.png`, fullPage: true });
    console.log('screenshot', vp.name);
}

await browser.close();
console.log('CAPTURE_DONE');
