/*
 * Spoločné pomôcky E2E testov nákupu (Playwright).
 */
const { chromium, devices } = require('playwright');
// Adresa obchodu a prístup k databáze z premenných prostredia (nastaví run.cjs).
const B = process.env.E2E_BASE || 'http://127.0.0.1:8125';
const { execSync } = require('child_process');
const sql = (q) => execSync(`mysql ${process.env.DB_HOST ? '-h' + process.env.DB_HOST + ' -P' + (process.env.DB_PORT || 3306) : ''} -u${process.env.DB_USERNAME || 'root'} ${process.env.DB_PASSWORD ? '-p' + process.env.DB_PASSWORD : ''} -N ${process.env.DB_NAME || 'eshop_e2e'} -e "${q}"`).toString().trim();
const admin = { email: process.env.E2E_ADMIN_EMAIL || 'admin@example.test', password: process.env.E2E_ADMIN_PASSWORD || 'Admin-heslo-123' };
const results = [];
function check(name, ok, detail = '') { results.push({ name, ok: !!ok, detail }); console.log((ok ? 'PASS ' : 'FAIL ') + name + (ok ? '' : ' :: ' + detail)); }
async function newPage(browser, opts = {}) {
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, ...opts });
  const page = await ctx.newPage();
  module.exports.lastPage = page;
  page.errors = [];
  page.on('pageerror', e => page.errors.push(e.message));
  page.on('response', r => { if (r.status() >= 500) page.errors.push('HTTP ' + r.status() + ' ' + r.url()); });
  return page;
}
async function dismissCookies(page) {
  const btn = page.getByRole('button', { name: /Prijať všetko|Odmietnuť/ }).first();
  if (await btn.isVisible().catch(() => false)) { await btn.click(); await page.waitForTimeout(200); }
}
async function addToCart(page, url, qty = 1) {
  await page.goto(B + url); await dismissCookies(page);
  const q = page.locator('main input[name=quantity]').first();
  if (await q.count()) await q.fill(String(qty));
  await page.locator('main form button[type=submit]', { hasText: /košíka/i }).first().click();
  await page.waitForTimeout(900);
}
const money = s => parseFloat(String(s).replace(/[^\d,.-]/g, '').replace('.', '').replace(',', '.'));
/** Text obsahu stránky košíka. */
async function cartTotal(page) { await page.goto(B + '/kosik'); return (await page.locator('main').innerText()); }
module.exports = { sql, admin, chromium, devices, B, results, check, newPage, dismissCookies, addToCart, money, cartTotal };
