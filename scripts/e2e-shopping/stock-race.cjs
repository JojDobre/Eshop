/*
 * Tovar sa vypredá počas vypĺňania pokladne.
 */
const L = require('./lib.cjs'); const { B, check } = L;
module.exports = async (b) => { const p = await L.newPage(b);
 await L.addToCart(p, '/produkt/kolumbia-huila-250-g', 3);
 await p.goto(B + '/pokladna');
 L.sql(`update products set stock_quantity=1 where id=2`);
 await p.fill('input[name=email]', 'race@example.test'); await p.fill('input[name=phone]', '0900000001');
 await p.fill('input[name=billing_name]', 'Rýchly Zákazník'); await p.fill('input[name=billing_street]', 'A 1');
 await p.fill('input[name=billing_zip]', '81101'); await p.fill('input[name=billing_city]', 'BA'); await p.check('input[name=terms]');
 await Promise.all([p.waitForLoadState('load'), p.locator('form[action$="/pokladna"] button[type=submit]').click()]);
 const t = await p.locator('#content').innerText();
 const orders = L.sql(`select count(*) from orders where email='race@example.test'`);
 check('R1 vypredaný tovar počas pokladne → objednávka sa nevytvorí a zákazník vidí dôvod', orders === '0' && /sklad|dostupn|množstv/i.test(t), p.url() + ' orders=' + orders + ' :: ' + (t.match(/[^\n]*(sklad|dostupn|množstv)[^\n]*/i) || [''])[0]);
 const stock = L.sql(`select stock_quantity from products where id=2`);
 check('R1 sklad nie je záporný', Number(stock) >= 0, stock);
 L.sql(`update products set stock_quantity=35 where id=2`);
};
