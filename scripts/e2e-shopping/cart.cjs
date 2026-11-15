/*
 * Košík: množstvo, sklad, vypredaný tovar, varianty, doplnky.
 */
const L = require('./lib.cjs');
const { B, check } = L;
module.exports = async (browser) => {
  // S1: jednoduchý produkt, množstvo, zmena, odstránenie
  let p = await L.newPage(browser);
  await L.addToCart(p, '/produkt/kolumbia-huila-250-g', 2);
  let text = await L.cartTotal(p);
  check('S1 košík obsahuje 2 ks Kolumbia', /Kolumbia Huila/.test(text) && /23,00/.test(text), text.slice(0, 400));
  await p.locator('main input[name=quantity]').first().fill('3');
  await p.locator('main input[name=quantity]').first().press('Enter'); await p.waitForTimeout(1200);
  text = await L.cartTotal(p);
  check('S1 zmena množstva na 3 → 34,50', /34,50/.test(text), text.slice(0, 400));
  await p.goto(B + '/kosik');
  await p.locator('main form').filter({ has: p.locator('input[name=_method][value=DELETE]') }).first().locator('button').click(); await p.waitForTimeout(1000);
  text = await L.cartTotal(p);
  check('S1 odstránenie → prázdny košík', /prázdny/i.test(text), text.slice(0, 300));

  // S2: viac než je na sklade (Brazília 12 ks)
  await p.goto(B + '/produkt/brazilia-santos-1-kg');
  check('S2 pole množstva má max = sklad (12)', await p.locator('main input[name=quantity]').first().getAttribute('max') === '12', '');
  const tok = await p.locator('main input[name=_token]').first().inputValue();
  await p.request.post(B + '/kosik', { form: { product_id: '3', quantity: '20', _token: tok } });
  text = await L.cartTotal(p);
  const qtyVal = await p.locator('main input[name=quantity]').first().inputValue().catch(() => '?');
  check('S2 priamy POST 20 ks → v košíku najviac 12 alebo nič', qtyVal === '12' || qtyVal === '?', 'qty=' + qtyVal + ' ' + text.slice(0,200));
  await p.goto(B + '/kosik'); while (await p.locator('main input[name=_method][value=DELETE]').count()) { await p.locator('main form').filter({ has: p.locator('input[name=_method][value=DELETE]') }).first().locator('button').click(); await p.waitForTimeout(800); }

  // S3: vypredaný produkt
  await p.goto(B + '/produkt/rooibos-s-vanilkou-100-g');
  const buyBtn = p.locator('main form button[type=submit]', { hasText: /košíka/i });
  const disabled = (await buyBtn.count()) === 0 || await buyBtn.first().isDisabled();
  check('S3 vypredaný produkt sa nedá kúpiť', disabled, 'tlačidlo je aktívne');
  const resp = await p.request.post(B + '/kosik', { form: { product_id: '6', quantity: '1', _token: await p.locator('input[name=_token]').first().inputValue() } });
  text = await L.cartTotal(p);
  check('S3 ani priamym POST sa vypredaný nepridá', !/Rooibos/.test(text), 'status ' + resp.status());

  // S4: varianty
  await p.goto(B + '/produkt/tricko-e2e');
  const btn = p.locator('main form').filter({ has: p.locator('input[name=variant_id]') }).locator('button[type=submit]').first();
  check('S4 bez výberu variantu je nákup zablokovaný', await btn.isDisabled(), 'tlačidlo aktívne');
  await p.getByRole('button', { name: 'M', exact: true }).click();
  const blue = p.getByRole('button', { name: /modrá/ }).first();
  check('S4 vypredaný variant M/modrá je nedostupný', await blue.isDisabled() || /line-through|opacity/.test(await blue.getAttribute('class') || ''), await blue.getAttribute('class'));
  await p.getByRole('button', { name: /červená/ }).first().click(); await p.waitForTimeout(300);
  check('S4 cena variantu M/červená 18,00 €', /18,00/.test(await p.locator('main').innerText()), '');
  await btn.click(); await p.waitForTimeout(1000);
  text = await L.cartTotal(p);
  check('S4 v košíku je variant M / červená', /M \/ červená/.test(text) && /18,00/.test(text), text.slice(0, 500));

  // S5: doplnok s textom
  await p.goto(B + '/produkt/rucny-mlyncek-na-kavu');
  const addonBox = p.getByLabel(/Gravírovanie/);
  check('S5 doplnok je na produkte', await addonBox.count() > 0, '');
  if (await addonBox.count()) {
    await addonBox.first().check();
    await p.locator('main form button[type=submit]', { hasText: /košíka/i }).first().click(); await p.waitForTimeout(800);
    const errText = await p.locator('main').innerText();
    check('S5 doplnok bez textu → chyba', /Text gravírovania|povinn/i.test(errText) && !/Gravírovanie[\s\S]{0,40}Košík/.test(errText), '');
    await p.getByLabel(/Text gravírovania/).fill('Pre Jožka');
    await p.locator('main form button[type=submit]', { hasText: /košíka/i }).first().click(); await p.waitForTimeout(1000);
    text = await L.cartTotal(p);
    check('S5 doplnok v košíku s textom a cenou', /Pre Jožka/.test(text) && /Gravírovanie/.test(text), text.slice(0, 600));
  }
  console.log('JS/HTTP errors:', p.errors);
  await p.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/e2e-cart.png', fullPage: true });
};
