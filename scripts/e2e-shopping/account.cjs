/*
 * Zákaznícky účet (registrácia, prihlásenie, história, zapamätaná adresa) a nákup na mobile.
 */
const L = require('./lib.cjs'); const { B, check } = L;
module.exports = async (b) => {
  // U1: registrácia, prihlásenie, predvyplnená pokladňa, história objednávok
  const p = await L.newPage(b);
  await p.goto(B + '/ucet/registracia'); await L.dismissCookies(p);
  const fields = await p.evaluate(() => [...document.querySelectorAll('#content form input')].map(i => i.name + ':' + i.type));
  console.log('REG fields', fields);
  // validácia: rozdielne heslá
  await p.fill('#content input[name=name]', 'Mária Zákazníčka');
  await p.fill('#content input[name=email]', 'maria@example.test');
  await p.fill('#content input[name=password]', 'Heslo-Maria-123');
  await p.fill('#content input[name=password_confirmation]', 'Ine-heslo-123');
  await p.check('#content input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('#content form button[type=submit]').first().click()]);
  let t = await p.locator('#content').innerText();
  check('U1 rozdielne heslá → chyba', /heslo|potvrdenie/i.test(t) && /registracia/.test(p.url()), t.slice(0, 300));
  await p.fill('#content input[name=password]', 'Heslo-Maria-123');
  await p.fill('#content input[name=password_confirmation]', 'Heslo-Maria-123');
  await p.check('#content input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('#content form button[type=submit]').first().click()]);
  t = await p.locator('#content').innerText();
  check('U1 registrácia úspešná → účet', /\/ucet/.test(p.url()) && !/registracia/.test(p.url()), p.url() + ' ' + t.slice(0, 200));
  await L.addToCart(p, '/produkt/zeleny-caj-sencha-100-g', 1);
  await p.goto(B + '/pokladna');
  check('U1 pokladňa predvyplní e-mail prihláseného', await p.inputValue('input[name=email]') === 'maria@example.test', await p.inputValue('input[name=email]'));
  await p.fill('input[name=phone]', '0900111222'); await p.fill('input[name=billing_name]', 'Mária Zákazníčka');
  await p.fill('input[name=billing_street]', 'Nová 5'); await p.fill('input[name=billing_zip]', '04001'); await p.fill('input[name=billing_city]', 'Košice');
  await p.check('input[name=terms]');
  await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  check('U1 objednávka prihláseného zákazníka', /\/objednavka\//.test(p.url()), p.url());
  await p.goto(B + '/ucet'); t = await p.locator('#content').innerText();
  check('U1 objednávka je v histórii účtu', /2026\d{5}/.test(t), t.slice(0, 400));
  // druhý nákup – adresa sa zapamätá
  await L.addToCart(p, '/produkt/zeleny-caj-sencha-100-g', 1);
  await p.goto(B + '/pokladna');
  check('U1 druhý nákup má predvyplnenú adresu', await p.inputValue('input[name=billing_street]') === 'Nová 5', await p.inputValue('input[name=billing_street]'));
  // odhlásenie a zlé heslo
  await p.locator('form[action$="/ucet/odhlasenie"] button').first().click().catch(async () => { await p.goto(B + '/ucet'); await p.locator('form[action$="/ucet/odhlasenie"] button').first().click(); });
  await p.waitForTimeout(800);
  await p.goto(B + '/ucet/prihlasenie');
  await p.fill('#content input[name=email]', 'maria@example.test'); await p.fill('#content input[name=password]', 'zle-heslo');
  await Promise.all([p.waitForLoadState('load'), p.locator('#content form button[type=submit]').first().click()]);
  check('U1 zlé heslo → chyba, neprihlási', /prihlasenie/.test(p.url()) && /nesprávn|neplatn|údaje/i.test(await p.locator('#content').innerText()), (await p.locator('#content').innerText()).slice(0, 200));

  // M1: celý nákup na mobile
  const m = await L.newPage(b, { ...L.devices['iPhone 13'] });
  await m.goto(B + '/'); await L.dismissCookies(m);
  await m.getByRole('button', { name: /menu/i }).first().click().catch(() => {});
  await m.waitForTimeout(400); await m.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/m-menu.png' });
  await L.addToCart(m, '/produkt/etiopia-yirgacheffe-250-g', 1);
  await m.goto(B + '/kosik'); await m.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/m-cart.png', fullPage: true });
  const overflow = async () => m.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
  check('M1 košík na mobile bez vodorovného posúvania', !(await overflow()), '');
  await m.goto(B + '/pokladna');
  check('M1 pokladňa na mobile bez vodorovného posúvania', !(await overflow()), '');
  await m.fill('input[name=email]', 'mobil@example.test'); await m.fill('input[name=phone]', '0900333444');
  await m.fill('input[name=billing_name]', 'Mobilný Zákazník'); await m.fill('input[name=billing_street]', 'Krátka 2');
  await m.fill('input[name=billing_zip]', '01001'); await m.fill('input[name=billing_city]', 'Žilina');
  await m.check('input[name=shipping_method_id][value="2"]'); await m.check('input[name=payment_method_id][value="2"]');
  await m.check('input[name=terms]');
  await m.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/m-checkout.png', fullPage: true });
  await Promise.all([m.waitForLoadState('load'), m.locator('form[action$="/pokladna"] button[type=submit]').click()]);
  check('M1 objednávka z mobilu vytvorená', /\/objednavka\//.test(m.url()), m.url());
  await m.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/m-thanks.png', fullPage: true });
  console.log('errors', p.errors, m.errors);
};
