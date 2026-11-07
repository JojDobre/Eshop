# Architektúra

Princíp: **staviame e-shop, nie framework.** Všade, kde má Laravel alebo overený balík stabilné riešenie,
používame ho. Vlastný kód pridávame iba tam, kde to vyžaduje modulárnosť e-shopu.

## Prehľad

| Potreba | Riešenie |
|---|---|
| Udalosti, fronta, migrácie, oprávnenia, kontajner, súbory | Laravel (Events, Queue, Migrations, Policies a Gates, Service Container, Filesystem) |
| Administrácia | Filament 5 |
| Nastavenia | `spatie/laravel-settings` (`app/Settings`) |
| Roly | `spatie/laravel-permission` |
| Preklady obsahu | `spatie/laravel-translatable` + `HasContentTranslations` (jazyk obsahu = jazyk obchodu) |
| Peniaze | `brick/money`, celé čísla v najmenších jednotkách meny (`App\Core\Support\Price`) |
| **Moduly** | vlastná tenká vrstva `app/Core/Modules` |
| **Šablóny vzhľadu** | vlastná tenká vrstva `app/Core/Themes` nad Blade |
| **Inštalátor** | `app/Core/Installer` |

```
app/
  Core/
    Eshop.php           verzia systému a minimálne požiadavky
    Installer/          webový inštalátor (kontrola hostingu, databáza, zápis .env, inštalácia)
    Modules/            načítanie modulov, závislosti, stav zapnutia
    Themes/             šablóny vzhľadu a poradie hľadania súborov
  Filament/             administrácia (stránky nastavení, používatelia, moduly)
  Providers/
    EshopServiceProvider.php   zaregistruje jadro, moduly a šablónu
  Settings/             nastavenia obchodu, značky a firmy
modules/                interné moduly
themes/                 šablóny vzhľadu obchodu
```

## Stav inštalácie

- Pri prvej požiadavke na webhostingu bez `.env` sa súbor vytvorí z `.env.example` s náhodným kľúčom
  (`bootstrap/prepare-env.php`).
- Kým nie je e-shop nainštalovaný, middleware `EnsureInstalled` presmeruje všetko na `/install`.
  Po inštalácii je inštalátor nedostupný (404).
- Stav je v súbore `storage/app/eshop/installed.json`, nie v databáze, aby sa dal overiť bez pripojenia.
- Ak koreňový `.htaccess` smeruje požiadavky do `public/`, `public/index.php` upraví základnú cestu
  a po inštalácii sa všetky odkazy tvoria z `APP_URL`, takže adresy neobsahujú `/public`.

## Moduly

| Modul | Obsah | Závisí od |
|---|---|---|
| `storefront` | úvodná stránka, sitemap, robots.txt, právne stránky a ich verzie, súhlas s cookies | – |
| `tax` | sadzby DPH, výpočet DPH | – |
| `catalog` | kategórie, produkty, varianty, vlastné polia, výrobcovia (GPSR), sklad, história cien | storefront, tax |
| `shipping` | spôsoby dopravy, cena podľa hmotnosti | – |
| `payments` | spôsoby platby, QR kódy na platbu (PAY by square, QR Platba, EPC) | shipping |
| `cart` | košík (v databáze, token v session) | catalog |
| `orders` | pokladňa, objednávky, kupóny, e-maily, register platobných brán, administrácia objednávok | storefront, catalog, cart, shipping, payments, tax |
| `pages` | statické stránky, verzie obchodných podmienok a zásad ochrany údajov | storefront |
| `invoices` | zálohové faktúry, faktúry, dobropisy v PDF | orders, tax |
| `stripe` | platba kartou cez Stripe Checkout a webhook | orders, payments |
| `customers` | zákaznícke účty (samostatné prihlásenie `customer`) | storefront, orders |
| `returns` | odstúpenie od zmluvy a reklamácie | orders |
| `translator` | strojový preklad (DeepL, Google), hromadný preklad | – |

Moduly komunikujú cez **udalosti** (`EshopInstalled` → predvolené dáta, `OrderPlaced` → e-maily,
zálohová faktúra, priradenie k účtu; `OrderStatusChanged` → e-mail zákazníkovi; `OrderPaid` → faktúra)
a cez **view composery** (napr. katalóg dodá kategórie do hlavičky, košík počet kusov). Šablóna tak
nemusí vedieť, ktoré moduly sú zapnuté.

