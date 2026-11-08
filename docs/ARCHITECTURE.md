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

## Funkcie obchodu a sklad

- **Prepínače funkcií** (`App\Core\Features\FeatureToggles`): jadro aj moduly registrujú `FeatureToggle`
  (názov, popis, odkaz na nastavenia, závislosti). Stránka Nastavenia → Funkcie obchodu ich zobrazí všetky.
  Katalóg: `Modules\Catalog\CatalogFeatures::enabled('bundles')`. Vypnutie nič nemaže, iba skryje funkciu.
- **Sklad:** celkový stav je v `products.stock_quantity` / `product_variants.stock_quantity` a každá zmena ide
  cez `StockService` (história v `stock_movements`). `StockService::take()` vráti záznam, odkiaľ sa tovar vydal
  (`order_items.allocations`) – balíček zo zložiek, šarže, predobjednané kusy – a `giveBack()` ho pri storne vráti.
- **Sklady a šarže** (`Modules\Catalog\StockLots`): `stock_lots` rozpisuje celkový stav podľa skladu a šarže,
  súčet vždy sedí s celkovým stavom (`reconcile()` dorovná ručné zmeny). Výdaj v poradí skladov a od najskoršej
  expirácie (FEFO); nepredajný tovar (neaktívny sklad, expirácia) odpočíta `sellableStock()`.
- **Predobjednávky:** chýbajúce kusy sa odpíšu do mínusu, príjem tovaru ich pokryje.
- **Digitálne produkty:** súbory na disku `local` (`storage/app/private`), odkazy `download_links`
  s náhodným kľúčom, sťahovanie cez `DownloadController` (žiadne verejné súbory ani symbolické odkazy).

## Šablóny vzhľadu

```
themes/default/
  theme.json        názov, verzia, rodič (parent), kompatibilita, settings, menus, areas, vite/stylesheets
  screenshot.png    náhľad v administrácii
  layouts/          view('theme::layouts.app')
  templates/        view('theme::templates.home')
  partials/
  blocks/           vzhľad blokov: view('theme::blocks.banner')
  assets/           CSS a JS (dodávané šablóny zostavuje Vite, nahraté majú hotové súbory)
  lang/             __('theme::shop.…')
  modules/{modul}/  prepísanie šablón modulu, napr. modules/storefront/…
```

Dodávané šablóny: **Default**, **Fresh** (odvodená, iné rozloženie hlavičky, karty a úvodu) a **Starter**
(kostra odvodenej šablóny s dokumentáciou v `themes/starter/README.md`).

- **Správa** (Obsah → Vzhľad, `App\Filament\Pages\Themes`): aktivácia, náhľad pred aktiváciou iba pre
  administrátora (`?theme_preview=nazov`, middleware `ApplyThemePreview`), nahratie ZIP s kontrolou
  (`ThemePackages`), export ZIP, odstránenie, vytvorenie odvodenej šablóny, kontrola (`ThemeChecker`).
- **Reťazec šablón:** aktívna → rodič → … → `default`. Pohľady (`theme::`) aj preklady (`ThemeTranslationLoader`)
  sa hľadajú v celom reťazci, odvodená šablóna obsahuje iba zmenené súbory.
- **Nastavenia šablóny** (`ThemeOptions`): definície v `theme.json` (`color`, `select`, `toggle`, `text`,
  `number`, `image`), hodnoty v tabuľke `theme_settings` pre každú šablónu; v šablóne `$themeOptions->get()`.
  Prispôsobenie so živým náhľadom (`CustomizeTheme`) ukladá neuložené hodnoty iba do relácie administrátora.
- **Výber šablóny podľa obsahu:** `ThemeManager::template('product', ['product-{adresa}', 'product-type-{id}'])`.
- **Ponuky** (`App\Core\Menus`): miesta určuje `theme.json` (`menus`), položky v `menu_items`, typy odkazov
  (stránka, kategória, produkt) registrujú moduly cez `MenuLinkTypes`; v šablóne `$menus->items('header')`.
- **Bloky** (`App\Core\Blocks\BlockTypes`): typy registrujú jadro (`CoreBlocks`) a moduly (`CatalogBlocks`),
  vzhľad určuje šablóna (`blocks/{typ}.blade.php`). Úvodná stránka a oblasti šablóny (`areas`) v `block_layouts`,
  stránky a kategórie v stĺpci `blocks`; všetko v každom jazyku. Vykreslenie `render_blocks()`, `block_area()`.
- **Súbory šablóny** bez kompilácie a symbolických odkazov: `theme_asset('custom.css')` (trasa `/theme-assets`).
- **Editor súborov** (`ThemeEditor`, `ThemeFiles`): iba hlavný administrátor, predvolene vypnutý,
  `ESHOP_THEME_EDITOR=false` ho vypne úplne; záloha pred každou zmenou, kontrola syntaxe, predvolená šablóna
  sa neupravuje.
- Šablóny dostávajú premenné `$shop`, `$branding`, `$company`, `$themeOptions` a `$menus`.
- Hlavná farba obchodu je v CSS premennej `--brand-primary` (Tailwind: `bg-primary`, `text-primary`).
- Rozšírenie šablóny bez vlastného systému hákov: Blade `@stack` / `@push`, komponenty a view composers.

## Doprava a kuriéri

