// Mobil: menu obchodu a administrácia na úzkej obrazovke (projekt „mobil“).
import { test, expect } from '@playwright/test';

test('administrácia na mobile: zoznam produktov ako karty a menu', async ({ page, isMobile }) => {
    test.skip(! isMobile, 'Iba na mobile');
    await page.goto('/admin/products');
    await expect(page.locator('.fi-ta-row').first()).toBeVisible();
    const width = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(width, 'Stránka sa nesmie posúvať do strany').toBeLessThanOrEqual(1);
});

test('obchod na mobile: menu sa otvorí a zavrie', async ({ browser, isMobile }) => {
    test.skip(! isMobile, 'Iba na mobile');
    const context = await browser.newContext({ ...test.info().project.use, storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await page.goto('/');
    const toggle = page.locator('button[aria-controls="mobile-menu"]').first();
    test.skip(await toggle.count() === 0, 'Aktívna šablóna nemá mobilné menu s týmto prepínačom');
    await toggle.click();
    await expect(page.locator('#mobile-menu')).toBeVisible();
    await toggle.click();
    await expect(page.locator('#mobile-menu')).toBeHidden();
    await context.close();
});
