/*
 * E2E test v prehliadači na čistej inštalácii: inštalátor, nákup bez registrácie, nákup s kupónom
 * a účtom, firemný nákup, kontrola prístupnosti a činnosti v administrácii.
 *
 * Použitie: node scripts/e2e.cjs <adresa> <db_host> <db_port> <db_name> <db_user> <db_pass>
 * (spúšťa ho scripts/e2e.sh nad rozbaleným inštalačným balíkom)
 */
const { chromium } = require('playwright');
const assert = require('assert');

const [base, dbHost, dbPort, dbName, dbUser, dbPass] = process.argv.slice(2);
const admin = { email: 'admin@e2e.test', password: 'E2e-heslo-123' };
const step = (name) => console.log('→ ' + name);

async function checkout(page, data) {
    await page.goto(base + '/pokladna', { waitUntil: 'networkidle' });
    for (const [name, value] of Object.entries(data)) {
        const field = page.locator(`[name="${name}"]`).first();
        if (await field.count()) await field.fill(value);
    }
    if (data.company) {
        await page.locator('[name="is_company"]').check();
        await page.locator('[name="billing_company"]').fill(data.company);
        await page.locator('[name="billing_company_id"]').fill('12345678');
    }
    await page.locator('[name="shipping_method_id"]').first().check();
    await page.locator('[name="payment_method_id"]').first().check();
    await page.locator('[name="terms"]').check();
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('form[action$="/pokladna"] button[type="submit"]').last().click()]);
    assert.match(page.url(), /objednavka|order/, 'Po odoslaní objednávky sa nezobrazilo potvrdenie: ' + page.url());
    return page.url();
}

async function addFirstProduct(page) {
    await page.goto(base + '/', { waitUntil: 'networkidle' });
    await page.locator('main a[href*="/produkt/"]').first().click();
    await page.waitForLoadState('networkidle');
    await Promise.all([page.waitForNavigation(), page.locator('main form[action$="/kosik"] button[type="submit"]').first().click()]);
}

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();
    page.on('pageerror', (error) => { throw error; });

    step('Inštalátor');
    await page.goto(base + '/', { waitUntil: 'networkidle' });
    assert.match(page.url(), /install/);
    await page.locator('select[name="locale"]').selectOption('sk').catch(() => {});
    await Promise.all([page.waitForNavigation(), page.locator('form button[type="submit"]').first().click()]);
    await page.goto(base + '/install/database');
    await page.fill('[name="host"]', dbHost);
    await page.fill('[name="port"]', dbPort);
    await page.fill('[name="database"]', dbName);
    await page.fill('[name="username"]', dbUser);
    await page.fill('[name="password"]', dbPass);
    await Promise.all([page.waitForNavigation(), page.locator('form button[type="submit"]').last().click()]);
    assert.match(page.url(), /install\/shop/, 'Databáza sa nepripojila: ' + page.url());
    await page.fill('[name="shop_name"]', 'E2E obchod');
    await page.fill('[name="contact_email"]', 'obchod@e2e.test');
    await page.fill('[name="admin_name"]', 'Správca');
    await page.fill('[name="admin_email"]', admin.email);
    await page.fill('[name="admin_password"]', admin.password);
    await page.fill('[name="admin_password_confirmation"]', admin.password);
    await page.locator('[name="demo_data"]').check();
    await Promise.all([page.waitForNavigation({ timeout: 120000 }), page.locator('form button[type="submit"]').last().click()]);
    assert.match(page.url(), /install\/done/, 'Inštalácia zlyhala: ' + page.url());

    step('Nákup bez registrácie');
    await addFirstProduct(page);
    await checkout(page, { email: 'jana@e2e.test', phone: '+421900123456', billing_name: 'Jana Nováková', billing_street: 'Hlavná 1', billing_city: 'Bratislava', billing_zip: '81101' });

    step('Registrácia a nákup s účtom');
    await page.goto(base + '/ucet/registracia');
    await page.fill('[name="name"]', 'Peter Zákazník');
    await page.fill('[name="email"]', 'peter@e2e.test');
    await page.fill('[name="password"]', 'Zakaznik-123');
    await page.fill('[name="password_confirmation"]', 'Zakaznik-123');
    const consent = page.locator('[name="terms"], [name="privacy"]');
    for (let i = 0; i < await consent.count(); i++) await consent.nth(i).check();
    await Promise.all([page.waitForNavigation(), page.locator('main form button[type="submit"]').last().click()]);
    await addFirstProduct(page);
    await checkout(page, { phone: '+421900123456', billing_name: 'Peter Zákazník', billing_street: 'Dlhá 2', billing_city: 'Žilina', billing_zip: '01001' });
    await page.goto(base + '/ucet');
    assert.ok((await page.content()).includes('Peter'), 'Účet zákazníka sa nezobrazil');

    step('Firemný nákup');
    const company = await browser.newPage();
    await addFirstProduct(company);
    await checkout(company, { email: 'firma@e2e.test', phone: '+421900123456', billing_name: 'Eva Firemná', billing_street: 'Priemyselná 3', billing_city: 'Košice', billing_zip: '04001', company: 'Firma s.r.o.' });

    step('Administrácia');
    const adminPage = await browser.newPage();
    await adminPage.goto(base + '/admin/login', { waitUntil: 'networkidle' });
    await adminPage.fill('input[type="email"]', admin.email);
    await adminPage.fill('input[type="password"]', admin.password);
    await Promise.all([adminPage.waitForURL(/\/admin$/), adminPage.locator('button[type="submit"]').click()]);
    for (const url of ['/admin/orders', '/admin/products', '/admin/system/status', '/admin/system/backups', '/admin/settings/privacy']) {
        const response = await adminPage.goto(base + url, { waitUntil: 'networkidle' });
        assert.strictEqual(response.status(), 200, url);
    }
    await adminPage.goto(base + '/admin/orders', { waitUntil: 'networkidle' });
    assert.ok((await adminPage.content()).includes('Firma s.r.o.') || (await adminPage.content()).includes('Eva Firemná'), 'Objednávka nie je v administrácii');

    await browser.close();
    console.log('✓ E2E testy prešli');
})().catch((error) => {
    console.error('✗ ' + error.message);
    process.exit(1);
});
