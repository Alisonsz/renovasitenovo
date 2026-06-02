import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR' });
const p = await ctx.newPage();
p.on('console', (m) => { if (m.type() === 'error') console.log('ERR', m.text()); });
await p.setViewportSize({ width: 1366, height: 950 });

await p.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle' });
await p.waitForTimeout(500);
await p.evaluate(() => {
    const set = (s, v) => { const el = document.querySelector(s); if (el) { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); } };
    set('input[type="email"]', 'admin@renovalaser.local');
    set('input[type="password"]', 'admin123');
});
await p.evaluate(() => { const btn = [...document.querySelectorAll('button')].find((e) => /entrar|acessar|login/i.test(e.innerText)); btn && btn.click(); });
await p.waitForTimeout(2000);

// Day view
await p.goto('http://127.0.0.1:8000/admin/appointments?view=day', { waitUntil: 'networkidle' });
await p.waitForTimeout(1000);
await p.screenshot({ path: '_reference/screenshots/agenda-day.jpg', type: 'jpeg', quality: 80 });
console.log('SHOT agenda-day');

// Week view
await p.goto('http://127.0.0.1:8000/admin/appointments?view=week', { waitUntil: 'networkidle' });
await p.waitForTimeout(1000);
await p.screenshot({ path: '_reference/screenshots/agenda-week.jpg', type: 'jpeg', quality: 80 });
console.log('SHOT agenda-week');

// Customer profile (CRM)
const custHref = await p.evaluate(async () => {
    await fetch('/admin/customers');
    return null;
});
await p.goto('http://127.0.0.1:8000/admin/customers', { waitUntil: 'networkidle' });
await p.waitForTimeout(700);
const firstCust = await p.evaluate(() => {
    const a = [...document.querySelectorAll('a')].find((e) => /\/admin\/customers\/\d+$/.test(e.getAttribute('href') || ''));
    return a ? a.getAttribute('href') : null;
});
if (firstCust) {
    await p.goto('http://127.0.0.1:8000' + firstCust, { waitUntil: 'networkidle' });
    await p.waitForTimeout(800);
    await p.screenshot({ path: '_reference/screenshots/crm-profile.jpg', type: 'jpeg', quality: 80 });
    console.log('SHOT crm-profile', firstCust);
}

// New appointment form
await p.goto('http://127.0.0.1:8000/admin/appointments/create', { waitUntil: 'networkidle' });
await p.waitForTimeout(700);
await p.screenshot({ path: '_reference/screenshots/agenda-form.jpg', type: 'jpeg', quality: 80 });
console.log('SHOT agenda-form');

await b.close();
console.log('AGENDA_SHOTS_DONE');
