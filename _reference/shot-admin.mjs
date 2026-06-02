import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR' });
const p = await ctx.newPage();
p.on('console', (m) => { if (m.type() === 'error') console.log('ERR', m.text()); });
await p.setViewportSize({ width: 1366, height: 900 });

// login
await p.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle' });
await p.waitForTimeout(600);
await p.evaluate(() => {
    const set = (sel, v) => { const el = document.querySelector(sel); if (el) { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); } };
    set('input[type="email"]', 'admin@renovalaser.local');
    set('input[type="password"]', 'admin123');
});
await p.waitForTimeout(300);
await p.evaluate(() => { const btn = [...document.querySelectorAll('button')].find((e) => /entrar|acessar|login/i.test(e.innerText)); btn && btn.click(); });
await p.waitForTimeout(2000);
console.log('AFTER_LOGIN_URL', p.url());

const pages = [
    ['/admin', 'admin-dashboard'],
    ['/admin/orders', 'admin-orders'],
    ['/admin/customers', 'admin-customers'],
    ['/admin/reports', 'admin-reports'],
    ['/admin/settings', 'admin-settings'],
    ['/admin/coupons', 'admin-coupons'],
];
for (const [url, name] of pages) {
    await p.goto('http://127.0.0.1:8000' + url, { waitUntil: 'networkidle' }).catch(() => {});
    await p.waitForTimeout(900);
    await p.screenshot({ path: `_reference/screenshots/${name}.jpg`, type: 'jpeg', quality: 80 });
    console.log('SHOT', name, p.url());
}

// order detail — click first "Ver"
await p.goto('http://127.0.0.1:8000/admin/orders', { waitUntil: 'networkidle' });
await p.waitForTimeout(800);
const detailHref = await p.evaluate(() => {
    const a = [...document.querySelectorAll('a')].find((e) => /\/admin\/orders\/\d+$/.test(e.getAttribute('href') || ''));
    return a ? a.getAttribute('href') : null;
});
if (detailHref) {
    await p.goto('http://127.0.0.1:8000' + detailHref, { waitUntil: 'networkidle' });
    await p.waitForTimeout(900);
    await p.screenshot({ path: '_reference/screenshots/admin-order-detail.jpg', type: 'jpeg', quality: 80 });
    console.log('SHOT admin-order-detail', detailHref);
}

await b.close();
console.log('ADMIN_SHOTS_DONE');
