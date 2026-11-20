// Prístupnosť (WCAG 2.1 AA) nástrojom axe-core: obchod a prihlásenie do administrácie bez vážnych chýb.
import { test, expect } from '@playwright/test';
import { createRequire } from 'node:module';
import { seedData } from './helpers.js';

const axePath = createRequire(import.meta.url).resolve('axe-core/axe.min.js');

async function audit(page) {
    await page.addScriptTag({ path: axePath });

    return page.evaluate(async () => {
        const result = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa'] } });

        return result.violations
            .filter((violation) => ['serious', 'critical'].includes(violation.impact))
            .map((violation) => `${violation.id}: ${violation.help} (${violation.nodes.slice(0, 3).map((node) => node.target.join(' ')).join(', ')})`);
    });
}

test.describe('Prístupnosť', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    for (const name of ['úvod', 'produkt', 'košík', 'prihlásenie do administrácie']) {
        test(name, async ({ page }) => {
            const seed = await seedData();
            const url = { 'úvod': '/', 'produkt': seed.white, 'košík': '/kosik', 'prihlásenie do administrácie': '/admin/login' }[name];
            await page.goto(url);
            await page.waitForLoadState('networkidle');
            expect(await audit(page)).toEqual([]);
        });
    }
});
