import { chromium } from 'playwright';

const LOCAL = 'http://127.0.0.1:8000/';
const DIR = '_reference/screenshots';

async function launch() {
    try {
        return await chromium.launch({ channel: 'chrome', headless: true });
    } catch {
        return await chromium.launch({ headless: true });
    }
}

const browser = await launch();
const ctx = await browser.newContext({ deviceScaleFactor: 1 });
const page = await ctx.newPage();

// --- Desktop: abrir modal de precos ---
await page.setViewportSize({ width: 1440, height: 900 });
await page.goto(LOCAL, { waitUntil: 'networkidle' });
await page.waitForTimeout(800);

const waLinks = await page.$$eval('a[href*="wa.me"]', (els) => els.map((e) => e.getAttribute('href')));
const social = await page.$$eval('a[href*="instagram"],a[href*="tiktok"]', (els) => els.map((e) => e.href));
console.log('wa.me CTAs:', waLinks.length);
console.log('redes:', social);

await page.click('text=Ver combos');
await page.waitForTimeout(500);
const modalVisible = await page.isVisible('[role="dialog"]');
console.log('modal aberto:', modalVisible);
await page.screenshot({ path: `${DIR}/state-modal-desktop.png`, fullPage: false });
await page.keyboard.press('Escape').catch(() => {});

// --- Mobile: abrir menu ---
await page.setViewportSize({ width: 390, height: 844 });
await page.goto(LOCAL, { waitUntil: 'networkidle' });
await page.waitForTimeout(800);
await page.click('[aria-label="Abrir menu"]');
await page.waitForTimeout(500);
const menuVisible = await page.isVisible('text=Depilação Feminina');
console.log('menu mobile aberto:', menuVisible);
await page.screenshot({ path: `${DIR}/state-menu-mobile.png`, fullPage: false });

await browser.close();
console.log('INTERACTIONS_DONE');
