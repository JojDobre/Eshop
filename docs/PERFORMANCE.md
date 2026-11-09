# Výkon

Výsledky záťažového testu pre verziu 1.0 podľa kapitoly 18 plánu.

## Podmienky

- **Dáta:** 10 000 produktov, 200 kategórií, 50 000 objednávok (`scripts/perf-seed.php`).
- **Záťaž:** 10 súbežných používateľov, 20 sekúnd na stránku (`scripts/load-test.cjs`).
- **Server:** vstavaný server PHP 8.4 s 8 procesmi a OPcache, MariaDB 10.11, 4 jadrá CPU, testovací
  stroj (nie referenčný webhosting). Na webhostingu s PHP-FPM očakávajte podobné alebo lepšie časy.

## Výsledky

| Stránka | p50 | p95 | Cieľ p95 |
|---|---|---|---|
| Detail produktu | 164 ms | 309 ms | ≤ 800 ms (bez cache stránok) |
| Kategória | 140 ms | 275 ms | ≤ 800 ms |
| Kategória s filtrom a zoradením | 140 ms | 272 ms | ≤ 1 000 ms |
| Vyhľadávanie | 90 ms | 175 ms | – |
| Úvodná stránka | 112 ms | 222 ms | – |
| Administrácia: objednávky (50 000) | 199 ms | 379 ms | ≤ 1 000 ms |
| Administrácia: produkty (10 000) | 258 ms | 502 ms | ≤ 1 000 ms |

E-shop zatiaľ nemá cache celých stránok; aj bez nej detail produktu a kategória takmer spĺňajú cieľ
≤ 300 ms, ktorý plán stanovuje pre stránky z cache. Odoslanie objednávky sa meria v E2E teste
(`scripts/e2e.sh`), samostatný záťažový scenár pokladne je v pláne po 1.0.

## Optimalizácie vo verzii 1.0

- Nastavenia (jadra aj modulov) sa načítajú raz za požiadavku a ukladajú sa do cache – stránka kategórie
  klesla zo 150 na 25 databázových dotazov a p95 z 588 ms na 275 ms.

## Ako meranie zopakovať

```bash
php scripts/perf-seed.php
```

```bash
node scripts/load-test.cjs http://127.0.0.1:8000 10 20 "" -- /produkt/perf-produkt-5000 /kategoria/perf-kategoria-50
```