- **Kuriér** = modul s rozhraním `Modules\Shipping\Carriers\ShippingCarrier` (vytvorenie zásielky, štítky,
  sledovanie, adresa sledovania, test spojenia, výdajné miesta), registrovaný v `CarrierRegistry`.
  Moduly: `Packeta` (REST/XML), `Gls` (MyGLS JSON), `Dpd` (sledovacie číslo), `CeskaPosta` (B2B ZSKService,
  podpis HMAC-SHA256, pobočky Balíkovny z verejného XML čítaného po riadkoch), `SlovenskaPosta` (REST API ePH;
  každá zásielka má vlastný podací hárok, referencia `idHárku/idZásielky`), v jadre `ManualCarrier`.
  `Ppl` (myAPI2, OAuth2, asynchrónne dávky – referencia je ID dávky), `Sps` (WebShip SOAP skladané ako XML
  bez rozšírenia soap), `Balikobot` (agregátor; kuriér a služba v `carrier_options`, referencia `kuriér:package_id`).
  Kuriér, ktorý vie vrátiť štítok iba pre jednu zásielku, označí rozhranie `SeparateLabels` – hromadná tlač
  potom pribalí štítky do ZIP samostatne.
- **Výdajné miesta:** typ dopravy `PickupPoint`. Kuriér určí výber cez `pickupSelector()`: vlastné okno
  (JavaScript `window.eshopPickupWidgets[nazov]`, napr. mapa Packeta) alebo vyhľadávanie v tabuľke
  `pickup_points` (GLS, denná aktualizácia `SyncPickupPoints`). Zvolené miesto je v `orders.pickup_point`.
- **Zásielky** (`shipments`, modul Objednávky): `CreateShipment`, `PrintShipmentLabels` (jeden kuriér = PDF,
  viac = ZIP), `TrackShipments` (webový cron každých 30 minút). Výpadky API sleduje `CarrierHealth`.
- Chyby kuriérov sú `CarrierException` s typom: nastavenie, nedostupná služba, neplatné údaje (kapitola 2.6).

## Blog

Modul `Blog`: `Post` (bloky v každom jazyku, verzie `blog_post_versions`, `preview_token`), kategórie, štítky,
komentáre. Prepojenie s produktmi (`blog_post_product`) a kategóriami katalógu sa zobrazuje cez view composer
v šablónach produktu a kategórie (`$relatedPosts`); záložka pri produkte cez `Modules\Catalog\Filament\ProductRelations`.
Testy štartujú so stavom modulov z `tests/fixtures/state` (zapnutý aj predvolene vypnutý blog).

## Predaj a marketing

- **Ceny pre skupiny:** `App\Core\Pricing\PriceContext` (scoped) určí skupinu zákazníka cez resolver z modulu
  zákazníkov. `Product::regularPriceIn()` použije cenu pre skupinu (`product_group_prices`), inak zľavu skupiny;
  `basePriceIn()` je bežná cena. Množstevné zľavy (`product_quantity_discounts`) uplatní `CartManager`.
- **Kontroly pokladne z iných modulov:** `CheckoutRequest::addCheck($kľúč, fn ($validator, $request) => …)`.
- **Darčekové poukážky** (`Modules\Orders\GiftVouchers`): poukážka je platba – `orders.voucher_amount`,
  suma na úhradu `Order::amountDue()` (QR, dobierka, Stripe, faktúra). Vydanie po `OrderPaid`, vrátenie pri zrušení.
- **Hodnotenia:** modul `Reviews`, súhrn v `products.reviews_count` a `rating_average` (`ReviewStats`), žiadosť
  o hodnotenie plánuje `ScheduleReviewRequest` a posiela `SendReviewRequests` (webový cron, podpísaný odkaz).
- **Marketing:** modul `Marketing` – newsletter (double opt-in, `NewsletterProvider` pre Mailchimp/Ecomail),
  opustené košíky (`carts.email`, `AbandonedCarts`), XML feedy (`ProductFeed`, súbor v `storage/app/private/feeds`
  obnovený raz za hodinu), meracie kódy cez `CookieConsent::addCode()` iba so súhlasom.
- **SEO:** presmerovania `App\Models\Redirect` (obsluha v 404), automaticky pri zmene slugu (`HasLocalizedSlug`
  + `SlugRedirects`), šablóny titulkov `App\Settings\SeoSettings::render()`.

## Rezervácie, dáta a doklady

- **Rezervácie:** modul `Reservations`. Voľné termíny počíta `Availability::slots()` z týždenného rozvrhu zdroja
  (`booking_resources.schedule`), výnimiek (`booking_closures`) a existujúcich rezervácií vrátane prestávky po
  termíne a kapacity. `Bookings::bookService()` overí termín znova v transakcii so zámkom na službe.
  Rezervácia jedinečného kusu blokuje nákup cez `Product::addAvailabilityCheck()`. Pripomienky posiela webový
  cron, kalendár pre obchod je tajná adresa iCal (`ICalendar`).
- **Import a export:** modul `Transfer`. Exporty sú `ExportDefinition` registrované v `Exports` (aj z iných
  modulov), importy sú `Importer` v `Importers`; `ImportRunner` spracúva súbor po častiach v časovom limite
  požiadavky a pokračuje v ďalšej (bez procesov na pozadí). XLSX číta a zapisuje OpenSpout.
- **Účtovné exporty:** modul `Accounting`, rozhranie `AccountingExporter` (Pohoda, Money S3) v `AccountingExporters`.
- **Reklamácie:** modul `Returns` – výsledok vybavenia, protokol v PDF (`ReturnProtocol`), dobropis cez
  `IssueInvoice::creditNote()`, upozornenie pred lehotou (`ReturnDeadlines`).

## Veľkosť obchodu

`App\Core\Features\ShopSize`: predvoľby funkcií pre malý, stredný a veľký obchod. Každý `FeatureToggle`
(`sizes`) a voliteľný modul (`"sizes"` v `module.json`) určí, či sa pri veľkosti zapne alebo vypne; bez
predvoľby je funkcia voliteľná. Zmena veľkosti najprv ukáže prehľad zmien, ručná zmena funkcie = „vlastný“.

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
