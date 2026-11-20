// Vzhľad: snímky úvodnej stránky každej šablóny a hlavných stránok administrácie sa porovnajú s uloženými.
// Nové snímky po zámernej zmene vzhľadu: npm run e2e:snimky (prepíše uložené snímky).
import { test, expect } from '@playwright/test';

const THEMES = ['default', 'farma', 'fresh', 'dielna', 'dvor', 'esencia', 'butik', 'kapitola', 'lupen', 'studio', 'starter'];

// Premenlivé časti (dátumy, odpočty, karusely) sa pri porovnaní zakryjú.
const MASK = ['time', '[data-countdown]', '.swiper', 'iframe', 'video'];

test.describe('Šablóny', () => {
    for (const theme of THEMES) {
        test(`úvodná stránka – ${theme}`, async ({ page }) => {
            const response = await page.goto(`/?theme_preview=${theme}`);
            test.skip(response.status() >= 400, `Šablóna ${theme} nie je nainštalovaná`);
            await page.waitForLoadState('networkidle');
            await page.evaluate(() => document.fonts.ready);
            await expect(page).toHaveScreenshot(`theme-${theme}.png`, {
                fullPage: true,
                mask: MASK.map((selector) => page.locator(selector)),
            });
        });
    }

    test.afterAll(async ({ browser }) => {
        const page = await browser.newPage({ storageState: 'storage/e2e/admin.json' });
        await page.goto('/?theme_preview=0');
        await page.close();
    });
});

test.describe('Administrácia', () => {
    for (const [name, url] of [['produkty', '/admin/products'], ['objednávky', '/admin/orders'], ['šablóny', '/admin/themes']]) {
        test(name, async ({ page }) => {
            await page.goto(url);
            await page.waitForLoadState('networkidle');
            await expect(page).toHaveScreenshot(`admin-${url.split('/').pop()}.png`, {
                mask: [page.locator('.fi-ta-cell-created-at, .fi-ta-cell-updated-at, time, .fi-ta-image img, .admin-theme-shot img')],
            });
        });
    }
});
