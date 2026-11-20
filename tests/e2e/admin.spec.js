// Administrácia: nástenka, zoznamy, detail objednávky, vzhľad, denný a nočný režim.
import { test, expect } from '@playwright/test';
import { watchErrors } from './helpers.js';

test.describe('Administrácia', () => {
    test('nástenka ukazuje graf, predaj a nezaplatené objednávky', async ({ page }) => {
        const errors = watchErrors(page);
        await page.goto('/admin');
        await expect(page.getByText('Tržby a objednávky')).toBeVisible();
        await expect(page.getByText('Najpredávanejšie produkty')).toBeVisible();
        await expect(page.getByText('Čakajú na platbu')).toBeVisible();
        await expect(page.locator('canvas').first()).toBeVisible();
        errors.assertNone();
    });

    test('prepínač denného a nočného režimu', async ({ page }) => {
        await page.goto('/admin');
        await page.getByRole('button', { name: 'Nočný režim' }).click();
        await expect(page.locator('html')).toHaveClass(/dark/);
        await page.reload();
        await expect(page.locator('html')).toHaveClass(/dark/);
        await page.getByRole('button', { name: 'Denný režim' }).click();
        await expect(page.locator('html')).not.toHaveClass(/dark/);
    });

    test('produkty: záložky filtrujú a menu … sa otvorí pri tlačidle', async ({ page }) => {
        await page.goto('/admin/products');
        await expect(page.locator('.fi-tabs-item', { hasText: 'Všetky' })).toBeVisible();

        const trigger = page.locator('.fi-ta-row .fi-dropdown-trigger button').first();
        await trigger.click();
        const menu = page.locator('.fi-dropdown-panel:visible').first();
        await expect(menu).toBeVisible();
        const button = await trigger.boundingBox();
        const panel = await menu.boundingBox();
        expect(Math.abs(panel.y - (button.y + button.height)), 'Menu je ďaleko od tlačidla').toBeLessThan(60);
        await expect(menu.getByText('Upraviť')).toBeVisible();

        await page.keyboard.press('Escape');
        await page.locator('.fi-tabs-item', { hasText: 'Skryté' }).click();
        await expect(page.locator('.fi-ta-row .fi-badge', { hasText: 'V obchode' })).toHaveCount(0);
    });

    test('každý hlavný zoznam sa načíta bez chyby', async ({ page }) => {
        const errors = watchErrors(page);

        for (const url of ['/admin/orders', '/admin/categories', '/admin/customers', '/admin/form-submissions', '/admin/themes', '/admin/settings/unpaid-orders']) {
            const response = await page.goto(url);
            expect(response.status(), url).toBeLessThan(400);
            await expect(page.locator('.fi-header-heading, .admin-theme-hero, .fi-page').first()).toBeVisible();
        }

        errors.assertNone();
    });

    test('prispôsobenie šablóny: zbalené menu, sekcie a náhľad zariadení', async ({ page }) => {
        const errors = watchErrors(page);
        await page.goto('/admin/themes');
        await page.locator('.admin-theme-hero').getByRole('link', { name: 'Prispôsobiť' }).click();
        await expect(page.locator('.admin-customizer')).toBeVisible();

        // Hlavné menu sa na počítači zbalí, aby mal náhľad viac miesta.
        await expect(page.locator('.fi-sidebar-item-label:visible')).toHaveCount(0);

        await page.locator('.admin-customizer-nav-item').first().click();
        await expect(page.locator('.admin-customizer-group-title:visible')).toBeVisible();
        await page.getByRole('button', { name: 'Všetky sekcie' }).click();
        await expect(page.locator('.admin-customizer-nav')).toBeVisible();

        await page.getByRole('button', { name: 'Mobil' }).click();
        await expect(page.locator('.admin-customizer-frame')).toHaveAttribute('data-device', 'mobile');
        await expect(page.frameLocator('.admin-customizer-frame iframe').locator('body')).toBeVisible();
        errors.assertNone();
    });
});
