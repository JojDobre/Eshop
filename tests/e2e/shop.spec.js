// Obchod: nákup s minimálnym množstvom, platba prevodom s QR kódom, mobilné menu. Beží na počítači aj mobile.
import { test, expect } from '@playwright/test';
import { addToCart, seedData, watchErrors } from './helpers.js';

const customer = {
    email: 'jana@e2e.test', phone: '+421900123456', billing_name: 'Jana Nováková',
    billing_street: 'Hlavná 1', billing_city: 'Bratislava', billing_zip: '81101',
};

test.describe('Nákup', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('úvodná stránka a produkt sa zobrazia bez chyby', async ({ page }) => {
        const errors = watchErrors(page);
        const seed = await seedData();
        await page.goto('/');
        await expect(page.locator('main, #content').first()).toBeVisible();
        await page.goto(seed.white);
        await expect(page.getByRole('heading', { level: 1 })).toContainText('E2E vajcia biele');
        await expect(page.locator('[data-minimum-quantity]')).toContainText('10 ks');
        errors.assertNone();
    });

    test('minimum 10 ks sa ráta spolu za druhy, klietka sa neráta', async ({ page }) => {
        const seed = await seedData();
        await addToCart(page, seed.white, 3);
        await addToCart(page, seed.cage, 1);
        await page.goto('/kosik');
        await expect(page.locator('[data-minimum-violations]')).toContainText('Pridajte ešte 7 ks');
        await expect(page.locator('a[href$="/pokladna"]')).toHaveCount(0);

        await addToCart(page, seed.brown, 7);
        await page.goto('/kosik');
        await expect(page.locator('[data-minimum-violations]')).toHaveCount(0);
        await expect(page.locator('a[href$="/pokladna"]').first()).toBeVisible();
    });

    test('objednávka s platbou prevodom ukáže QR kód', async ({ page }) => {
        const seed = await seedData();
        await addToCart(page, seed.white, 10);
        await page.goto('/pokladna');

        for (const [name, value] of Object.entries(customer)) {
            await page.locator(`[name="${name}"]`).first().fill(value);
        }

        await page.locator('[name="shipping_method_id"]').first().check();
        await page.locator('label', { hasText: /prevod/i }).locator('input[name="payment_method_id"]').first().check();
        await page.locator('[name="terms"]').check();
        await page.locator('form[action$="/pokladna"] button[type="submit"]').last().click();

        await expect(page).toHaveURL(/objednavka/);
        const qr = page.getByRole('img', { name: /naskenujte/i });
        await expect(qr).toBeVisible();
        await expect(qr).toHaveAttribute('src', /^data:image\/png;base64,/);
    });
});
