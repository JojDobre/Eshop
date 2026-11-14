# Príručka pre vývojárov

Architektúra je opísaná v [ARCHITECTURE.md](ARCHITECTURE.md), pravidlá kódu v `CLAUDE.md`.
Tu je praktický návod, ako e-shop rozšíriť modulom alebo šablónou.

## Vývojové prostredie

```bash
composer install
```

```bash
npm install && npm run build
```

```bash
php artisan test
```

Testy bežia na SQLite v pamäti; CI ich spúšťa aj na MySQL 8.0, 8.4 a MariaDB 10.6/11.4 a overuje
prechody databázy zo všetkých vydaných verzií (`scripts/test-upgrades.sh`). Štýl kódu: `vendor/bin/pint`.

## Modul

```
modules/Mojmodul/
├── module.json                      názov, verzia, závislosti, oprávnenia, veľkosti obchodu
├── src/MojmodulServiceProvider.php  odvodený od App\Core\Modules\ModuleServiceProvider
├── src/MojmodulSettings.php         nastavenia (spatie/laravel-settings) – zaregistrujú sa samy
├── src/Filament/Resources|Pages     administrácia (nájde sa automaticky)
├── database/migrations              migrácie aj migrácie nastavení
├── resources/views                  šablóny pod menným priestorom mojmodul::
├── routes/web.php                   trasy obchodu
└── lang/{sk,cs,en}                  preklady pod menným priestorom mojmodul::
```

`module.json` – `requires.eshop` a `requires.modules` používajú caret (`^1.0`). Moduly sa zapínajú
v administrácii; vypnutý modul sa vôbec nenačíta.

### Body rozšírenia

| Čo | Ako |
|---|---|
| Kontrola v pokladni | `CheckoutRequest::addCheck('kluc', fn ($validator, $request) => …)` |
| Dostupnosť produktu | `Product::addAvailabilityCheck('kluc', fn (Product $p) => bool)` |
| Sekcia vo formulári produktu | `ProductResource::addFormSection('kluc', fn () => Section::make(…))` |
| Kód po súhlase s cookies | `CookieConsent::addCode('kluc', 'analytics', fn () => '<script>…')` |
| E-mailová šablóna | `EmailTemplates::register(new EmailTemplateDefinition(…))` |
| Export dát | `Exports::register(new ExportDefinition(…))` (modul Import a export) |
| Uchovávanie osobných údajov | `DataRetention::register(new RetentionPolicy(…))` |
| Kuriér | implementácia `Modules\Shipping\Carriers\Carrier` |
| Zapínateľná funkcia | `FeatureToggles::register(new FeatureToggle(…))` |
| Plánovaná úloha | `callAfterResolving(Schedule::class, fn ($schedule) => …)` – beží cez cron aj webový cron |

Pravidlá: obchodná logika v akciách a službách, texty iba z prekladov, žiadne procesy na pozadí,
Redis ani symbolické odkazy (lacný webhosting).

## Šablóna

```
themes/mojasablona/
├── theme.json        názov, verzia, rodič, nastavenia (text, color, toggle, select, number, image), ponuky
├── templates/        stránky (home, category, product, cart, checkout, order, page, search…)
├── partials/         časti (header, footer, product-card…)
├── blocks/           bloky pre skladanie stránok
├── lang/{sk,cs,en}   preklady pod theme::
└── assets/           štýly a skripty (Vite pri dodávaných šablónach, hotové súbory pri nahratých)
```

Odvodená šablóna (`"parent": "default"`) obsahuje iba súbory, ktoré mení – napr. šablóna **Farma**
prepisuje hlavičku, pätičku, kartu produktu a úvodnú stránku a zvyšok preberá z Default. Nastavenia
z `theme.json` sú v šablóne cez `$themeOptions->get('kluc')`, obrázky cez `$themeOptions->imageUrl('kluc')`.
Typy nastavení: `text`, `textarea`, `richtext`, `color`, `select`, `toggle`, `number`, `image` a `sections`
(zoradenie sekcií s rozložením). Zdedené nastavenia, ktoré odvodená šablóna nepoužíva, skryjete zoznamom
`"hide_settings": ["announcement"]`, aby v prispôsobení nemiatli.
Kontrolu šablóny spustíte v **Vzhľad → Šablóny**.

Farba značky je premenná `--brand-primary`; predvolené premenné CSS definujte cez `:where(:root)`,
aby ich nastavenia obchodu vždy prepísali.

## Vydanie verzie

1. Zvýšte `App\Core\Eshop::VERSION`, verzie zmenených modulov a šablón a zapíšte zmeny do `CHANGELOG.md`.
2. `scripts/build-release.sh` – inštalačný balík `dist/eshop-<verzia>.zip` a aktualizačný balík
   `dist/eshop-update-<verzia>.zip`, podpísaný kľúčom z `ESHOP_SIGNING_KEY`.
3. `scripts/e2e.sh` – inštalácia a nákupy v prehliadači, `scripts/a11y-check.cjs` – prístupnosť.

Pár kľúčov na podpisovanie vytvorí `php scripts/generate-signing-key.php`. Súkromný kľúč nikdy
nedávajte do repozitára, verejný vložte do `App\Core\Eshop::UPDATE_PUBLIC_KEY`.
