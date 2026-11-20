// Príprava: (voliteľne) inštalácia čistého obchodu, testovacie dáta a prihlásenie správcu.
import { test as setup, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { ADMIN } from '../helpers.js';

const APP = process.env.E2E_APP || '.';

setup('inštalácia, testovacie dáta a prihlásenie', async ({ page }) => {
    setup.setTimeout(180_000);
    fs.mkdirSync('storage/e2e', { recursive: true });

    await page.goto('/');

    // Čistý obchod: prejde webovým inštalátorom s ukážkovými dátami (iba keď je to výslovne povolené).
    if (page.url().includes('/install')) {
        expect(process.env.E2E_INSTALL, 'Obchod nie je nainštalovaný – spustite s E2E_INSTALL=1').toBe('1');

        await page.locator('select[name="locale"]').selectOption('sk').catch(() => {});
        await Promise.all([page.waitForURL(/install/), page.locator('form button[type="submit"]').first().click()]);
        await page.goto('/install/database');
        await page.fill('[name="host"]', process.env.E2E_DB_HOST || '127.0.0.1');
        await page.fill('[name="port"]', process.env.E2E_DB_PORT || '3306');
        await page.fill('[name="database"]', process.env.E2E_DB_NAME || 'eshop_e2e');
        await page.fill('[name="username"]', process.env.E2E_DB_USERNAME || 'root');
        await page.fill('[name="password"]', process.env.E2E_DB_PASSWORD || '');
        await Promise.all([page.waitForURL(/install\/shop/), page.locator('form button[type="submit"]').last().click()]);
        await page.fill('[name="shop_name"]', 'E2E obchod');
        await page.fill('[name="contact_email"]', 'obchod@e2e.test');
        await page.fill('[name="admin_name"]', 'Správca');
        await page.fill('[name="admin_email"]', ADMIN.email);
        await page.fill('[name="admin_password"]', ADMIN.password);
        await page.fill('[name="admin_password_confirmation"]', ADMIN.password);
        await page.locator('[name="demo_data"]').check();
        await Promise.all([page.waitForURL(/install\/done/, { timeout: 150_000 }), page.locator('form button[type="submit"]').last().click()]);
    }

    // Testovacie dáta (minimum v kategórii, IBAN pre QR platbu…).
    const output = execFileSync('php', [path.join(APP, 'artisan'), 'tinker', `--execute=require ${JSON.stringify(path.resolve('tests/e2e/fixtures/seed.php'))};`]).toString();
    const json = output.trim().split('\n').reverse().find((line) => line.startsWith('{'));
    fs.writeFileSync('storage/e2e/seed.json', json);
    execFileSync('php', [path.join(APP, 'artisan'), 'optimize:clear']);

    // Prihlásenie správcu raz pre všetky testy (administrácia obmedzuje počet prihlásení za minútu).
    await page.goto('/admin/login');
    await page.fill('input[type=email]', ADMIN.email);
    await page.fill('input[type=password]', ADMIN.password);
    await page.getByRole('button', { name: /prihlásiť/i }).click();
    await page.waitForURL(/\/admin\/?$/);
    await page.context().storageState({ path: 'storage/e2e/admin.json' });
});
