import { chromium } from 'playwright';
async function launch() {
    try { return await chromium.launch({ channel: 'chrome', headless: true }); }
    catch { return await chromium.launch({ headless: true }); }
}
try {
    const b = await launch();
    const p = await (await b.newContext()).newPage();
    await p.setViewportSize({ width: 1440, height: 900 });
    await p.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle', timeout: 20000 }).catch(() => {});
    await p.waitForTimeout(1500);
    const read = () =>
        p.evaluate(() => {
            const track = document.querySelector('ul[style*="translateX"]');
            const tx = track ? track.style.transform : 'none';
            const fa = !!document.querySelector('i.fa-solid, i.fa-brands, i.fa-regular');
            const faVisible = (() => {
                const el = document.querySelector('header i.fa-circle-user');
                if (!el) return 'noicon';
                const r = el.getBoundingClientRect();
                return r.width > 5 ? 'icon-ok' : 'icon-zero';
            })();
            return { tx, fa, faVisible };
        });
    const a = await read();
    await p.waitForTimeout(3800);
    const c = await read();
    await b.close();
    const moved = a.tx !== c.tx;
    console.log(`CARO_moved_${moved}_fa_${a.fa}_${a.faVisible}`);
} catch (e) {
    console.log('CARO_ERR_' + e.message.slice(0, 40).replace(/\s+/g, '_'));
}
