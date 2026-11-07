# Prehľad zmien

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
