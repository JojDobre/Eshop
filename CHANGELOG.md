# Prehľad zmien

## 0.6.0 – Jazyky, meny a DPH v EÚ (krok 4a)

- **Viac jazykov obchodu:** ďalšie jazyky v nastaveniach, predvolený jazyk bez predpony, ostatné
  s predponou v adrese (`/cz/…`, `/en/…`), preložené adresy produktov, kategórií a stránok, `hreflang`,
  prepínač jazyka, sitemap vo všetkých jazykoch, objednávky a e-maily v jazyku zákazníka.
- **Preklady v administrácii:** prepínač jazyka obsahu na stránkach úprav, stĺpec s chýbajúcimi prekladmi.
- **Viac mien:** kurzy ručne alebo denne z ECB, zaokrúhľovanie (matematicky, na celé jednotky, na ,99),
  prepínač meny, ručné ceny v každej mene s prepočtom chýbajúcich, doprava, platby a kupóny v mene
  objednávky, kurz uložený v objednávke a tržby prepočítané do predvolenej meny.
- **DPH v EÚ:** sadzby pre ďalšie krajiny EÚ (s predvyplnením), režim OSS, prenesenie daňovej povinnosti
  s overením IČ DPH vo VIES a povinným textom na faktúre; zoznam krajín EÚ v pokladni.
- Modul **Strojový preklad** (DeepL, Google): tlačidlo Preložiť, slovník pojmov, mesačný limit,
  označenie „na kontrolu“ a hromadný preklad po dávkach.

## 0.5.0 – Pilotná verzia pre malého obchodníka (krok 3)

- **DPH:** modul Dane so sadzbami SK a CZ, ceny s DPH podľa nastavenia, rozpis DPH v objednávke a na dokladoch.
- **GPSR a Omnibus:** výrobcovia s údajmi o bezpečnosti, história cien a najnižšia cena za 30 dní pri zľave.
- **Sklad:** pohyby skladu, upozornenie na nízky sklad (e-mail, nástenka).
- **Varianty** s vlastnou cenou, skladom a obrázkom; **vlastné polia** produktov s filtrami.
- **Kupóny** a cena dopravy podľa hmotnosti.
- Modul **Doklady**: zálohové faktúry, faktúry a dobropisy v PDF.
- **Upraviteľné texty e-mailov** a e-maily pri zmene stavu objednávky.
- **QR platba** (PAY by square, QR Platba, EPC QR) a modul **Stripe** na platbu kartou.
- Modul **Zákazníci**: registrácia, prihlásenie, objednávky, adresy, zrušenie účtu.
- **Verzie právnych dokumentov** a záznam súhlasu, obchodné podmienky v PDF k potvrdeniu.
- **Lišta cookies** s načítaním analytiky až po súhlase.
- Modul **Vrátenie a reklamácie** so zákonnými lehotami.
- **Zálohy**, webový cron, **logy**, **stav systému**, **dvojfaktorové prihlásenie**, **knižnica súborov**.
- Odstránený balík spatie/laravel-backup (nahradený zálohou v PHP, ktorá nepotrebuje mysqldump).

## 0.2.0 – Prvý funkčný obchod (krok 2)

- Moduly **Katalóg, Doprava, Platby, Košík, Objednávky, Stránky**.
- Šablóna obchodu: úvod, kategória, produkt, vyhľadávanie, košík, pokladňa, objednávka, statická stránka, 404.
- Pokladňa na jednej stránke, objednávka bez registrácie, odpis skladu so zámkom proti súbežným nákupom.
- E-maily o objednávke (zákazník aj obchodník), nastavenie SMTP v administrácii, heslo uložené šifrovane.
- Správa objednávok: stavy, platba, poznámky, história; nástenka s tržbami.
- Inštalátor vytvorí predvolenú dopravu, platby a stránky, voliteľne ukážkový katalóg.
- SEO: meta údaje, kanonické adresy, Open Graph, JSON-LD, sitemap.xml, robots.txt.
- Obsah sa ukladá v jazyku obchodu nezávisle od jazyka administrácie, sumy sa dajú zadávať s desatinnou čiarkou.
- Administrácia: hlavné tlačidlá vo farbe značky, skupiny menu Katalóg, Predaj, Obsah, Nastavenia, Systém.

## 0.1.0 – Technický prototyp (krok 1)

- Laravel 13 a Filament 5, minimálne PHP 8.3.
- Webový inštalátor (SK, CZ, EN): kontrola webhostingu, overenie databázy (MySQL 8.0+, MariaDB 10.6+,
  obmedzene MySQL 5.7), prefix tabuliek, nastavenie obchodu a administrátora.
- Beh na webhostingu bez nastavenia DocumentRoot (koreňový `.htaccess`) aj v podadresári.
- Administrácia: nastavenia obchodu, značky (logo, favicon, hlavná farba) a firemných údajov,
  používatelia s rolou hlavného administrátora, profil s jazykom administrácie, prehľad modulov.
- Systém modulov (`module.json`, závislosti, kontrola verzií, záchranný režim).
- Systém šablón vzhľadu s odvodenými šablónami, predvolená šablóna obchodu.
- Písmo uložené na hostingu (bez externých služieb).
- Inštalačný balík (`scripts/build-release.sh`) a CI na GitHub Actions.
