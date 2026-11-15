/*
 * E2E test celého nákupu na čistej inštalácii s ukážkovými dátami (veľkosť obchodu „veľký“).
 *
 * Použitie: E2E_BASE=http://127.0.0.1:8199 E2E_APP=/cesta/k/eshop DB_NAME=… DB_USERNAME=… DB_PASSWORD=…
 *           E2E_ADMIN_EMAIL=… E2E_ADMIN_PASSWORD=… node scripts/e2e-shopping/run.cjs
 */
const { execFileSync } = require('child_process');
const path = require('path');
const L = require('./lib.cjs');

(async () => {
  if (process.env.E2E_APP) {
    console.log(execFileSync('php', [path.join(process.env.E2E_APP, 'artisan'), 'tinker', '--execute=require ' + JSON.stringify(path.join(__dirname, 'seed.php')) + ';']).toString().trim());
    execFileSync('php', [path.join(process.env.E2E_APP, 'artisan'), 'optimize:clear']);
  }

  const browser = await L.chromium.launch();

  for (const suite of ['cart', 'checkout', 'admin', 'account', 'international', 'stock-race']) {
    console.log('\n→ ' + suite);
    try {
      await require('./' + suite + '.cjs')(browser);
    } catch (error) {
      const last = L.lastPage;
      const where = last ? last.url() : '';
      if (last) await last.screenshot({ path: (process.env.E2E_SHOTS || '/tmp') + '/fail-' + suite + '.png', fullPage: true }).catch(() => {});
      L.check(suite + ': scenár sa nedokončil', false, error.message.split('\n')[0] + ' @ ' + where);
    }
  }

  await browser.close();
  const failed = L.results.filter((result) => ! result.ok);
  console.log(`\n${L.results.length - failed.length}/${L.results.length} kontrol prešlo`);
  failed.forEach((result) => console.log('  ✗ ' + result.name + ' – ' + result.detail));
  process.exit(failed.length ? 1 : 0);
})();
