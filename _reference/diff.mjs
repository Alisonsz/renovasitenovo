import { chromium } from 'playwright';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import fs from 'fs';

const LIVE = 'https://renovalaserdepilacao.com.br/';
const LOCAL = 'http://127.0.0.1:8000/';
const DIR = '_reference/screenshots';

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'mobile', width: 390, height: 844 },
];

async function launch() {
    try {
        return await chromium.launch({ channel: 'chrome', headless: true });
    } catch {
        return await chromium.launch({ headless: true });
    }
}

async function shoot(page, url, vp, file) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 }).catch(() => {});
    await page.waitForTimeout(1500);
    // Esconde widgets de chat de terceiros para nao gerar falso-positivo
    await page.addStyleTag({
        content:
            '[class*="whatsapp"],[id*="whatsapp"],iframe[src*="wa."],.elementor-popup-modal{display:none!important}',
    }).catch(() => {});
    await page.screenshot({ path: file, fullPage: false });
}

const browser = await launch();
const page = await (await browser.newContext({ deviceScaleFactor: 1 })).newPage();

for (const vp of viewports) {
    const liveFile = `${DIR}/fold-live-${vp.name}.png`;
    const localFile = `${DIR}/fold-local-${vp.name}.png`;
    await shoot(page, LIVE, vp, liveFile);
    await shoot(page, LOCAL, vp, localFile);

    const a = PNG.sync.read(fs.readFileSync(liveFile));
    const b = PNG.sync.read(fs.readFileSync(localFile));
    const width = Math.min(a.width, b.width);
    const height = Math.min(a.height, b.height);
    const diff = new PNG({ width, height });

    // recorta ambos para dimensao comum
    const crop = (src) => {
        const out = new PNG({ width, height });
        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const si = (src.width * y + x) << 2;
                const di = (width * y + x) << 2;
                out.data[di] = src.data[si];
                out.data[di + 1] = src.data[si + 1];
                out.data[di + 2] = src.data[si + 2];
                out.data[di + 3] = src.data[si + 3];
            }
        }
        return out;
    };
    const ca = crop(a);
    const cb = crop(b);
    const mismatch = pixelmatch(ca.data, cb.data, diff.data, width, height, { threshold: 0.15 });
    fs.writeFileSync(`${DIR}/fold-diff-${vp.name}.png`, PNG.sync.write(diff));
    const pct = ((mismatch / (width * height)) * 100).toFixed(2);
    console.log(`${vp.name}: ${mismatch} px diferentes de ${width * height} (${pct}%)`);
}

await browser.close();
console.log('DIFF_DONE');