Ďalšie body rozšírenia (registre v kontajneri):

| Register | Na čo |
|---|---|
| `EmailTemplates` (jadro) | modul zaregistruje upraviteľný e-mail (`EmailTemplateDefinition`), odosiela cez `TemplateMail` |
| `LegalPages` (storefront) | adresy a aktuálne verzie právnych dokumentov (`LegalDocument`) |
| `SitemapRegistry` (storefront) | adresy do `sitemap.xml` |
| `OrderMailAttachments` (orders) | prílohy potvrdenia objednávky (zálohová faktúra, obchodné podmienky) |
| `PaymentGateways` (orders) | platobné brány (`PaymentGateway`), napr. Stripe |
| `OrderResource::registerRelationManager()` | záložky v detaile objednávky (doklady, reklamácie) |
| `PaymentQrRegistry` (invoices) | QR kód na dokladoch |

Nastavenia modulu môžu byť v module (trieda `Settings` z `spatie/laravel-settings` a migrácia
v `database/migrations`), napr. `Modules\Stripe\StripeSettings`.

Modul je **obyčajný Laravel Service Provider** s popisným súborom `module.json`.

```
modules/Storefront/
  module.json
  src/StorefrontServiceProvider.php     (menný priestor Modules\Storefront\)
  src/Http/...
  routes/web.php                        načíta sa automaticky
  database/migrations/                  štandardné Laravel migrácie, načítajú sa automaticky
  resources/views/                      view('storefront::...'), šablóna ich môže prepísať
  lang/                                 __('storefront::...')
```

### module.json

```json
{
    "name": "blog",
    "version": "1.0.0",
    "title": { "sk": "Blog", "cs": "Blog", "en": "Blog" },
    "description": { "sk": "Články prepojené s produktmi." },
    "provider": "Modules\\Blog\\BlogServiceProvider",
    "namespace": "Modules\\Blog\\",
    "core": false,
    "enabled": true,
    "requires": {
        "eshop": "^0.1",
        "modules": { "catalog": "^1.0" }
    },
    "permissions": ["blog.manage"]
}
```

| Kľúč | Význam |
|---|---|
| `name` | Jedinečný názov (malé písmená, číslice, pomlčky). |
| `version` | Verzia modulu (semver). |
| `provider` | Service Provider modulu. Odporúčame dediť z `App\Core\Modules\ModuleServiceProvider`. |
| `namespace` | Nepovinné. Predvolene `Modules\{NázovAdresára}\` → adresár `src/`. Triedy sa načítajú bez úpravy `composer.json`. |
| `core` | Základný modul: vždy zapnutý, nedá sa vypnúť. |
| `enabled` | Či je modul po inštalácii zapnutý. |
| `requires.eshop` | Podporované verzie e-shopu (semver obmedzenie). |
| `requires.modules` | Závislosti na iných moduloch a ich verziách. |

### Pravidlá

- Moduly sa načítajú v poradí podľa závislostí.
- Modul s chýbajúcou alebo nekompatibilnou závislosťou, s nekompatibilnou verziou e-shopu alebo
  v cyklickej závislosti sa **nenačíta**. Dôvod sa zobrazí v administrácii (Systém → Moduly).
  Moduly, ktoré od neho závisia, sa nenačítajú tiež.
- Modul sa nedá zapnúť bez zapnutých závislostí a nedá sa vypnúť, ak ho potrebuje iný zapnutý modul.
- **Vypnutím modulu sa nemažú dáta.**
- Stav zapnutia je v `storage/app/eshop/modules.json` (nie v databáze), aby sa dal načítať pri štarte.
- Chyba pri registrácii modulu nezhodí obchod: zapíše sa do logu, modul sa označí ako problémový
  a ostatné moduly bežia ďalej.
- **Záchranný režim:** `ESHOP_SAFE_MODE=true` v `.env` načíta iba základné moduly.

> Podrobné spracovanie chýb za behu (dočasná nedostupnosť integrácií, izolácia pri opakovaných chybách)
> pribudne spolu s prvými integráciami (platby, kuriéri). Pozri `PLAN.md`, kapitola 2.6.

## Jazyky, meny a DPH

- **Jazyky obchodu** (`App\Core\Content\ShopLocales`): predvolený jazyk bez predpony, ďalšie s predponou
  (`/en/…`, čeština `/cz/…`). Predponu odstráni globálny middleware `ResolveLocalePrefix` ešte pred výberom
  trasy a pri tvorbe odkazov ju pridá `LocalizedUrls` (`URL::formatPathUsing`) – iba trasám označeným
  middlewarom jazyka obchodu. Moduly tak registrujú trasy iba raz. Odkazy pre iný jazyk:
  `app(LocalizedUrls::class)->in('en', fn () => route(...))`.
- **Preložené adresy:** `HasLocalizedSlug` vytvára adresu v každom jazyku s názvom; chýbajúca adresa sa
  nahradí adresou v predvolenom jazyku. Stránky s vlastnou adresou nastavia `AlternateUrls` (prepínač, hreflang).
- **Preklady v administrácii:** trait `FillsContentTranslations` (prepínač jazyka, ukladanie do zvoleného
  jazyka, tlačidlá strojového prekladu, ak je v kontajneri `MachineTranslator`).
- **Meny** (`App\Core\Support\Currencies`): kurzy a zaokrúhľovanie, `convert()`. Ručná cena v mene má prednosť,
  inak prepočet. Mena zákazníka sa nastavuje iba v obchode (`SetStorefrontLocale`), administrácia pracuje
  v predvolenej mene. Kurzy z ECB (`EcbRates`).
- **DPH** (`Modules\Tax\TaxRules`): `TaxContext` (tuzemsko, OSS, prenesenie daňovej povinnosti) podľa krajiny
  doručenia a IČ DPH overeného vo VIES; `CheckoutTotals` podľa neho upraví ceny a sadzby položiek.

## Šablóny vzhľadu

```
themes/default/
  theme.json        názov, verzia, rodič (parent), kompatibilita
  layouts/          view('theme::layouts.app')
  templates/        view('theme::templates.home')
  partials/
  blocks/
  assets/           CSS a JS (zostavuje Vite pri vytváraní balíka)
  lang/             __('theme::shop.…')
  modules/{modul}/  prepísanie šablón modulu, napr. modules/storefront/…
