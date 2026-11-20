// Spoločné pomôcky pre testy v prehliadači.
import { expect } from '@playwright/test';

export const ADMIN = {
    email: process.env.E2E_ADMIN_EMAIL || 'admin@e2e.test',
    password: process.env.E2E_ADMIN_PASSWORD || 'E2e-heslo-123',
};

/** Pole formulára podľa popisu (povinné pole má na konci „*“, číselné polia sú „spinbutton“). */
export function field(page, label) {
    const name = new RegExp('^' + label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\*?$');

    return page.getByRole('textbox', { name }).or(page.getByRole('spinbutton', { name }));
}

/** Zachytí chyby JavaScriptu na stránke – test na konci overí, že žiadna nenastala. */
export function watchErrors(page) {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    return {
        assertNone: () => expect(errors, 'Chyby JavaScriptu na stránke').toEqual([]),
    };
}

/** Pridá produkt do košíka z jeho stránky. */
export async function addToCart(page, productPath, quantity = 1) {
    await page.goto(productPath);
    await page.locator('#quantity').fill(String(quantity));
    await page.getByRole('button', { name: /do košíka/i }).first().click();
    await page.waitForLoadState('networkidle');
}

/** Vyprázdni košík. */
export async function emptyCart(page) {
    await page.context().clearCookies({ name: /cart|session/i }).catch(() => {});
}

/** Údaje testovacieho obchodu z fixtures/seed.php (uložené pri inštalácii). */
export async function seedData() {
    const fs = await import('node:fs');

    return JSON.parse(fs.readFileSync('storage/e2e/seed.json', 'utf8'));
}
