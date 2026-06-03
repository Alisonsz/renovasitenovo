import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
const b = await launch();
const ctx = await b.newContext({
    locale: 'pt-BR',
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
});
const p = await ctx.newPage();
const errs = [];
p.on('console', (m) => { if (m.type() === 'error') errs.push(m.text()); });
await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await p.waitForTimeout(1200);

// Generic test for a track identified by a child selector.
async function testTrack(name, hasButton) {
    const info = await p.evaluate((hasBtn) => {
        const lis = [...document.querySelectorAll('[data-carousel-item]')];
        const li = hasBtn ? lis.find(x => x.querySelector('button')) : lis.find(x => !x.querySelector('button'));
        if (!li) return null;
        const el = li.closest('div[class*="overflow-x-auto"]');
        const r = el.getBoundingClientRect();
        el.setAttribute('data-probe', '1');
        return {
            cx: Math.round(r.left + r.width / 2),
            cy: Math.round(r.top + r.height / 2),
            oneSet: Math.round(el.querySelector('ul').scrollWidth / 3),
            start: Math.round(el.scrollLeft),
            client: el.clientWidth,
        };
    }, hasButton);
    if (!info) { console.log(name, 'NOT FOUND'); return; }

    // perform several touch swipes in the SAME direction and ensure we never get
    // stuck at an edge (scrollLeft must keep changing and stay within [0, 2*oneSet]).
    const positions = [];
    for (let i = 0; i < 8; i++) {
        // swipe left (advance) — finger moves right-to-left
        await p.touchscreen.tap(info.cx, info.cy).catch(() => {});
        await p.evaluate(async ({ cx, cy }) => {
            const el = document.querySelector('[data-probe="1"]');
            // simulate a quick swipe by programmatic touch sequence
            const mk = (type, x) => el.dispatchEvent(new TouchEvent(type, { bubbles: true, cancelable: true, touches: type === 'touchend' ? [] : [new Touch({ identifier: 1, target: el, clientX: x, clientY: cy })] }));
            // fallback: just move scrollLeft like a swipe would, then let wrap() run on scroll
            el.scrollLeft += 220;
            el.dispatchEvent(new Event('scroll'));
            await new Promise(r => setTimeout(r, 30));
        }, { cx: info.cx, cy: info.cy });
        const sl = await p.evaluate(() => document.querySelector('[data-probe="1"]').scrollLeft);
        positions.push(Math.round(sl));
        await p.waitForTimeout(40);
    }

    const within = positions.every(x => x >= -2 && x <= info.oneSet * 2 + 2);
    const everStuckAtZero = positions.filter(x => x <= 1).length;
    const everStuckAtMax = positions.filter(x => x >= info.oneSet * 2 - 1).length;
    console.log(name, 'oneSet=' + info.oneSet, 'positions=' + JSON.stringify(positions));
    console.log('   stays-in-middle-band=' + within, 'stuckAtStart=' + everStuckAtZero, 'stuckAtEnd=' + everStuckAtMax);
    await p.evaluate(() => document.querySelector('[data-probe="1"]')?.removeAttribute('data-probe'));
}

// FEATURES (no button), then PRICING (has button)
await testTrack('FEATURES', false);
await testTrack('PRICING ', true);

console.log('CONSOLE_ERRORS', errs.length);
await b.close();
console.log('PROBE_TOUCH_DONE');
