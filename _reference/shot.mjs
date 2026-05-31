import { chromium } from 'playwright';

const URL = process.argv[2];
const PREFIX = process.argv[3] || 'shot';
if (!URL) {
    console.error('uso: node _reference/shot.mjs <url> <prefix>');
    process.exit(1);
}

const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'mobile', width: 390, height: 844 },
];

async function launch() {
    try {
        return await chromium.launch({ channel: 'chrome', headless: true });
    } catch {
        return await chromium.launch({ headless: true });
    }
}

async function autoScroll(p) {
    await p.evaluate(async () => {
        await new Promise((res) => {
            let total = 0;
            const dist = 500;
            const t = setInterval(() => {
                window.scrollBy(0, dist);
                total += dist;
                if (total >= document.body.scrollHeight + 1000) {
                    clearInterval(t);
                    res();
                }
            }, 60);
        });
    });
    await p.waitForTimeout(500);
    await p.evaluate(() => window.scrollTo(0, 0));
    await p.waitForTimeout(200);
}

const browser = await launch();
const context = await browser.newContext({ deviceScaleFactor: 1 });
const page = await context.newPage();

for (const vp of viewports) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.goto(URL, { waitUntil: 'networkidle', timeout: 45000 }).catch(() => {});
    await page.waitForTimeout(1200);
    await autoScroll(page);
    await page.screenshot({ path: `_reference/screenshots/${PREFIX}-${vp.name}.png`, fullPage: true });
    console.log('shot', vp.name);
}

await browser.close();
console.log('DONE', PREFIX);
