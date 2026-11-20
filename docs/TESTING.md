# Testovanie

Obchod má dve sady automatických testov:

| Sada | Čo overuje | Príkaz |
|---|---|---|
| PHPUnit | logika (objednávky, sklad, QR platby, e-maily, PDF doklady…) | `php artisan test` |
| Playwright | správanie v prehliadači: administrácia, nákup, mobil, prístupnosť, vzhľad šablón, rýchlosť | `npm run e2e` |

Obe sady bežia automaticky na GitHube pri každej zmene (CI). Výsledky testov v prehliadači
(správa, snímky chýb, Lighthouse) sú v prílohe behu `e2e-report`.

## Testy v prehliadači lokálne

1. Spustite obchod, napr. cez Docker (`docs/DOCKER.md`) – adresa `http://localhost:8080`.
2. Nainštalujte nástroje (raz):

   ```bash
   npm install
   npx playwright install chromium
   ```

3. Spustite testy. Prihlásenie správcu sa zadáva premennými:

   ```bash
   E2E_BASE=http://localhost:8080 E2E_ADMIN_EMAIL=admin@test.local E2E_ADMIN_PASSWORD=heslo npm run e2e
   ```

   Pri čistej databáze test obchod aj nainštaluje (`E2E_INSTALL=1` a údaje k databáze `E2E_DB_HOST`,
   `E2E_DB_NAME`, `E2E_DB_USERNAME`, `E2E_DB_PASSWORD`). Ak beží obchod v Dockeri, nastavte aj
   `E2E_APP` na priečinok, kde je `artisan` dostupný, alebo spúšťajte testy priamo v kontajneri.

> Testy vytvárajú produkty a objednávky („E2E vajcia…“). Púšťajte ich iba na testovacej kópii, nikdy na ostrom obchode.

## Užitočné príkazy

| Príkaz | Čo robí |
|---|---|
| `npm run e2e` | administrácia, nákup, mobil, validácia formulárov, prístupnosť |
| `npm run e2e:vzhlad` | porovná snímky šablón a administrácie s uloženými |
| `npm run e2e:snimky` | po zámernej zmene vzhľadu uloží nové snímky (`tests/e2e/__snapshots__`) |
| `npm run e2e:rychlost` | Lighthouse (výkon, prístupnosť, SEO) – správy v `storage/e2e/lighthouse` |
| `npm run e2e:report` | otvorí správu z posledného behu |
| `npx playwright test --ui` | interaktívny režim s prehliadačom |

## Kde sú testy

- `tests/e2e/setup` – inštalácia, testovacie dáta (`tests/e2e/fixtures/seed.php`), prihlásenie
- `tests/e2e/admin.spec.js` – nástenka, zoznamy, menu „…“, deň/noc, prispôsobenie šablóny
- `tests/e2e/shop.spec.js` – nákup s minimom 10 ks, platba prevodom s QR kódom
- `tests/e2e/product-validation.spec.js` – validácia formulára nového produktu
- `tests/e2e/a11y.spec.js` – prístupnosť (axe-core, WCAG 2.1 AA)
- `tests/e2e/visual` – snímky šablón a administrácie
- `scripts/lighthouse.mjs` – rýchlosť stránok