```

- Aktívna šablóna sa vyberá v administrácii (Nastavenia → Obchod).
- Súbory sa hľadajú v poradí: aktívna šablóna → jej rodič → … → `default`.
  **Odvodená šablóna** tak stačí, keď obsahuje iba zmenené súbory: `"parent": "default"` v `theme.json`.
- Šablóny dostávajú premenné `$shop` (GeneralSettings), `$branding` (BrandingSettings) a `$company`
  (CompanySettings).
- Hlavná farba obchodu je v CSS premennej `--brand-primary` (Tailwind: `bg-primary`, `text-primary`)
  a čitateľná farba textu na nej je v `--brand-primary-foreground`.
- Rozšírenie šablóny bez vlastného systému hákov: Blade `@stack` / `@push` (napr. `head`, `scripts`,
  `header-actions`), komponenty a view composers.

## Oprávnenia

- Laravel Policies a Gates. Rola `super-admin` má všetky oprávnenia (`Gate::before`),
  okrem mazania používateľov, ktoré vždy kontroluje `UserPolicy`:
  nikto nezmaže sám seba ani posledného hlavného administrátora.
- Ďalšie roly a ich oprávnenia pribudnú v ďalších krokoch.
- Zákazníci majú vlastnú tabuľku `customers` a strážcu `customer`; s účtami administrácie sa nemiešajú.
- Dvojfaktorové prihlásenie do administrácie: Filament MFA (overovacia aplikácia, záložné kódy), tajný kľúč šifrovane.

## Prevádzka

- **Zálohy** (`App\Core\Backup`): export databázy v PHP cez PDO (bez `mysqldump`), ZIP s nahranými súbormi
  a nastaveniami do `storage/app/backups`, automaticky denne cez plánovač.
- **Plánovač:** `routes/console.php`; cron `schedule:run` alebo webový cron `/cron/{kľúč}` (kľúč odvodený od `APP_KEY`).
- **Logy a stav systému:** Systém → Logy, Systém → Stav systému (`App\Core\System`).

## Testy

- `tests/Unit/Core` – moduly, šablóny, zápis `.env`, kontrola požiadaviek.
- `tests/Feature` – inštalátor, administrácia, obchod.
- Testy nikdy nezapisujú do skutočného `.env` ani do stavu inštalácie (`tests/TestCase.php`).
- CI: PHP 8.3, 8.4 a 8.5 s MySQL 8.0 pri každej zmene. Denne aj MySQL 8.4, MariaDB 10.6 a 11.4.
