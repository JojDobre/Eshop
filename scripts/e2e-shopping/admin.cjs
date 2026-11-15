/*
 * Administrácia objednávky: zaplatenie a faktúra, zmena stavu, storno (sklad a poukážka späť), PDF doklady.
 */
const L = require('./lib.cjs'); const { B, check } = L;
const { sql } = L;
module.exports = async (b) => { const p = await L.newPage(b, { viewport: { width: 1440, height: 1000 } });
  await p.goto(B + '/admin/login'); await p.fill('input[type=email]', L.admin.email); await p.fill('input[type=password]', L.admin.password);
  await p.click('button[type=submit]'); await p.waitForURL(/admin(?!\/login)/);
  // Objednávky z checkout.cjs: prvá dobierka, druhá bankový prevod.
  const [codId, bankId] = sql("select id from orders where email='zakaznik@example.test' order by id").split('\n');
  // Označiť ako zaplatenú (bankový prevod)
  await p.goto(B + '/admin/orders/' + bankId); await p.waitForTimeout(800);
  await p.getByRole('button', { name: 'Označiť ako zaplatenú' }).click(); await p.waitForTimeout(600);
  const confirm = p.locator('.fi-modal-window button', { hasText: /Potvrdiť|Označiť|Áno/ }).last();
  if (await confirm.isVisible().catch(() => false)) await confirm.click();
  await p.waitForTimeout(1500);
  check('A1 objednávka označená ako zaplatená', sql(`select payment_status from orders where id=${bankId}`) === 'paid', sql(`select payment_status from orders where id=${bankId}`));
  const docs = sql(`select type from invoices where order_id=${bankId}`).split('\n');
  check('A1 po zaplatení je vystavená faktúra', docs.some(d => /invoice/.test(d) && !/proforma/.test(d)), docs.join(','));
  // Zmeniť stav na odoslanú
  await p.getByRole('button', { name: 'Zmeniť stav' }).click(); await p.waitForTimeout(700);
  await p.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/adm-status-modal.png' });
  const sel = p.locator('.fi-modal-window select').first();
  if (await sel.count()) { const opts = await sel.locator('option').allInnerTexts(); console.log('statusy', opts); await sel.selectOption({ label: opts.find(o => /Odoslan|Expedovan/i.test(o)) }); }
  else { await p.locator('.fi-modal-window .fi-select-input-btn, .fi-modal-window [role=combobox]').first().click(); await p.getByRole('option', { name: /Odoslan|Expedovan/i }).first().click(); }
  await p.locator('.fi-modal-window button[type=submit]').last().click(); await p.waitForTimeout(1500);
  check('A2 stav zmenený na odoslaná', /shipped|sent/.test(sql(`select status from orders where id=${bankId}`)), sql(`select status from orders where id=${bankId}`));
  // Storno dobierkovej objednávky → sklad a poukážka späť
  const stockBefore = sql('select stock_quantity from products where id=2'); const vBefore = sql("select balance from gift_vouchers");
  await p.goto(B + '/admin/orders/' + codId); await p.waitForTimeout(800);
  await p.getByRole('button', { name: 'Zmeniť stav' }).click(); await p.waitForTimeout(700);
  if (await sel.count()) { const opts = await p.locator('.fi-modal-window select').first().locator('option').allInnerTexts(); await p.locator('.fi-modal-window select').first().selectOption({ label: opts.find(o => /Zrušen|Storno/i.test(o)) }); }
  else { await p.locator('.fi-modal-window .fi-select-input-btn, .fi-modal-window [role=combobox]').first().click(); await p.getByRole('option', { name: /Zrušen|Storno/i }).first().click(); }
  await p.locator('.fi-modal-window button[type=submit]').last().click(); await p.waitForTimeout(1500);
  check('A3 zrušená objednávka', sql(`select status from orders where id=${codId}`) === 'cancelled', sql(`select status from orders where id=${codId}`));
  check('A3 tovar sa vrátil na sklad', Number(sql('select stock_quantity from products where id=2')) === Number(stockBefore) + 2, stockBefore + ' → ' + sql('select stock_quantity from products where id=2'));
  check('A3 poukážka sa vrátila', Number(sql('select balance from gift_vouchers')) === Number(vBefore) + 1000, vBefore + ' → ' + sql('select balance from gift_vouchers'));
  // Faktúra PDF sa dá stiahnuť (správca aj zákazník)
  const invId = sql(`select id from invoices where order_id=${bankId} order by id desc limit 1`);
  const r = await p.request.get(B + '/admin/doklady/' + invId + '/pdf');
  const body = await r.body();
  check('A4 správca stiahne PDF dokladu', r.status() === 200 && body.slice(0, 4).toString() === '%PDF', r.status() + ' ' + r.headers()['content-type']);
  const tokenNum = sql(`select concat(o.token, ' ', i.number) from invoices i join orders o on o.id=i.order_id where i.id=${invId}`).split(' ');
  const ctx2 = await b.newContext(); const r2 = await ctx2.request.get(B + '/objednavka/' + tokenNum[0] + '/doklad/' + tokenNum[1]);
  check('A4 zákazník stiahne PDF zo stránky objednávky', r2.status() === 200 && (await r2.body()).slice(0, 4).toString() === '%PDF', String(r2.status()));
  const r3 = await ctx2.request.get(B + '/admin/doklady/' + invId + '/pdf', { maxRedirects: 0 });
  check('A4 neprihlásený nestiahne doklad z administrácie', r3.status() !== 200, String(r3.status()));
  console.log('errors', p.errors);
};
