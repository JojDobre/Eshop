/*
 * Nákup v angličtine a v českých korunách.
 */
const L = require('./lib.cjs'); const { B, check } = L;
module.exports = async (b) => { const p = await L.newPage(b);
  await p.goto(B + '/en'); await L.dismissCookies(p);
  const t0 = await p.locator('body').innerText();
  check('I1 anglická verzia obchodu sa zobrazí', /Cart|Search|Add to cart|Products/i.test(t0), t0.slice(0, 200));
  // prepnutie meny na CZK
  const tok = await p.locator('input[name=_token]').first().inputValue();
  const rr = await p.request.post(B + '/mena', { form: { currency: 'CZK', _token: tok } });
  console.log('currency switch', rr.status());
  await p.goto(B + '/en');
  const prodLink = p.locator('a[href*="/en/"]').filter({ hasText: /Colombia|Kolumbia/i }).first();
  const href = await prodLink.getAttribute('href').catch(() => null);
  console.log('product link', href);
  await p.goto(href || B + '/en');
  const pt = await p.locator('#content').innerText();
  check('I1 cena v CZK', /Kč|CZK/.test(pt), pt.slice(0, 300));
  await p.locator('#content form button[type=submit]').filter({ hasText: /cart/i }).first().click(); await p.waitForTimeout(1000);
  const cartUrl = (await p.locator('header a[href*="/en/"]').filter({ has: p.locator('svg') }).last().getAttribute('href').catch(() => null));
  await p.goto(B + '/en/cart').catch(() => {});
  let ct = await p.locator('#content').innerText();
  if (!/Kolumbia|Colombia/.test(ct)) { await p.goto(B + '/en/kosik'); ct = await p.locator('#content').innerText(); }
  check('I1 košík v angličtine a CZK', /Kč|CZK/.test(ct) && /(Cart|Subtotal)/i.test(ct), p.url() + ' ' + ct.slice(0, 300));
  const checkoutLink = p.locator('#content a', { hasText: /Checkout/i }).first();
  await checkoutLink.click(); await p.waitForLoadState('load');
  await p.fill('input[name=email]', 'en@example.test'); await p.fill('input[name=phone]', '+420 600 000 000');
  await p.fill('input[name=billing_name]', 'John Smith'); await p.fill('input[name=billing_street]', 'Main 1');
  await p.fill('input[name=billing_zip]', '11000'); await p.fill('input[name=billing_city]', 'Praha');
  await p.selectOption('select[name=billing_country]', 'CZ').catch(() => {});
  await p.check('input[name=shipping_method_id][value="2"]'); await p.check('input[name=payment_method_id][value="1"]'); await p.check('input[name=terms]');
  await p.evaluate(() => { document.querySelector('form[action*="pokladna"], form[action*="checkout"]').noValidate = true; });
  await Promise.all([p.waitForLoadState('load'), p.locator('#content form button[type=submit]').last().click()]);
  const ot = await p.locator('#content').innerText();
  check('I1 objednávka v EN a CZK vytvorená', /order|objednavka/i.test(p.url()) && /Kč|CZK/.test(ot), p.url() + ' :: ' + ot.slice(0, 500));
  console.log('errors', p.errors);
};
