import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext({ locale: 'pt-BR' });
const p = await ctx.newPage();
p.on('console', (m) => { if (m.type() === 'error') console.log('PAGE_ERR', m.text()); });

// 1) Find a product slug from a category page
await p.goto('http://127.0.0.1:8000/depilacao-feminina', { waitUntil: 'networkidle', timeout: 30000 }).catch(() => {});
await p.waitForTimeout(800);
const productHref = await p.evaluate(() => {
    const a = [...document.querySelectorAll('a[href*="/produto/"]')][0];
    return a ? a.getAttribute('href') : null;
});
console.log('PRODUCT_HREF', productHref);

if (productHref) {
    await p.goto('http://127.0.0.1:8000' + productHref, { waitUntil: 'networkidle' }).catch(() => {});
    await p.waitForTimeout(700);
    // add to cart
    const added = await p.evaluate(() => {
        const btn = [...document.querySelectorAll('button')].find((e) => /adicionar|comprar/i.test(e.innerText));
        if (btn) { btn.click(); return btn.innerText; }
        return null;
    });
    console.log('ADD_BTN', added);
    await p.waitForTimeout(1500);
}

// 2) Go to checkout, screenshot step 1 (email-first)
await p.goto('http://127.0.0.1:8000/checkout', { waitUntil: 'networkidle' }).catch(() => {});
await p.waitForTimeout(1000);
await p.setViewportSize({ width: 1280, height: 900 });
await p.screenshot({ path: '_reference/screenshots/checkout-step1.jpg', type: 'jpeg', quality: 82 });

// fill email + continue
await p.evaluate(() => {
    const email = document.querySelector('input[type="email"]');
    if (email) { email.value = 'cliente@example.com'; email.dispatchEvent(new Event('input', { bubbles: true })); }
});
await p.waitForTimeout(300);
await p.evaluate(() => {
    const btn = [...document.querySelectorAll('button')].find((e) => /continuar/i.test(e.innerText));
    btn && btn.click();
});
await p.waitForTimeout(1500);
await p.screenshot({ path: '_reference/screenshots/checkout-step2.jpg', type: 'jpeg', quality: 82 });

// fill details + go to payment — scope to the checkout form panel (avoid header search)
await p.evaluate(() => {
    const fire = (el, v) => { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); };
    // the panel is the white card; find labels by their text
    const labels = [...document.querySelectorAll('label')];
    const byText = (t) => labels.find((l) => l.textContent.trim().toLowerCase().startsWith(t));
    const nome = byText('nome completo')?.querySelector('input');
    const tel = byText('telefone')?.querySelector('input');
    const cpf = byText('cpf')?.querySelector('input');
    if (nome) fire(nome, 'Cliente Teste');
    if (tel) fire(tel, '11988887777');
    if (cpf) fire(cpf, '12345678909');
});
await p.waitForTimeout(400);
await p.evaluate(() => {
    const btn = [...document.querySelectorAll('button')].find((e) => /pagamento/i.test(e.innerText));
    btn && btn.click();
});
await p.waitForTimeout(1200);
await p.screenshot({ path: '_reference/screenshots/checkout-step3-pix.jpg', type: 'jpeg', quality: 82 });

// switch to credit card
await p.evaluate(() => {
    const btn = [...document.querySelectorAll('button')].find((e) => /cart[aã]o/i.test(e.innerText));
    btn && btn.click();
});
await p.waitForTimeout(800);
await p.screenshot({ path: '_reference/screenshots/checkout-step3-card.jpg', type: 'jpeg', quality: 82 });

await b.close();
console.log('CHECKOUT_SHOTS_DONE');
