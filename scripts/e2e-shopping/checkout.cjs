/*
 * Pokladňa: kupóny, poukážky, validácie, dopravy a platby, doprava zadarmo.
 */
const L = require('./lib.cjs');
const { B, check } = L;
async function fillCustomer(p, o = {}) {
  await p.fill('input[name=email]', o.email || 'zakaznik@example.test');
  await p.fill('input[name=phone]', o.phone || '+421 900 123 456');
  await p.fill('input[name=billing_name]', o.name || 'Ján Testovací');
  await p.fill('input[name=billing_street]', 'Hlavná 1');
  await p.fill('input[name=billing_zip]', o.zip || '811 01');
  await p.fill('input[name=billing_city]', 'Bratislava');
}
async function placeOrder(p) {
  await p.check('input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  await p.waitForTimeout(800);
  return p.url();
}
module.exports = async (browser) => {
  let p = await L.newPage(browser);

  // K1: kupóny a poukážky
  await L.addToCart(p, '/produkt/kolumbia-huila-250-g', 2); // 23,00
  await p.goto(B + '/kosik');
  const couponForm = p.locator('form[action$="/kosik/kupon"]');
  await couponForm.locator('input[name=code]').fill('NEEXISTUJE'); await couponForm.locator('button').click(); await p.waitForTimeout(800);
  let t = await p.locator('main').innerText();
  check('K1 neplatný kupón → chyba', /neplat|neexist|nenašl/i.test(t), t.slice(0, 400));
  await p.locator('form[action$="/kosik/kupon"] input[name=code]').fill('MIN100'); await p.locator('form[action$="/kosik/kupon"] button').click(); await p.waitForTimeout(800);
  t = await p.locator('main').innerText();
  check('K1 kupón s minimom pod hranicou → chyba', /minim|aspoň|od /i.test(t) && !/−\s?4,60|-4,60/.test(t), t.slice(0, 500));
  await p.locator('form[action$="/kosik/kupon"] input[name=code]').fill('zlava10'); await p.locator('form[action$="/kosik/kupon"] button').click(); await p.waitForTimeout(800);
  t = await p.locator('main').innerText();
  check('K1 kupón ZLAVA10 (malými písmenami) → zľava 2,30 €', /2,30/.test(t) && /20,70/.test(t), t.slice(0, 600));
  await p.locator('form[action$="/kosik/poukazka"] input[name=code]').fill('DAREK2026AB'); await p.locator('form[action$="/kosik/poukazka"] button').click(); await p.waitForTimeout(800);
  t = await p.locator('main').innerText();
  check('K1 poukážka 10 € → na úhradu 10,70 €', /10,70/.test(t), t.slice(0, 700));

  // K2: validácie pokladne
  await p.goto(B + '/pokladna'); await L.dismissCookies(p);
  await p.evaluate(() => { const f = document.querySelector('form[action$="/pokladna"]'); f.noValidate = true; });
  await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  t = await p.locator('main').innerText();
  const errs = ['e-mail', 'meno', 'ulic', 'psč', 'mesto', 'podmienk'].filter(w => new RegExp(w, 'i').test(t.match(/(Pole|musí|povinn)[^\n]*/gi)?.join(' ') || ''));
  check('K2 prázdna pokladňa → chyby pri povinných poliach', errs.length >= 5, 'nájdené: ' + errs.join(',') + ' :: ' + (t.match(/(Pole|musí|povinn)[^\n]*/gi) || []).join(' | '));
  check('K2 chybové hlášky bez technických názvov polí', !/billing_|shipping_|_id\b/.test(t), (t.match(/[^\n]*(billing_|shipping_)[^\n]*/g) || []).join(' | '));
  await fillCustomer(p, { email: 'zly-email' });
  await p.evaluate(() => { document.querySelector('form[action$="/pokladna"]').noValidate = true; });
  await p.check('input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  t = await p.locator('main').innerText();
  check('K2 neplatný e-mail → chyba a vyplnené údaje ostanú', /e-mail/i.test(t) && await p.inputValue('input[name=billing_name]') === 'Ján Testovací', '');
  // firma bez názvu a IČO
  await p.fill('input[name=email]', 'firma@example.test');
  await p.check('input[name=is_company]');
  await p.evaluate(() => { document.querySelector('form[action$="/pokladna"]').noValidate = true; });
  await p.check('input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  t = await p.locator('main').innerText();
  check('K2 nákup na firmu vyžaduje názov firmy a IČO', /firm/i.test(t) && /IČO/i.test(t) && p.url().endsWith('/pokladna'), (t.match(/[^\n]*(firm|IČO)[^\n]*/gi) || []).slice(0, 5).join(' | '));
  await p.uncheck('input[name=is_company]').catch(() => {});
  // iná dodacia adresa bez údajov (kuriér)
  await p.check('input[name=shipping_method_id][value="2"]');
  await p.check('input[name=ship_to_different_address]');
  await p.evaluate(() => { document.querySelector('form[action$="/pokladna"]').noValidate = true; });
  await p.check('input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  t = await p.locator('main').innerText();
  check('K2 iná dodacia adresa vyžaduje údaje', p.url().endsWith('/pokladna') && /doruč|dodac/i.test(t), (t.match(/[^\n]*(Pole|musí)[^\n]*/gi) || []).slice(0, 6).join(' | '));

  // K3: objednávka kuriér + dobierka s kupónom a poukážkou
  await p.uncheck('input[name=ship_to_different_address]');
  await fillCustomer(p);
  await p.check('input[name=shipping_method_id][value="2"]'); await p.waitForTimeout(300);
  const codVisible = await p.locator('input[name=payment_method_id][value="2"]').isVisible();
  check('K3 pri kuriérovi je dostupná dobierka', codVisible, '');
  await p.check('input[name=payment_method_id][value="2"]'); await p.waitForTimeout(300);
  const summary = await p.locator('aside, .checkout-summary').first().innerText().catch(() => '');
  console.log('SUMMARY K3:', summary.replace(/\n/g, ' | '));
  let url = await placeOrder(p);
  t = await p.locator('main').innerText();
  check('K3 objednávka kuriér + dobierka vytvorená', /\/objednavka\//.test(url), url + ' :: ' + t.slice(0, 400));
  console.log('ORDER K3:', t.replace(/\n/g, ' | ').slice(0, 900));

  // K4: osobný odber + bankový prevod → QR kód, košík prázdny
  await L.addToCart(p, '/produkt/etiopia-yirgacheffe-250-g', 1);
  await p.goto(B + '/pokladna'); await fillCustomer(p);
  await p.check('input[name=shipping_method_id][value="1"]'); await p.waitForTimeout(300);
  check('K4 pri osobnom odbere nie je dobierka', !(await p.locator('input[name=payment_method_id][value="2"]').isVisible()), '');
  await p.check('input[name=payment_method_id][value="1"]');
  url = await placeOrder(p);
  t = await p.locator('main').innerText();
  check('K4 objednávka s bankovým prevodom má QR a údaje na platbu', /\/objednavka\//.test(url) && await p.locator('main img[alt*="QR"], main svg').count() > 0 && /IBAN|účet|VS|variabil/i.test(t), t.slice(0, 600));
  await p.goto(B + '/kosik');
  check('K4 po objednávke je košík prázdny', /prázdny/i.test(await p.locator('main').innerText()), '');

  // K5: doprava zdarma od 60 € (Brazília 2× 34 € = 68 €)
  await L.addToCart(p, '/produkt/brazilia-santos-1-kg', 2);
  await p.goto(B + '/pokladna');
  t = await p.locator('form[action$="/pokladna"]').innerText();
  check('K5 kuriér zadarmo nad 60 €', /Kuriér na adresu[\s\S]{0,80}Zadarmo/.test(t), t.match(/Kuriér na adresu[\s\S]{0,80}/)?.[0]);
  await fillCustomer(p, { email: 'zdarma@example.test' });
  await p.check('input[name=shipping_method_id][value="2"]'); await p.check('input[name=payment_method_id][value="1"]');
  url = await placeOrder(p);
  check('K5 objednávka vytvorená', /\/objednavka\//.test(url), url);

  console.log('JS/HTTP errors:', p.errors);
};
