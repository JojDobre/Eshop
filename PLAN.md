# Univerzálny e-shop – plán projektu

> **Repozitár:** `JojDobre/Eshop`
> **Stav:** plán schválený (verzia 5, november 2026). Hotové: všetky kroky 0–5 – produkčná verzia 1.0.0 (november 2026). Ďalší vývoj podľa kapitoly 19 (plán po verzii 1.0).

Univerzálny e-shop, ktorý sa dá ponúknuť rôznym predajcom: oblečenie, nábytok, tovar, služby, zvieratá, potraviny, digitálne produkty. **Jedna inštalácia = jeden e-shop** s vlastným názvom, logom, farbami a šablónou. Musí bežať na **lacnom webhostingu**, bez vlastného servera.

---

## Obsah

0. [Ciele a princípy](#0-ciele-a-princípy)
1. [Technológie, hosting a kompatibilita](#1-technológie-hosting-a-kompatibilita)
2. [Architektúra systému](#2-architektúra-systému)
3. [Postup vývoja](#3-postup-vývoja)
4. [Veľkosť obchodu a zapínanie funkcií](#4-veľkosť-obchodu-a-zapínanie-funkcií)
5. [Produkty a katalóg](#5-produkty-a-katalóg)
6. [Jazyky, meny a DPH](#6-jazyky-meny-a-dph)
7. [Šablóny vzhľadu](#7-šablóny-vzhľadu)
8. [Obchod (časť pre zákazníkov)](#8-obchod-časť-pre-zákazníkov)
9. [Blog](#9-blog)
10. [Doprava a kuriéri](#10-doprava-a-kuriéri)
11. [Platby](#11-platby)
12. [Doklady a e-maily](#12-doklady-a-e-maily)
13. [Rezervácie](#13-rezervácie)
14. [Administrácia](#14-administrácia)
15. [Exporty, importy a účtovníctvo](#15-exporty-importy-a-účtovníctvo)
16. [Právne a legislatívne požiadavky](#16-právne-a-legislatívne-požiadavky)
17. [Prevádzka: aktualizácie, zálohy, logy, monitoring](#17-prevádzka-aktualizácie-zálohy-logy-monitoring)
18. [Bezpečnosť, výkon a testovanie](#18-bezpečnosť-výkon-a-testovanie)
19. [Plán po verzii 1.0](#19-plán-po-verzii-10)
20. [Zhrnutie rozhodnutí](#20-zhrnutie-rozhodnutí)

---

## 0. Ciele a princípy

1. **Staviame e-shop, nie framework.** Všade, kde Laravel alebo overený balík má stabilné riešenie, použijeme ho. Vlastnú vrstvu doplníme iba tam, kde to vyžaduje modulárnosť e-shopu.
2. **Priebežne funkčný produkt.** Každý krok vývoja končí niečím, čo sa dá nainštalovať a vyskúšať. Žiadne mesiace budovania infraštruktúry bez použiteľného e-shopu.
3. **Najprv malý obchodník.** Prvý cieľ je verzia, ktorú môže ostro používať malý obchod. Detailnejšie funkcie pribúdajú postupne.
4. **Pripravené na rast, ale nie predčasne.** Databáza a jadro od začiatku počítajú s viacerými jazykmi, menami a modulmi, aby ich neskoršie doplnenie neznamenalo prerábanie. Rozhranie a funkcie sa však stavajú až vtedy, keď na ne príde rad.
5. **Kapitoly 4 až 18 opisujú cieľový rozsah.** Kedy sa ktorá funkcia robí, určuje kapitola 3.

---

## 1. Technológie, hosting a kompatibilita

| Vrstva | Riešenie |
|---|---|
| Jazyk / framework | **PHP a Laravel** |
| Databáza | **MySQL / MariaDB** |
| Administrácia | **Filament** (hotový administračný systém pre Laravel postavený na Livewire) |
| Obchod (frontend) | Šablóny **Blade + Tailwind CSS + Alpine.js** |
| PDF | **dompdf** (čisté PHP, nič navyše netreba inštalovať) |
| E-maily | SMTP, teda e-mailová schránka z webhostingu |
| Úlohy na pozadí | Laravel Queue s ukladaním úloh do databázy. Spúšťa ich cron, a ak hosting cron nemá, spustia sa počas návštev webu |
| Cache | Súbory alebo databáza. **Redis alebo Memcached voliteľne**, ak ich hosting ponúka |
| Úložisko súborov | Úložisko na hostingu. Voliteľne S3, FTP alebo SFTP (cez Laravel Filesystem) |

### Inštalácia ako pri WordPresse

- Na hosting sa nahrá **ZIP** cez FTP. Obsahuje všetky PHP knižnice aj hotové štýly a skripty, takže na hostingu netreba nič kompilovať.
- Funguje aj na hostingoch, kde sa nedá zmeniť koreňový adresár webu (DocumentRoot). Postará sa o to `.htaccess`, ktorý zároveň chráni súbory s nastaveniami.
- **Webový inštalátor:**
  - skontroluje požiadavky
  - opýta sa na prístup k databáze
  - vytvorí administrátora
  - nastaví obchod (názov, krajina, mena, jazyk)
  - voliteľne nahrá ukážkové dáta

### Podporované verzie

| Komponent | Plná podpora | Obmedzená podpora |
|---|---|---|
| PHP | **8.3, 8.4, 8.5** (nové verzie po otestovaní) | – |
| MySQL | **8.0, 8.4** | 5.7 (už sa nevyvíja, funguje, ale neodporúča sa a netestuje sa pravidelne) |
| MariaDB | **10.6, 10.11, 11.4** a novšie | – |
| Webserver | Apache s mod_rewrite, LiteSpeed | Nginx (s dodanou konfiguráciou) |
| Prehliadače | Posledné 2 verzie Chrome, Firefox, Safari, Edge | – |

**Povinné rozšírenia PHP:** `pdo_mysql`, `mbstring`, `openssl`, `intl`, `curl`, `zip`, `fileinfo`, `sodium`, `bcmath`, `xml`, `dom`, `gd` alebo `imagick`.

**Minimálne limity:**
- `memory_limit` 128 MB (odporúčané 256 MB)
- `max_execution_time` 30 s
- `upload_max_filesize` 16 MB

Inštalátor aj stránka „Stav systému" zobrazia, čo je v poriadku a čo treba upraviť.

---

## 2. Architektúra systému

### 2.1 Čo použijeme z Laravelu a čo doplníme

| Potreba | Hotové riešenie (Laravel alebo overený balík) | Naša vrstva navyše |
|---|---|---|
| Udalosti | **Laravel Events a Listeners** | Iba zoznam udalostí e-shopu (`OrderPlaced`, `OrderPaid`, …) a ich dokumentácia |
| Fronta úloh | **Laravel Queue** (ukladanie úloh do databázy) + **Scheduler** | Spúšťanie počas návštev webu, ak hosting nemá cron |
| Migrácie databázy | **Laravel Migrations** | Modul iba zaregistruje svoj adresár s migráciami |
| Oprávnenia | **Policies a Gates** + `spatie/laravel-permission` | Modul zaregistruje zoznam svojich oprávnení |
| Závislosti a služby | **Service Container a Service Providers** | Registrácia implementácií rozhraní (kuriér, platba, účtovníctvo) |
| Úložiská | **Laravel Filesystem** (lokálne, FTP, SFTP, S3) | – |
| Cache, e-maily, notifikácie, kontrola vstupov, preklady, logy, šifrovanie, obmedzenie počtu požiadaviek, plánované úlohy | **Laravel** | – |
| Administrácia | **Filament** | Vlastné obrazovky iba tam, kde Filament nestačí (napr. prispôsobenie vzhľadu) |
| Nastavenia | `spatie/laravel-settings` | Formulár nastavení modulu sa vygeneruje z jeho popisu |
| Preklady obsahu | `spatie/laravel-translatable` | – |
| Obrázky a súbory | `spatie/laravel-medialibrary` | Knižnica súborov v administrácii |
| Záznam zmien | `spatie/laravel-activitylog` | – |
| Zálohy | `spatie/laravel-backup` | Test obnovy, obrazovka v administrácii |
| Stav systému | `spatie/laravel-health` | Vlastné kontroly (cron, kuriéri, platobné brány) |
| Peniaze a meny | `brick/money` | – |
| PDF | `barryvdh/laravel-dompdf` | – |
| API tokeny (po 1.0) | **Laravel Sanctum** | – |

**Pravidlo pre výber balíkov:** používame iba balíky, ktoré sú dlhodobo udržiavané, fungujú na bežnom webhostingu (nepotrebujú žiadne služby, ktoré by bežali stále na pozadí) a nemajú zbytočné závislosti.

### 2.2 Moduly: tenká vlastná vrstva

Toto je jediná väčšia časť, ktorú si napíšeme sami, pretože Laravel ju v takejto podobe nemá.

- Funkcie e-shopu sú rozdelené do **interných modulov**: katalóg, objednávky, blog, rezervácie, každý kuriér, každá platobná brána a pod.
- **Modul je obyčajný Laravel Service Provider** s popisným súborom `module.json`. Ten obsahuje názov, verziu, závislosti na iných moduloch, oprávnenia a popis nastavení.
- **Načítanie modulov (malý vlastný kód):**
  - prečíta `module.json` zapnutých modulov
  - skontroluje závislosti
  - zaregistruje Service Provider modulu
  - vypnutý modul sa vôbec nenačíta
- Modul sa nedá zapnúť, kým nie sú zapnuté moduly, ktoré potrebuje, a nedá sa vypnúť, ak ho iný zapnutý modul potrebuje. Administrácia vtedy vysvetlí, čo treba urobiť.
- Na tom istom mechanizme je postavené zapínanie funkcií podľa veľkosti obchodu (kapitola 4).

```
app/                      # jadro: nastavenia, moduly, spoločné služby
modules/
  Catalog/
    module.json
    src/                  # obchodná logika (modely, akcie, služby)
    Filament/             # obrazovky v administrácii
    Http/                 # kontroléry
    database/migrations/  # štandardné Laravel migrácie
    resources/views/      # predvolené šablóny modulu (téma ich môže prepísať)
    lang/
    tests/
  Shipping/Packeta/
  Payment/Stripe/
  ...
themes/
```

### 2.3 Obchodná logika oddelená od používateľského rozhrania

- Obchodná logika je v **akciách a službách**, napr. `PlaceOrder`, `CalculateCart`, `CreateInvoice`, `ReserveStock`.
- Kontroléry, obrazovky administrácie aj príkazy z príkazového riadku volajú tie isté akcie. Pravidlá (ceny, DPH, sklad, kupóny) sú tak napísané **na jednom mieste** a vo všetkých častiach systému fungujú rovnako.
- **Peniaze** sa počítajú v najmenších jednotkách meny (centy, haliere) cez `brick/money`, aby nevznikali chyby pri zaokrúhľovaní.

### 2.4 Jednotné rozhrania pre poskytovateľov

Ide o obyčajné PHP rozhrania, ktorých implementácie sa registrujú cez Service Container.

| Rozhranie | Metódy (zjednodušene) | Implementácie |
|---|---|---|
| `PaymentGateway` | vytvoriť platbu, spracovať webhook, vrátiť peniaze, stav platby | Stripe, prevod, dobierka, hotovosť; neskôr GoPay, Comgate |
| `ShippingCarrier` | cena dopravy, výdajné miesta, vytvoriť zásielku, štítok, sledovanie, zrušenie | Packeta, GLS, DPD, osobný odber, vlastná doprava |
| `AccountingExporter` | export dokladov, objednávok a skladu za obdobie | Pohoda, Money S3 |
| `TranslationProvider` | preložiť text | DeepL, Google Translate |
| `ExchangeRateProvider` | aktuálne kurzy | ECB, ručné zadanie |

Nový kuriér alebo platobná brána = nový modul, ktorý implementuje rozhranie. Pokladňa, objednávky a administrácia ho začnú používať automaticky.

### 2.5 Rozšíriteľnosť šablón bez vlastného systému hákov

Vlastný systém hákov (ako vo WordPresse) **nerobíme**. Šablóny a moduly sa rozširujú štandardnými nástrojmi:

- **Blade komponenty, `@stack` a `@push`:** modul môže vložiť obsah do pripraveného miesta v šablóne, napr. „pod cenou produktu"
- **View composers:** modul doplní dáta do šablóny
- **Laravel Events:** reakcia na dianie v systéme (objednávka, platba, zmena stavu)
- **Prepisovanie súborov šablóny:** pozri kapitolu 7

### 2.6 Spracovanie chýb modulov a integrácií

Jedna výnimka nesmie vypnúť celý modul. Postup:

1. **Zachytenie a záznam:** každá chyba sa zapíše do logu aj s informáciou, z ktorého modulu pochádza.
2. **Rozlíšenie typu chyby:**

   | Typ | Príklady | Reakcia |
   |---|---|---|
   | **Infraštruktúra** | výpadok databázy, plný disk, vypršaný časový limit | Úloha na pozadí sa zopakuje s postupne dlhšími prestávkami. Modul sa nepovažuje za chybný. |
   | **Externá služba** | API kuriéra alebo platobnej brány neodpovedá, vracia chybu 5xx, obmedzuje počet požiadaviek | Opakovanie s prestávkami. Pri opakovaných zlyhaniach sa integrácia **dočasne označí ako nedostupná** (napr. kuriér sa prestane ponúkať v pokladni) a po čase sa automaticky skúsi znova. Keď služba začne fungovať, integrácia sa sama obnoví. |
   | **Konfigurácia** | neplatný API kľúč, chýbajúce nastavenie | Upozornenie administrátorovi s návodom, čo opraviť. |
   | **Chyba v kóde modulu** | neočakávaná výnimka v module | Záznam a upozornenie. |

3. **Izolácia pri opakovaných kritických chybách:** ak sa rovnaká kritická chyba modulu opakuje (napr. 10-krát za 5 minút, limit sa dá nastaviť), **vypne sa iba dotknutá časť**: blok sa nezobrazí alebo sa kuriér neponúkne. Zvyšok obchodu funguje ďalej a rozpracovaná objednávka sa nepreruší.
4. **Upozornenie administrátora:** e-mailom a na dashboarde, aj s popisom chyby a odkazom do logu.
5. **Ručné ovládanie:** administrátor môže modul alebo integráciu ručne deaktivovať, znova aktivovať a vynulovať počítadlo chýb.
6. **Záchranný režim:** ak sa aplikácia kvôli modulu vôbec nespustí, dá sa otvoriť v záchrannom režime (tajná adresa alebo prepínač v konfigurácii), v ktorom beží iba jadro a administrácia. Problémový modul sa tak dá vypnúť.

### 2.7 API: presné vymedzenie

| | Interné JSON endpointy | Verejné REST API |
|---|---|---|
| **Kedy** | Od prvého funkčného obchodu | Po verzii 1.0 |
| **Na čo** | Iba pre vlastnú šablónu obchodu: košík, našepkávanie, výdajné miesta, prepočet dopravy | Pre externé systémy: ERP, sklady, mobilné aplikácie, integrácie |
| **Prihlásenie** | Session a CSRF ochrana, rovnako ako bežné stránky | Tokeny (Sanctum) s obmedzenými oprávneniami a obmedzením počtu požiadaviek |
| **Adresa** | `/_shop/...` | `/api/v1/...` |
| **Stabilita** | Bez záruky, môže sa zmeniť s ktoroukoľvek verziou | Verzované. Spätná kompatibilita v rámci `v1`, staršia verzia funguje po prechodné obdobie |
| **Dokumentácia** | Iba pre tvorcov šablón | Verejná dokumentácia (OpenAPI) |

### 2.8 Verzovanie a dokumentácia

- Celý systém sa verzuje **sémanticky** (napr. 1.4.2) a každá verzia má prehľad zmien.
- Rozhrania z kapitoly 2.4, udalosti a pravidlá pre šablóny sú **od verzie 1.0 stabilné v rámci hlavnej verzie**. Samostatné verzovanie interného API nerobíme.
- **Dokumentácia pre vývojárov:**
  - architektúra
  - ako vytvoriť modul a šablónu
  - zoznam rozhraní, udalostí a miest v šablóne, kam sa dá vkladať obsah
  - neskôr dokumentácia REST API

### 2.9 Pluginy tretích strán

**Odkladáme ich až za verziu 1.0** (kapitola 19). Interné moduly, ktoré píšeme a testujeme sami, sú niečo iné ako otvorený ekosystém cudzích PHP rozšírení. Ten si vyžaduje kontrolu kódu, podpisovanie, izoláciu a podporu. Architektúra modulov je však navrhnutá tak, aby sa naň dalo neskôr nadviazať.

---

## 3. Postup vývoja

Vývoj je rozdelený do piatich krokov. Po každom kroku existuje funkčný celok, ktorý sa dá nainštalovať a vyskúšať. **Cieľ kroku 3 je verzia, ktorú môže ostro používať malý obchodník.**

### Krok 1 – Technický prototyp

**Cieľ:** overiť, že základ funguje na bežnom webhostingu.

- Projekt Laravel + Filament, štruktúra modulov a načítanie modulov
- Návrh databázy: stĺpce na preklady a ceny s menou sú pripravené od začiatku, hoci rozhranie zatiaľ používa jeden jazyk a jednu menu
- Webový inštalátor a zostavenie inštalačného ZIP súboru
- Prihlásenie do administrácie, administrátor a základné nastavenia (názov obchodu, logo, farba, firemné údaje)
- Jednoduchá šablóna obchodu (hlavička, päta, úvodná stránka)
- Automatické testy pri každej zmene kódu (GitHub Actions)
- **Overenie inštalácie na reálnom lacnom webhostingu**

**Výstup:** nainštalovaný prázdny obchod a funkčná administrácia.

### Krok 2 – Prvý funkčný obchod

**Cieľ:** dá sa prijať objednávka od začiatku do konca. Jeden jazyk (SK), jedna mena (EUR).

- Kategórie a jednoduché produkty (názov, popis, obrázky, cena, počet kusov na sklade)
- Výpis kategórie, detail produktu, jednoduché vyhľadávanie
- Košík a pokladňa bez registrácie
- Doprava s pevnou cenou a osobný odber
- Platba prevodom, dobierkou a v hotovosti pri osobnom odbere
- Objednávky v administrácii so stavmi a poznámkami
- Potvrdzovací e-mail zákazníkovi a upozornenie obchodníkovi
- Statické stránky (obchodné podmienky, kontakt)
- Základné SEO (pekné adresy, meta údaje, sitemap)

**Výstup:** demo obchod, ktorý sa dá ukázať a testovať.

### Krok 3 – Plnohodnotný nákupný systém

**Cieľ:** **pilotná verzia (0.5) pre prvého malého obchodníka.** Obsahuje všetko, čo malý obchod potrebuje na ostrú a legálnu prevádzku.

- **Katalóg:**
  - varianty (veľkosť × farba) s **obrázkami pre každú kombináciu**
  - **vlastné polia pre produkty** (typy, kontrola hodnôt, filtre)
  - výrobcovia
- **Knižnica súborov:** centrálna správa obrázkov a dokumentov
- **Sklad:** množstvá pri variantoch, odpočet pri objednávke, upozornenie na nízky stav, ručné úpravy so záznamom
- **DPH:** sadzby, ceny s DPH aj bez DPH
- **Platby:** Stripe (karta, Apple Pay, Google Pay), QR kód pri prevode
- **Doprava:** pravidlá podľa hmotnosti a sumy, doprava zadarmo od určitej sumy
- **Doklady:** PDF faktúra, zálohová faktúra a dobropis s číselnými radmi, odosielané e-mailom
- **E-maily:** šablóny e-mailov upraviteľné v administrácii
- **Zákaznícky účet:** objednávky, faktúry, adresy
- **Kupóny** (percentuálne, pevná suma, doprava zadarmo)
- **Právne minimum:**
  - verzie obchodných podmienok a záznam súhlasu
  - najnižšia cena za 30 dní pri zľave
  - cookie lišta
  - údaje o výrobcovi (GPSR)
  - evidencia odstúpení od zmluvy a reklamácií s lehotami
- **Prevádzkové minimum:**
  - automatické zálohy
  - logy
  - stránka „Stav systému"
  - 2FA pre administrátorov
  - základné bezpečnostné opatrenia
- **Jednoduchý dashboard** (tržby, objednávky, nízky sklad)

**Výstup:** verzia 0.5, ktorá sa nasadí u prvého reálneho malého obchodníka. Jeho spätná väzba určí priority ďalšieho vývoja.

### Krok 4 – Univerzálnosť

**Cieľ:** z obchodu pre jedného malého predajcu spraviť univerzálny produkt. Krok sa robí po menších častiach, z ktorých každá sa vydá samostatne (0.6, 0.7, …), v poradí podľa spätnej väzby.

| Časť | Obsah |
|---|---|
| **4a – Jazyky a meny** | SK, CZ, EN a ďalšie, preklady všetkého obsahu, **preklady cez externé API**, meny s ručnou cenou na produkte, kurzy, DPH podľa krajín, OSS |
| **4b – Typy produktov a sklad** | Typy produktov, vlastné polia aj pre zákazníkov, objednávky a ďalšie objekty, **predobjednávky**, **šarže**, **expirácia**, viac skladov, balíčky, doplnkové voľby |
| **4c – Moduly a šablóny** | Veľkosť obchodu a zapínanie funkcií, kompletný systém šablón (odvodené šablóny, prispôsobenie so živým náhľadom, bloky, nahratie ZIP), druhá šablóna a šablóna „starter" |
| **4d – Doprava a obsah** | Kuriéri Packeta, GLS, DPD, Česká a Slovenská pošta, blog s prepojením na produkty |
| **4e – Predaj a marketing** | Zákaznícke skupiny a B2B ceny, poukážky, hodnotenia, porovnávač 2.0, newsletter, opustené košíky, XML feedy, meracie kódy, pokročilé SEO |
| **4f – Rezervácie, dáta a doklady** | Rezervácie, import a export, Pohoda a Money S3, vrátenie tovaru a reklamácie s protokolmi |

### Krok 5 – Produkčná verzia 1.0

**Cieľ:** stabilná verzia, ktorá sa dá bezpečne ponúkať rôznym obchodníkom.

- **Bezpečnosť:** bezpečnostný audit (kontrola podľa OWASP, závislosti, nahrávanie súborov, oprávnenia)
- **Aktualizácie:** podpísané aktualizácie s možnosťou vrátenia, otestované prechody zo všetkých vydaných verzií
- **Zálohy:** externé úložisko, test obnovy, obnova na čistom hostingu
- **Legislatíva:** automatické mazanie údajov podľa GDPR, evidencia EPR, kontrola prístupnosti a vyhlásenie o prístupnosti
- **Testovanie:**
  - kompletné E2E testy
  - záťažové testy podľa kapitoly 18
  - testovanie na rôznych webhostingoch (napr. WebSupport, Wedos, Active24, Forpsi, Hostinger)
  - testovanie inštalátora na čistom hostingu
  - kontrola kompatibility šablón a modulov
- **Dokumentácia** pre obchodníkov a vývojárov
- **Nasadenie u ďalších reálnych obchodníkov** rôznych typov (napr. potraviny, móda, služby)

**Výstup:** verzia 1.0. Funkcie po verzii 1.0 sú v kapitole 19.

### Prehľad

| Krok | Verzia | Kto to môže používať |
|---|---|---|
| 1 – Technický prototyp | 0.1 | Vývoj, overenie hostingu |
| 2 – Prvý funkčný obchod | 0.2 | Demo, interné testovanie |
| 3 – Plnohodnotný nákupný systém | **0.5** | **Prvý reálny malý obchodník** |
| 4 – Univerzálnosť | 0.6 – 0.9 | Pilotní obchodníci rôznych typov |
| 5 – Produkčná verzia | **1.0** | Všetci obchodníci |
| Po 1.0 | 1.x | Pokročilé funkcie podľa dopytu |

Po každej menšej časti práce spravím commit a push do `JojDobre/Eshop`.

---

## 4. Veľkosť obchodu a zapínanie funkcií

*Kedy: krok 4c. Do tej doby sú zapnuté funkcie malého obchodu.*

Obchod s 20 produktmi a 100 zákazníkmi nepotrebuje sklady, roly ani účtovné exporty. Obchodník preto v inštalátore aj neskôr v nastaveniach vyberie **veľkosť obchodu**. Podľa nej sa zapnú alebo vypnú funkcie a zjednoduší sa administrácia.

| Funkcia / modul | Malý | Stredný | Veľký |
|---|:---:|:---:|:---:|
| Produkty, varianty, košík, pokladňa, objednávky, doklady | ✅ | ✅ | ✅ |
| Platby, základná doprava, DPH, kupóny, zákaznícky účet | ✅ | ✅ | ✅ |
| Viac jazykov a mien | voliteľné | ✅ | ✅ |
| Kuriéri s API (Packeta, GLS, DPD) | voliteľné | ✅ | ✅ |
| Blog, hodnotenia | voliteľné | ✅ | ✅ |
| Darčekové poukážky, newsletter, opustené košíky | – | ✅ | ✅ |
| Porovnávač produktov | – | voliteľné | ✅ |
| XML feedy (Heureka, Google, Facebook) | – | ✅ | ✅ |
| Predobjednávky | voliteľné | voliteľné | ✅ |
| Šarže a expirácia | voliteľné | voliteľné | voliteľné |
| Viac skladov, inventúry | – | – | ✅ |
| Zákaznícke skupiny a B2B ceny | – | voliteľné | ✅ |
| Roly a podrobné oprávnenia | – | ✅ | ✅ |
| Import a export | základný | ✅ | ✅ |
| Účtovné exporty (Pohoda, Money S3) | – | voliteľné | ✅ |
| Rezervácie | voliteľné | voliteľné | voliteľné |

**Pravidlá:**

- Veľkosť obchodu je iba **predvolený výber**. Na stránke **Nastavenia → Funkcie** sa dá každá funkcia samostatne zapnúť alebo vypnúť. Pri vlastnej kombinácii sa obchod označí ako „Vlastný".
- Pri zmene veľkosti obchodu sa najprv zobrazí **prehľad, čo sa zapne a čo vypne**. Až potom sa zmena uloží.
- **Vypnutím funkcie sa nemažú dáta.** Funkcia sa iba skryje z administrácie a obchodu. Po opätovnom zapnutí je všetko späť.
- Pri zapínaní sa zohľadňujú závislosti medzi modulmi (kapitola 2.2).
- Pri každej funkcii je krátky popis, aby obchodník vedel, čo zapína.

---

## 5. Produkty a katalóg

### 5.1 Typy produktov

*Kedy: krok 4b. Dovtedy existuje jeden všeobecný typ produktu s vlastnými poliami.*

Typ produktu určuje, aké vlastné polia a funkcie má produkt. Predpripravené typy:

| Typ | Špecifické funkcie |
|---|---|
| **Oblečenie / obuv** | Varianty (veľkosť × farba), tabuľka veľkostí, obrázky podľa farby |
| **Nábytok / tovar** | Rozmery, hmotnosť, materiál, nadrozmerná doprava, montáž ako príplatok |
| **Potraviny** | Predaj na kusy, kg alebo litre, **cena za mernú jednotku**, alergény, nutričné hodnoty, zloženie, **šarže a expirácia** |
| **Kozmetika / drogéria** | Zloženie, objem, **šarže a expirácia** |
| **Zvieratá** | Jedinečný kus (počet 1), plemeno, vek, pohlavie, očkovanie, čip, rodokmeň. Namiesto okamžitého nákupu sa dá nastaviť **rezervácia** |
| **Služby** | Bez dopravy. Voliteľne **rezervácia termínu** (kapitola 13) |
| **Digitálne produkty** | Súbor na stiahnutie po zaplatení, obmedzený počet stiahnutí a platnosť odkazu |

### 5.2 Vlastné polia

*Kedy: polia pre produkty v kroku 3, pre ostatné objekty a s prekladmi v kroku 4b.*

Univerzálny e-shop nikdy nepokryje potreby všetkých obchodníkov vopred. Jeden potrebuje materiál, druhý rozmery, tretí výkon, štvrtý pôvod produktu. **Vlastné polia** to riešia bez úpravy jadra. Sú zároveň jediným mechanizmom pre „vlastnosti produktov": vlastnosť je vlastné pole s príznakom „filtrovateľné".

- **Kde sa dajú použiť:** produkty, varianty, kategórie, výrobcovia, zákazníci, objednávky, články
- **Typy hodnôt:**
  - krátky text, dlhý text alebo formátovaný text
  - celé alebo desatinné číslo **s jednotkou** (cm, kg, W, …), rozsah od–do
  - áno/nie, výber z možností, viacnásobný výber
  - dátum, dátum a čas
  - farba, URL, e-mail
  - súbor alebo obrázok z knižnice súborov
- **Kontrola hodnôt:** povinné pole, minimum a maximum, dĺžka, formát (regulárny výraz), jedinečnosť
- **Preklady:** pole môže mať hodnotu v každom jazyku. Možnosti výberu sa prekladajú tiež.
- **Filtre:** filtrovateľné pole sa automaticky zobrazí ako filter v kategórii (zaškrtávacie políčka, posuvník rozsahu, výber farby) a dá sa použiť aj v porovnávači.
- **Kde sa zobrazujú:**
  - administrácia (skupina polí, záložka, poradie, nápoveda)
  - detail produktu a karta produktu v obchode
  - pokladňa: pole, ktoré vyplní zákazník, napr. „Text na tortu" alebo „Želaný dátum doručenia"
  - faktúry, e-maily, exporty, importy a feedy
- **Sady polí pre typy produktov:** typ produktu určuje, ktoré polia produkt má
- **Technicky:** hodnoty sú uložené v samostatnej tabuľke so stĺpcami podľa typu hodnoty a s indexmi, aby filtre zostali rýchle aj pri tisícoch produktov

### 5.3 Varianty a obrázky variantov

- Varianty z kombinácií vlastností (napr. veľkosť × farba), automatické vygenerovanie kombinácií
- Každý variant môže mať vlastnú cenu, SKU, EAN, hmotnosť, sklad a stav
- **Obrázky pre každú kombináciu variantov**, alebo pre jednu hodnotu (napr. všetky veľkosti v červenej farbe zdieľajú fotky). Galéria na stránke produktu sa po výbere variantu prepne.

### 5.4 Sklad, predobjednávky, šarže a expirácia

**Sklad** (*krok 3, viac skladov v kroku 4b*)
- Množstvá pri produktoch a variantoch, rezervácia tovaru počas platby, upozornenie na nízky stav
- Pohyby na sklade so záznamom: príjem s nákupnou cenou, výdaj, úprava, neskôr presun a inventúra
- Viac skladov alebo predajní

**Predobjednávky** (*krok 4b*)
- Predaj produktu ešte pred naskladnením, s **predpokladaným dátumom dostupnosti** zobrazeným na webe
- Limit počtu kusov na predobjednávku
- Platba vopred, záloha alebo platba až pri naskladnení
- Ak objednávka obsahuje bežný tovar aj predobjednávku, zákazník si vyberie, či sa má odoslať naraz, alebo v dvoch zásielkach
- Pri naskladnení sa objednávky automaticky uvoľnia na vybavenie v poradí, v akom prišli, a zákazník dostane e-mail

**Šarže** (*krok 4b*)
- Príjem tovaru po šaržiach: číslo šarže, dátum výroby, dátum spotreby alebo minimálnej trvanlivosti, dodávateľ, sklad
- Výdaj podľa pravidla **„najskôr exspiruje, najskôr sa vydá"**, alebo ručný výber šarže
- **Spätné dohľadanie:** ku každej objednávke sa ukladá, z ktorej šarže tovar pochádzal. Pri stiahnutí produktu z trhu sa tak dá rýchlo zistiť, ktorým zákazníkom šarža išla.
- Číslo šarže sa dá vytlačiť na dodací list

**Expirácia** (*krok 4b*)
- Dátum spotreby alebo minimálnej trvanlivosti pri šarži
- Upozornenie X dní pred expiráciou
- Voliteľne automatická zľava alebo skrytie tovaru s blížiacou sa expiráciou
- Na webe sa môže zobraziť „trvanlivosť minimálne do …"
- Prehľad tovaru s blížiacou sa expiráciou a odpis expirovaného tovaru

### 5.5 Knižnica súborov

*Kedy: krok 3.*

- **Centrálna knižnica obrázkov a dokumentov** (ako v knižnici médií vo WordPresse)
- Priečinky, štítky, vyhľadávanie, nahrávanie viacerých súborov presunutím myšou
- Jeden súbor sa dá použiť v produktoch, článkoch, stránkach aj blokoch
- **Alternatívne texty** v každom jazyku (kvôli prístupnosti)
- Úprava obrázka: orezanie, otočenie, bod záujmu pre automatické orezávanie
- Automatické zmenšenie a formát WebP
- **Dokumenty** (návody, certifikáty, karty bezpečnostných údajov), ktoré sa dajú priradiť k produktom (GPSR)
- Pri každom súbore vidno, kde je použitý, a dajú sa vyhľadať a zmazať nepoužívané súbory
- Prehľad zabraného miesta na disku

### 5.6 Ďalšie možnosti katalógu

- **Doplnkové voľby k produktu:** darčekové balenie, gravírovanie a pod., s príplatkom
- Súvisiace produkty, príslušenstvo a alternatívy
- **Balíčky:** viac produktov spolu za zvýhodnenú cenu
- **Nákupná cena** pri produkte aj variante
- **Výrobcovia** s údajmi pre GPSR, **údaje o obaloch** pre EPR (kapitola 16)

---

## 6. Jazyky, meny a DPH

*Kedy: jeden jazyk, jedna mena a DPH v krokoch 2–3. Viac jazykov a mien v kroku 4a. Databáza s nimi počíta od kroku 1.*

### Jazyky

- **SK, CZ, EN** sú predpripravené. Ďalšie jazyky sa dajú pridať v administrácii.
- **Preložiť sa dá všetko:** produkty, kategórie, vlastné polia, články, stránky, ponuky, doprava, platby, e-maily, doklady, právne texty a texty šablóny.
- V administrácii sú jazyky ako záložky a pri každej položke vidno, čo ešte chýba preložiť.
- Adresy podľa jazyka (`/sk/...`, `/cz/...`, `/en/...`), preložené adresy jednotlivých stránok a označenie jazykových verzií pre Google (`hreflang`).

### Preklady cez externé API

- Napojenie na **DeepL** a **Google Translate** cez rozhranie `TranslationProvider`. API kľúč sa vloží v nastaveniach.
- Tlačidlo **„Preložiť"** pri poli alebo celom produkte a **hromadný preklad** všetkého, čo chýba (beží na pozadí)
- **Slovník pojmov** (napr. názvy značiek a odborné výrazy sa neprekladajú alebo sa prekladajú vždy rovnako)
- Strojový preklad sa označí ako **„na kontrolu"**, kým ho niekto neschváli
- Odhad počtu znakov pred hromadným prekladom a mesačný limit, aby sa neminulo viac, ako obchodník chce

### Meny

- **EUR, CZK, USD, GBP** sú predpripravené. Ďalšie meny sa dajú pridať.
- **Na každom produkte aj variante sa dá zadať cena ručne v každej mene.** Ak cena v nejakej mene chýba, prepočíta sa kurzom.
- Kurz zadáte ručne, alebo sa každý deň automaticky stiahne z ECB (Európska centrálna banka).
- Pre každú menu sa nastavuje zaokrúhľovanie: na celé koruny, na „,99" alebo podľa matematických pravidiel.
- Aj doprava, platby, kupóny, poukážky a hranica pre dopravu zadarmo majú hodnoty pre každú menu.
- Objednávka si **pamätá menu a kurz z času nákupu**.

### DPH

- Sadzby DPH podľa krajín a skupín produktov. **Sadzby sa dajú upraviť v administrácii.**
- Ceny sa zadávajú s DPH alebo bez DPH, podľa nastavenia
- **Prenesenie daňovej povinnosti:** firma z inej krajiny EÚ s platným IČ DPH nakupuje bez DPH. IČ DPH sa overí v databáze EÚ (VIES).
- Podpora režimu OSS: DPH podľa krajiny zákazníka

---

## 7. Šablóny vzhľadu

*Kedy: jednoduchá šablóna v kroku 1, šablóna Default s prepisovaním súborov v krokoch 2–3, kompletný systém šablón v kroku 4c.*

Celá zákaznícka časť je **šablóna**, ktorú možno vymeniť, pridať alebo upraviť bez zásahu do jadra systému. Šablóny sú postavené na štandardných nástrojoch Laravelu (Blade, komponenty, `@stack`, view composers), nie na vlastnom systéme hákov.

### Štruktúra šablóny

```
themes/
  default/
    theme.json          # názov, verzia, autor, náhľad, kompatibilita s jadrom, nastavenia šablóny
    screenshot.webp
    layouts/            # základné rozloženia stránok
    templates/          # stránky: home, category, product, cart, checkout, blog, compare, …
    partials/           # časti stránky: hlavička, päta, karta produktu, …
    blocks/             # bloky pre skladanie stránok
    assets/             # CSS, JS, obrázky, písma
    lang/               # preklady textov šablóny
```

### Čo šablóny dokážu

- **Vlastné nastavenia šablóny** definované v `theme.json`: farby, písma, logo, rozloženie hlavičky, počet produktov v rade, štýl tlačidiel a pod.
- **Odvodená šablóna (child theme):** upraví len niekoľko súborov a všetko ostatné preberá z rodičovskej šablóny. Úpravy tak neprepíše aktualizácia rodičovskej šablóny.
- **Prepísanie šablóny modulu:** šablóna môže nahradiť ľubovoľnú predvolenú šablónu modulu vlastným súborom s rovnakou cestou.
- **Výber šablóny podľa obsahu:** napr. `product-food`, potom `product`, alebo `category-{slug}`, potom `category`.
- **Oblasti na bloky** a **miesta na ponuky (menu)** definované šablónou. Obsah sa do nich vkladá v administrácii.
- **Kompatibilita:** šablóna v `theme.json` uvádza, s ktorými verziami jadra funguje.

### Správa šablón v administrácii

- Zoznam nainštalovaných šablón s náhľadmi
- **Nahratie šablóny ako ZIP**, aktivácia, odstránenie, export šablóny do ZIP
- **Prispôsobenie vzhľadu so živým náhľadom:** vľavo nastavenia, vpravo stránka, ktorá sa mení hneď pri úprave
- **Náhľad šablóny pred aktiváciou:** šablónu vidí iba administrátor
- **Editor súborov šablóny:**
  - pred každou zmenou sa uloží záloha a zmena sa dá vrátiť
  - môže ho používať iba hlavný administrátor
  - dá sa úplne vypnúť
- **Skladanie stránok z blokov:** úvodná stránka, statické stránky aj kategórie. Bloky:
  - banner alebo slider
  - produkty (výber, kategória, akcie, novinky), kategórie, články
  - text, obrázok, video
  - výhody obchodu, newsletter
  - vlastný HTML kód
- **Kontrola šablóny:** základné pravidlá prístupnosti a povinné súčasti (cookie lišta, právne odkazy)

### Dodané šablóny

1. **Default:** minimalistická, nadčasová, so svetlým aj tmavým režimom. Hodí sa pre každý typ obchodu.
2. **Druhá šablóna s iným rozložením** (napr. pre potraviny alebo módu)
3. **Šablóna „starter"** pre vývojárov: minimálna kostra a dokumentácia

> Šablóny obsahujú PHP kód (Blade), preto treba nahrávať iba šablóny z dôveryhodných zdrojov. Pri nahrávaní sa zobrazí upozornenie.

---

## 8. Obchod (časť pre zákazníkov)

- Úvodná stránka poskladaná z blokov
- Katalóg s filtrami z vlastných polí, ceny, značky a dostupnosti, triedenie, stránkovanie, rýchly náhľad produktu
- **Vyhľadávanie s našepkávaním**, ktoré hľadá v produktoch, kategóriách aj článkoch a zvláda aj slová bez diakritiky
- **Detail produktu:**
  - galéria, ktorá sa mení podľa vybraného variantu
  - doplnkové voľby
  - sklad, predpokladaný dátum doručenia, označenie predobjednávky
  - parametre, hodnotenia
  - informácie o výrobcovi a bezpečnosti produktu
  - súvisiace produkty a **súvisiace články**
- **Košík:** vysúvací panel aj samostatná stránka, kupóny, poukážky, ukazovateľ „do dopravy zadarmo chýba X"
- **Pokladňa na jednej stránke:**
  - nákup aj bez registrácie, firemné údaje (IČO, DIČ, IČ DPH)
  - doprava s **výberom výdajného miesta na mape**, platba
  - vlastné polia objednávky, poznámka, súhlasy
  - tlačidlo „Objednávka zaväzujúca k platbe"
- **Zákaznícky účet:**
  - objednávky a ich sledovanie, faktúry
  - adresy, obľúbené produkty
  - vrátenie tovaru a reklamácie online, rezervácie
  - digitálne produkty na stiahnutie
  - export vlastných údajov a žiadosť o zrušenie účtu
- Zákazník bez registrácie sleduje objednávku cez odkaz z e-mailu
- Upozornenie „dajte mi vedieť, keď bude tovar naskladnený", naposledy prezerané produkty

### Porovnávač produktov 2.0

*Kedy: krok 4e.*

- Porovnanie **vedľa seba**: na počítači do 4 produktov, na mobile 2 produkty s posúvaním
- **Zvýraznenie rozdielov** a prepínač „Zobraziť iba rozdiely"
- **Výber vlastností**, ktoré chce zákazník porovnávať, zoskupených podľa skupín
- Zvýraznenie najlepšej číselnej hodnoty (čo je „najlepšie", sa nastavuje pri vlastnom poli)
- Hlavička s cenou a tlačidlom „Do košíka" zostáva pri posúvaní viditeľná
- Produkty rôznych typov sa rozdelia do skupín
- Zdieľanie porovnania cez odkaz a tlač

### SEO, prístupnosť a výkon

- **SEO:** pekné adresy, meta údaje, sitemap, štruktúrované dáta pre Google, Open Graph, kanonické adresy, presmerovania 301
- **Prístupnosť:** WCAG 2.1 AA (kapitola 16.7)
- **Výkon:** lenivé načítanie obrázkov, formát WebP, cache stránok aj dotazov do databázy

---

## 9. Blog

*Kedy: krok 4d.*

- Články s **blokovým editorom**: text, nadpisy, obrázok, galéria, video, citát, tabuľka, výzva s tlačidlom, **vložený produkt alebo karta produktov**
- Kategórie, štítky, autori, titulný obrázok, čas čítania, obsah článku generovaný z nadpisov
- **Prepojenie s produktmi oboma smermi:**
  - článok → odporúčané produkty (karta s cenou a tlačidlom „Do košíka")
  - produkt → súvisiace články („Ako vybrať…", recepty, návody)
  - prepojenie sa nastavuje z článku aj z produktu a dá sa prepájať aj s kategóriami
- Koncepty, **plánované publikovanie**, náhľad pred zverejnením, história verzií
- Preklady do všetkých jazykov
- Komentáre so schvaľovaním a ochranou proti spamu (dajú sa vypnúť)
- SEO polia, RSS
- Bloky „Najnovšie" a „Súvisiace články" pre úvodnú stránku, kategórie a produkty

---

## 10. Doprava a kuriéri

*Kedy: pevná cena a osobný odber v kroku 2, pravidlá v kroku 3, kuriéri s API v kroku 4d.*

Každý kuriér je modul s rozhraním `ShippingCarrier`. V administrácii stačí **vložiť API kľúče**, zapnúť modul a **otestovať spojenie**.

| Kuriér | Funkcie |
|---|---|
| **Packeta** | Mapa výdajných miest a Z-BOXov, doručenie domov, vytvorenie zásielky, štítky v PDF, sledovanie zásielky, dobierka |
| **GLS** (MyGLS API) | Doručenie domov, ParcelShopy a GLS boxy, štítky, sledovanie, dobierka |
| **Česká pošta** (B2B API) | Balík Do ruky, Balíkovna (pošty, partneri, BOXy), štítky, sledovanie, dobierka |
| **Slovenská pošta** (API ePH) | Balík a Expres kuriér na adresu, štítky, odkaz na sledovanie, dobierka |
| **PPL** (myAPI2) | PPL Parcel na adresu, ParcelShopy a ParcelBoxy, štítky, sledovanie, dobierka |
| **SPS** (WebShip) | Kuriér na adresu, štítky, sledovanie, dobierka |
| **Balíkobot** | DPD, Geis, Toptrans, WE\|DO, 123kuriér a ďalší cez jedno napojenie |
| **DPD** | Doručenie domov, Pickup body a boxy, štítky, sledovanie, dobierka |
| **Vlastná doprava** | Osobný odber (viac predajní s otváracími hodinami), vlastný rozvoz (podľa PSČ), kuriér bez napojenia |

- Cena podľa hmotnosti, sumy objednávky, krajiny, meny a počtu balíkov
- Evidencia skutočných **nákladov na dopravu** (pre analytiku)
- Doprava zadarmo od určitej sumy
- Obmedzenie dopravy na typy produktov alebo kategórie (napr. zvieratá iba osobne)
- **Hromadné vytvorenie zásielok a tlač štítkov** naraz
- Sledovacie číslo sa automaticky pošle zákazníkovi. Stav zásielky sa pravidelne kontroluje a objednávka sa sama označí ako doručená.
- Pri výpadku API kuriéra sa postupuje podľa kapitoly 2.6
- Ďalší kuriéri sa dajú doplniť ako nové moduly

---

## 11. Platby

*Kedy: prevod, dobierka a hotovosť v kroku 2, Stripe a QR kód v kroku 3.*

| Platba | Popis |
|---|---|
| **Kartou** | Stripe (karta, Apple Pay, Google Pay). Ďalšie brány (GoPay, Comgate, …) sa doplnia ako moduly s rozhraním `PaymentGateway` |
| **Bankový prevod** | QR kód (PAY by square pre SK, QR Platba pre CZ), variabilný symbol, ručné párovanie platieb, neskôr import bankového výpisu |
| **Dobierka** | Príplatok podľa meny |
| **Hotovosť** | Iba pri osobnom odbere |

- Pri každej platbe sa dá obmedziť, s akou dopravou, v akej mene a v akej krajine je dostupná, prípadne od akej a do akej sumy objednávky
- Evidencia **poplatkov za platby**
- Vrátenie peňazí pri platbe kartou z administrácie
- Ak platba kartou zlyhá, zákazník dostane odkaz na opakovanú platbu

> Upozornenie: na prijímanie hotovosti na predajni je na Slovensku potrebná registrovaná pokladnica eKasa. Tú e-shop nenahrádza. Riešenie bude evidovať, že objednávka bola zaplatená v hotovosti.

---

## 12. Doklady a e-maily

*Kedy: základné e-maily v kroku 2, doklady a upraviteľné šablóny v kroku 3.*

### PDF doklady

- **Zálohová faktúra, faktúra, dobropis, dodací list, reklamačný protokol**
- Číselné rady sa dajú nastaviť pre každý typ dokladu
- Každý doklad v jazyku a mene objednávky
- Logo, podpis a pečiatka, QR kód na platbu, všetky zákonné náležitosti
- Vystavený doklad sa už nemení. Oprava sa robí dobropisom.
- Vzhľad dokladov sa dá upraviť šablónou

### Automatické e-maily (s PDF v prílohe)

- potvrdenie objednávky (+ zálohová faktúra + obchodné podmienky v PDF vo verzii, s ktorou zákazník súhlasil)
- prijatie platby (+ faktúra)
- odoslanie (+ sledovacie číslo)
- pripravené na vyzdvihnutie
- storno, dobropis
- vrátenie tovaru a reklamácie
- predobjednaný tovar je naskladnený
- rezervácia (potvrdenie, pripomienka, zrušenie)
- registrácia, zabudnuté heslo
- opustený košík
- výzva na hodnotenie
- naskladnenie tovaru, o ktorý mal zákazník záujem
- upozornenia pre obchodníka (nová objednávka, nízky sklad, blížiaca sa expirácia, reklamácia, blížiaca sa lehota)

### Ďalšie funkcie e-mailov

- **Úprava e-mailových šablón v administrácii** pre každý jazyk, s premennými a náhľadom
- Vzhľad e-mailov sa preberá z nastavení značky
- Odosielanie cez frontu s automatickým opakovaním pri zlyhaní
- Testovacie odoslanie, **záznam odoslaných e-mailov** a opätovné odoslanie
- Doklady aj e-maily sa dajú poslať aj ručne z detailu objednávky

---

## 13. Rezervácie

*Kedy: krok 4f. Modul sa dá vypnúť.*

- **Služby:** kalendár s voľnými termínmi, dĺžka služby, kapacita, viac zamestnancov alebo miestností, prestávky, sviatky a dovolenky
- **Zvieratá a jedinečné produkty:** rezervácia kusu na určitý čas so zálohou alebo bez nej
- Platba vopred, záloha alebo platba na mieste
- Pripomienka e-mailom, zrušenie rezervácie zákazníkom do nastaveného termínu
- Kalendár rezervácií v administrácii (pohľad na deň, týždeň a mesiac), ručné vytvorenie rezervácie
- Export rezervácií do kalendára (iCal)

---

## 14. Administrácia

### Celkovo

- Prehľadná ponuka, ktorá zobrazuje **iba zapnuté funkcie**
- **Globálne vyhľadávanie** (Ctrl+K) v objednávkach, produktoch, zákazníkoch aj článkoch
- Svetlý a tmavý režim, administrácia funguje aj na mobile, je v slovenčine, češtine aj angličtine
- Notifikácie v administrácii aj e-mailom
- V každej tabuľke: filtre, **uložené pohľady**, výber stĺpcov, hromadné akcie, export
- **Kôš:** zmazané položky sa dajú obnoviť
- **Záznam zmien:** kto, čo a kedy zmenil
- Upozornenie na neuložené zmeny, náhľad v obchode pred zverejnením, duplikovanie
- Klávesové skratky, **sprievodca prvým nastavením obchodu**

### Dashboard

- Tržby, objednávky, priemerný košík, porovnanie s predchádzajúcim obdobím
- Najpredávanejšie produkty, objednávky na vybavenie, nízky sklad, blížiace sa expirácie, dnešné rezervácie
- Upozornenia: blížiace sa lehoty reklamácií, problémy systému a integrácií

### Katalóg

- Produkty, varianty, typy produktov, **vlastné polia**, výrobcovia a značky, štítky
- Kategórie v strome, ktorý sa preusporadúva myšou
- Ceny v menách, nákupné ceny, akciové ceny s platnosťou od–do, ceny pre zákaznícke skupiny, množstevné zľavy
- Sklady, pohyby na sklade, **šarže, expirácie, predobjednávky**
- Hromadná úprava (ceny, sklad, kategórie, viditeľnosť)
- **Knižnica súborov** (kapitola 5.5)

### Objednávky

- Zoznam so stavmi, ktoré sa dajú upraviť (názov, farba, čo sa pri zmene stavu stane)
- **Ručné vytvorenie objednávky**, úprava položiek v existujúcej objednávke
- Platby, zásielky, doklady, šarže, interné poznámky, história komunikácie
- Hromadné akcie: zmena stavu, tlač faktúr a štítkov, vytvorenie zásielok, export

### Vrátenie tovaru a reklamácie

- Evidencia so stavmi, lehotami, dôvodom a výsledkom, protokol v PDF a dobropis (kapitola 16.2)

### Zákazníci

- Detail zákazníka (objednávky, útrata, adresy, poznámky, vlastné polia, súhlasy aj s verziami)
- Zákaznícke skupiny, blokovanie zákazníkov
- GDPR: export a anonymizácia údajov na žiadosť

### Marketing

- Kupóny, darčekové poukážky, newsletter (export, Mailchimp, Ecomail), opustené košíky
- XML feedy: Heureka, Google Merchant, Facebook a Instagram katalóg
- Meracie kódy: Google Analytics 4, Google Ads, Meta Pixel, so súhlasom s cookies

### Obsah a vzhľad

- Blog, statické stránky z blokov, ponuky, presmerovania 301, právne texty s verziami
- Šablóny, prispôsobenie so živým náhľadom, editor súborov

### Nastavenia

- Veľkosť obchodu a funkcie, moduly a ich stav
- Značka: názov, logo (svetlé a tmavé), favicon, hlavná farba, písmo
- Firemné údaje, predajne, jazyky, meny a kurzy, krajiny a DPH, právne pravidlá krajín
- Doprava, platby, kuriéri a platobné brány (API kľúče, testovací režim), prekladové API
- E-maily (SMTP), číselné rady dokladov, cookie lišta, režim údržby

### Systém

- Stav systému, logy, zálohy, aktualizácie (kapitola 17)

### Používatelia a bezpečnosť

- Roly s podrobnými oprávneniami. Predpripravené roly: Hlavný administrátor, Administrátor, Správca objednávok, Expedícia, Skladník, Redaktor, Marketing, Účtovník.
- **2FA** (dá sa nastaviť ako povinné), záznam prihlásení, odhlásenie zo všetkých zariadení

---

## 15. Exporty, importy a účtovníctvo

*Kedy: krok 4f. Jednoduchý export objednávok do CSV už v kroku 3.*

### Exporty

Objednávky, produkty, zákazníci, doklady, sklad, šarže, rezervácie a prehľady sa dajú exportovať do:

- **CSV** (s voľbou oddeľovača a kódovania znakov)
- **XLSX** (Excel)
- **XML**
- **JSON**
- **PDF** (tlačové zostavy)
- **ZIP** s PDF dokladmi za obdobie

Ďalšie možnosti: výber stĺpcov a filtrov, uložené šablóny exportov, naplánované exporty odosielané e-mailom. Veľké exporty bežia na pozadí.

### Importy

- Produkty, varianty, vlastné polia, ceny, sklad, kategórie a zákazníci z **CSV a XLSX**
- Priradenie stĺpcov, náhľad a kontrola chýb pred importom
- Import po častiach, aby sa zmestil do časového limitu webhostingu

### Účtovníctvo

- **Pohoda:** XML export faktúr, dobropisov, objednávok a skladových pohybov
- **Money S3:** XML export faktúr a dobropisov
- Ďalšie formáty (OMEGA, iDoklad, SuperFaktúra, …) sa dajú doplniť ako moduly

---

## 16. Právne a legislatívne požiadavky

### Dôležité upozornenie

> **Systém poskytuje nástroje, nie záruku právneho súladu.** Predpripravené pravidlá a vzorové texty sú pomôcka, nie právna rada. Za súlad so zákonmi zodpovedá obchodník a mal by si nastavenia aj texty dať skontrolovať právnikom.
>
> Legislatíva sa mení, preto **každý súbor pravidiel krajiny** obsahuje:
> - dátum poslednej kontroly
> - zdroj (odkazy na predpisy)
> - verziu
>
> Ak pravidlá neboli skontrolované dlhšie ako 12 mesiacov, administrácia zobrazí upozornenie. Aktualizované pravidlá prichádzajú s novými verziami systému, ale nenahrádzajú vlastnú kontrolu obchodníka.

### Konfigurovateľné pravidlá podľa krajín

Právne požiadavky nie sú zakódované napevno. Pre každú krajinu je **súbor pravidiel**, ktorý sa dá upraviť v administrácii:

- lehota na odstúpenie od zmluvy (predvolene 14 dní)
- lehota na vybavenie reklamácie
- záručná doba
- lehoty uchovávania údajov
- povinné texty a súhlasy v pokladni
- sadzby DPH

Predpripravené pravidlá: **Slovensko, Česko, všeobecné pravidlá EÚ**.

### 16.1 Obchodné podmienky a súhlasy

- **História verzií** obchodných podmienok, zásad ochrany osobných údajov a reklamačného poriadku. Každá verzia má dátum platnosti a staršie verzie sa nedajú upraviť.
- Pri objednávke sa uloží, **s ktorou verziou zákazník súhlasil**, kedy a z akej IP adresy. To isté platí pre newsletter a cookies.
- Platný text príde zákazníkovi ako PDF v potvrdzovacom e-maile

### 16.2 Odstúpenie od zmluvy a reklamácie

- **Lehoty sa počítajú automaticky** od dátumu doručenia podľa pravidiel krajiny
- **Evidencia reklamácií:**
  - dátum prijatia, produkt, **dôvod**, požadovaný spôsob vybavenia
  - postup, **výsledok** (oprava, výmena, zľava, vrátenie peňazí, zamietnutie) a dátum vybavenia
  - upozornenie pred uplynutím lehoty
  - prehľad reklamácií na export
- Formulár na odstúpenie od zmluvy aj pre zákazníkov bez registrácie
- Informácia o alternatívnom riešení sporov

### 16.3 Ochrana osobných údajov (GDPR)

- **Pravidlá uchovávania** pre každý typ údajov:
  - neaktívne účty
  - opustené košíky
  - logy
  - doklady (uchovávajú sa po zákonnú dobu, osobné údaje mimo dokladov sa anonymizujú)
- **Automatické mazanie a anonymizácia** cez cron, so záznamom
- Export a vymazanie údajov na žiadosť, evidencia žiadostí
- Cookie lišta so súhlasom podľa kategórií (Google Consent Mode v2)

### 16.4 Ceny a hodnotenia (smernica Omnibus)

- Pri zľave sa zobrazí **najnižšia cena za posledných 30 dní** (systém si pamätá históriu cien)
- Informácia, ako obchod overuje hodnotenia, a označenie **overený nákup**

### 16.5 Bezpečnosť produktov (GPSR)

- Výrobca (názov, adresa, kontakt), zodpovedná osoba v EÚ
- Identifikácia produktu (typ, šarža, EAN, model)
- Bezpečnostné informácie, upozornenia a dokumenty z knižnice súborov
- Údaje sa dajú nastaviť pre celého výrobcu
- Zoznam produktov s chýbajúcimi údajmi
- Stiahnutie produktu z trhu: vyhľadanie zákazníkov (aj podľa šarže) a ich upozornenie

### 16.6 Obaly a environmentálne povinnosti (EPR)

- Materiály a hmotnosti obalov pri produkte alebo type balenia
- Prehľad obalov uvedených na trh za obdobie podľa materiálov, s exportom
- Označenie elektrozariadení, batérií a pod. a zobrazenie recyklačného poplatku, ak sa vyžaduje

### 16.7 Prístupnosť

- Šablóny budú spĺňať **WCAG 2.1 AA** (norma EN 301 549), ktorú vyžaduje **Akt o prístupnosti (EAA)**
- Ovládanie klávesnicou, čítačky obrazovky, kontrast, alternatívne texty, prístupné formuláre
- Generátor vyhlásenia o prístupnosti a kontrola šablóny

### 16.8 Ostatné

- Povinné informácie o predávajúcom na webe
- Evidencia hotovostných platieb a upozornenie na eKasu
- Obmedzenie predaja podľa veku (alkohol, tabak)

---

## 17. Prevádzka: aktualizácie, zálohy, logy, monitoring

*Kedy: zálohy, logy a stav systému v kroku 3. Zvyšok v kroku 5.*

### Aktualizácie

- Balík je **podpísaný** (Ed25519) a má kontrolný súčet (SHA-256). Pred inštaláciou sa overí, že je kompletný a pochádza od autora.
- **Kontrola kompatibility** pred aktualizáciou (PHP, databáza, moduly, šablóna)
- Priebeh:
  1. zapne sa režim údržby
  2. vytvorí sa záloha
  3. nahradia sa súbory
  4. spustia sa Laravel migrácie (po častiach)
  5. vykoná sa kontrola, či všetko funguje
- **Vrátenie aktualizácie:**
  - automaticky pri chybe
  - ručne jedným kliknutím
  - cez záchranný skript, ak administrácia nefunguje
- Kanály aktualizácií: stabilný a beta, prehľad zmien v administrácii

### Zálohovanie

- `spatie/laravel-backup`: záloha databázy a súborov jedným kliknutím alebo podľa plánu, nastaviteľný počet uchovávaných záloh
- **Externé úložisko** cez Laravel Filesystem: FTP/SFTP, S3-kompatibilné úložiská. Voliteľne Google Drive a Dropbox.
- Šifrovanie záloh heslom
- **Test obnovy:**
  - kontrola, či je záloha kompletná a čitateľná
  - voliteľne skúšobná obnova do dočasných tabuliek
  - pri zlyhaní príde upozornenie
- Obnova v administrácii alebo cez záchranný skript

### Logovanie

- **Centrálny prehľad logov** (logy Laravelu cez Monolog, zobrazené v administrácii):
  - chyby a výnimky s označením modulu
  - systémové udalosti
  - komunikácia s platobnými bránami a kuriérmi
  - e-maily a bezpečnostné udalosti
- Filtrovanie a zoskupovanie rovnakých chýb, automatické premazávanie starých záznamov
- E-mail pri kritickej chybe. Voliteľne Sentry.

### Monitoring: stránka „Stav systému"

| Kontrola | Čo sa sleduje |
|---|---|
| Databáza | Pripojenie, verzia, veľkosť, rýchlosť odozvy, čakajúce migrácie |
| Cron | Čas posledného spustenia, upozornenie, ak cron nebeží |
| Fronta | Čakajúce a neúspešné úlohy, opätovné spustenie |
| SMTP | Test pripojenia a odoslania, posledné chyby |
| Integrácie | Stav kuriérov, platobných brán a prekladového API (dostupné, dočasne nedostupné, chybná konfigurácia) |
| Hosting | PHP a rozšírenia, limity, voľné miesto, práva na zápis |
| Bezpečnosť | SSL certifikát, vypnutý ladiaci režim, chránené adresáre |
| Zálohy | Posledná úspešná záloha a výsledok testu obnovy |

- **Kontrolná adresa (health endpoint)** pre externé služby, ktoré sledujú dostupnosť webu

---

## 18. Bezpečnosť, výkon a testovanie

### Bezpečnosť

- Ochrana proti útokom CSRF a XSS a proti SQL injection (štandardné mechanizmy Laravelu), bezpečné ukladanie hesiel
- **2FA** pre administrátorov (od kroku 3)
- Obmedzenie počtu pokusov o prihlásenie, ochrana formulárov proti spamu
- **Ochrana API** (po 1.0): tokeny s oprávneniami a platnosťou, obmedzenie počtu požiadaviek, voliteľne povolené iba z určitých IP adries. Overovanie podpisov webhookov od platobných brán a kuriérov platí od začiatku.
- **Bezpečné nahrávanie súborov:**
  - typ súboru sa kontroluje podľa obsahu, nielen podľa prípony
  - súbory sa premenujú, v adresári s nahranými súbormi sa zakáže spúšťanie PHP
  - obrázky sa znova uložia, SVG súbory sa vyčistia
  - limity veľkosti
  - ZIP súbory sa kontrolujú pred rozbalením
- API kľúče šifrované (Laravel Encryption), bezpečnostné HTTP hlavičky

### Výkon

- Databázové indexy navrhnuté podľa skutočných dotazov. Pri vývoji sa sledujú pomalé a zbytočne opakované dotazy.
- Cache celých stránok pre neprihlásených návštevníkov, cache dotazov a nastavení
- Redis alebo Memcached voliteľne, inak cache v súboroch alebo v databáze
- Ťažké operácie bežia na pozadí

**Výkonnostné ciele a podmienky merania:**

| Podmienka | Hodnota |
|---|---|
| Hosting | Referenčný lacný webhosting. Konkrétny tarif sa vyberie v kroku 1 a zapíše do dokumentácie. PHP 8.3, zapnutý OPcache |
| Dáta | 10 000 produktov, 30 000 variantov, 200 kategórií, 20 vlastných polí, 50 000 objednávok |
| Záťaž | 10 súbežných používateľov, meranie nástrojom k6 z dátového centra v rovnakej krajine |
| Metrika | **p95** času odozvy servera (95 % požiadaviek je rýchlejších) |

| Stránka | Cieľ p95 |
|---|---|
| Detail produktu a kategória, neprihlásený návštevník, zahriata cache stránok | **≤ 300 ms** |
| Detail produktu a kategória bez cache stránok (prihlásený zákazník, plný košík) | ≤ 800 ms |
| Filtrovanie v kategórii | ≤ 1 000 ms |
| Odoslanie objednávky (bez čakania na platobnú bránu) | ≤ 1 500 ms |
| Zoznam produktov a objednávok v administrácii | ≤ 1 000 ms |

Merania sa opakujú pri každej väčšej verzii a výsledky sa zapisujú do dokumentácie.

### Testovanie

Testovať každú kombináciu všetkého by bolo neúmerne nákladné. Preto kombinujeme **hlavnú testovaciu maticu** s **cielenými scenármi**.

**Pri každej zmene kódu (GitHub Actions):**
- jednotkové a integračné testy na **PHP 8.3, 8.4 a 8.5** s **MySQL 8.0**
- kontrola štýlu kódu a statická analýza (PHPStan)

**Raz denne a pred vydaním verzie:**
- kompatibilita databáz: celá sada testov na jednej verzii PHP s **MySQL 8.4, MariaDB 10.6 a MariaDB 11.4**
- **E2E testy** (Playwright, automatické prehrávanie nákupu v prehliadači) pre kľúčové scenáre:
  - nákup bez registrácie, nákup s účtom, firemný nákup, kupón
  - každá platba aspoň raz a každá doprava aspoň raz
  - kombinácie platby, dopravy, meny a jazyka vyberané **párovou metódou**: každá dvojica hodnôt sa otestuje aspoň raz, nie celý kartézsky súčin
  - vrátenie tovaru, predobjednávka, produkt so šaržou
  - kľúčové činnosti v administrácii
- testy napojení na kuriérov a platobné brány proti ich testovacím (sandbox) prostrediam
- automatická kontrola prístupnosti šablóny Default

**Pred verziou 1.0 a pred väčšími verziami:** záťažové testy, testy aktualizácií a obnovy záloh, manuálne testovanie na reálnych webhostingoch.

**Pri každej opravenej chybe** pribudne test, ktorý ju zachytí, aby sa chyba nevrátila.

---

## 19. Plán po verzii 1.0

O poradí rozhodne dopyt obchodníkov.

- **Pokročilá analytika:**
  - zisk, marža, zisk po doprave a poplatkoch
  - najziskovejšie produkty
  - opakované nákupy, celková hodnota zákazníka, kohorty
  - nákupný lievik
  - hodnota a obrátka skladu

  Dáta na to (nákupné ceny, náklady na dopravu, poplatky) sa zbierajú už od kroku 3.
- **Verejné REST API** (`/api/v1`) a odchádzajúce webhooky (kapitola 2.7)
- **Pluginy tretích strán:** inštalácia zo ZIP, kontrola a podpisovanie, izolácia chýb podľa kapitoly 2.6, prípadne neskôr marketplace modulov a šablón
- **Affiliate program** (provízie partnerom)
- **Vernostný program** (body, úrovne zákazníkov)
- **POS:** pokladňa na predajni s napojením na eKasu cez certifikované zariadenie
- **AI funkcie:** generovanie popisov produktov, inteligentné vyhľadávanie, odporúčanie produktov
- **Live chat**
- Ďalší kuriéri, platobné brány a účtovné systémy

---

## 20. Zhrnutie rozhodnutí

| Otázka | Rozhodnutie |
|---|---|
| Technológie | Laravel + Filament, PHP 8.3+, MySQL 8.0+ / MariaDB 10.6+ |
| Princíp | Staviame e-shop, nie framework. Laravel a overené balíky všade, kde sa dá |
| Hosting | Bežný webhosting, inštalácia nahratím ZIP a webovým inštalátorom |
| Architektúra | Interné moduly (Service Provider + `module.json`), rozhrania pre poskytovateľov, Laravel Events, Queue, Migrations, Policies |
| Chyby modulov | Záznam, rozlíšenie typu chyby, dočasná nedostupnosť integrácií, izolácia iba dotknutej časti pri opakovaných chybách, upozornenie, ručné ovládanie |
| API | Interné JSON endpointy pre šablónu od začiatku, verejné verzované REST API po 1.0 |
| Pluginy tretích strán | Po verzii 1.0 |
| Inštalácie | Jeden predajca na jednu inštaláciu |
| Veľkosť obchodu | Malý, stredný, veľký alebo vlastný, každá funkcia sa dá samostatne zapnúť alebo vypnúť |
| Katalóg | Typy produktov, **vlastné polia**, obrázky variantov, predobjednávky, šarže, expirácia, knižnica súborov |
| Jazyky | SK, CZ, EN a ďalšie, preklady cez DeepL alebo Google |
| Meny | EUR, CZK, USD, GBP, ručná cena na produkte v každej mene |
| Platby | Kartou (Stripe), prevod s QR kódom, dobierka, hotovosť pri osobnom odbere |
| Doprava | Packeta, GLS, DPD (stačí vložiť API kľúče), osobný odber, vlastná doprava |
| Doklady | Vlastné PDF doklady, ktoré sa odosielajú e-mailom |
| Vzhľad obchodu | Vymeniteľné šablóny na štandardných nástrojoch Laravelu (Blade) |
| Legislatíva | Konfigurovateľné pravidlá krajín s dátumom kontroly. Ide o nástroje, nie záruku súladu |
| Výkon | p95 ≤ 300 ms pri zahriatej cache, 10 000 produktov, referenčný hosting |
| Testovanie | Hlavná matica pri každej zmene + denné E2E testy s párovými kombináciami |
| Postup | 1. prototyp → 2. funkčný obchod → **3. pilot pre malého obchodníka (0.5)** → 4. univerzálnosť → 5. verzia 1.0 |
