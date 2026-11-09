/*
 * Kontrola prístupnosti (WCAG 2.1 AA) stránok obchodu nástrojom axe-core.
 *
 * Použitie: node scripts/a11y-check.cjs http://127.0.0.1:8000 [/cesta …]
 * Vráti chybový kód, ak nájde vážne alebo kritické problémy.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const base = (process.argv[2] || 'http://127.0.0.1:8000').replace(/\/$/, '');
const pages = process.argv.slice(3).length ? process.argv.slice(3) : ['/', '/kategoria/nasadove-vajcia', '/produkt/japonska-nosna', '/kosik', '/hladat?q=a'];
const axe = fs.readFileSync(path.join(__dirname, '../node_modules/axe-core/axe.min.js'), 'utf8');

(async () => {
    const browser = await chromium.launch();
    let failures = 0;

    for (const width of [1280, 390]) {
        const page = await browser.newPage({ viewport: { width, height: 900 } });

        for (const url of pages) {
            await page.goto(base + url, { waitUntil: 'networkidle' });
            await page.addScriptTag({ content: axe });
            const result = await page.evaluate(() => axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] }));
            const serious = result.violations.filter((v) => ['serious', 'critical'].includes(v.impact));

            console.log(`${width}px ${url}: ${result.violations.length} problémov (${serious.length} vážnych)`);
            for (const violation of result.violations) {
                console.log(`  [${violation.impact}] ${violation.id}: ${violation.help}`);
                for (const node of violation.nodes.slice(0, 3)) {
                    console.log(`      ${node.target.join(' ')}`);
                }
            }
            failures += serious.length;
        }

        await page.close();
    }

    await browser.close();
    process.exit(failures > 0 ? 1 : 0);
})();
