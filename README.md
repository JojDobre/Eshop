# E-shop

Univerzálny e-shop pre bežný webhosting: oblečenie, nábytok, potraviny, služby, zvieratá aj digitálne produkty.
Jedna inštalácia = jeden obchod s vlastným názvom, logom, farbami a šablónou vzhľadu.

> **Stav:** krok 4d – doprava a obsah (0.9.0). Obchod predáva vo viacerých jazykoch a menách, posiela
> zásielky cez Packetu, GLS a poštu so štítkami a sledovaním, má blog prepojený s produktmi a vzhľad skladaný
> z blokov a šablón. Funkcie sa zapínajú podľa veľkosti obchodu.
> Plán ďalších krokov: [`PLAN.md`](PLAN.md), prehľad zmien: [`CHANGELOG.md`](CHANGELOG.md).

## Čo je hotové

**Doprava a obsah (krok 4d)**

- **Kuriéri Packeta, GLS, PPL, SPS, Česká pošta, Slovenská pošta a ďalší cez Balíkobot:** výdajné miesta a boxy v pokladni, zásielky a štítky (aj hromadne), sledovanie,
  dobierka; **DPD** a vlastný kuriér so sledovacím číslom.
- **Doprava:** vlastný rozvoz podľa PSČ, náklady na dopravu, doprava len pre vybrané typy produktov.
- **Blog** s blokovým editorom, plánovaním, komentármi, RSS a prepojením na produkty.

**Veľkosť obchodu a šablóny (krok 4c)**

- **Veľkosť obchodu** (malý, stredný, veľký, vlastný) zapne vhodné funkcie.
- **Šablóny:** náhľad pred aktiváciou, nahratie a export ZIP, odvodené šablóny, kontrola, editor súborov.
- **Prispôsobenie so živým náhľadom**, **bloky** na úvodnej stránke, stránkach a v kategóriách, **ponuky**.
- Šablóny **Default**, **Fresh** a **Starter** (pre vývojárov).

**Rozšírený katalóg a sklad (krok 4b)** – zapína sa v Nastavenia → Funkcie obchodu

- **Typy produktov** s vlastnými poliami, cenou za mernú jednotku, bez dopravy a jedinečným kusom.
- **Vlastné polia** pre kategórie, výrobcov, zákazníkov a objednávky, texty v každom jazyku.
- **Doplnkové voľby** s príplatkom, **balíčky** produktov, **predobjednávky**.
- **Viac skladov, šarže a expirácia:** výdaj od najskoršej expirácie, inventúra, presun, odpis, zľava
  na končiaci tovar, dohľadanie šarže z objednávky.
- **Digitálne produkty** s odkazmi na stiahnutie (limit, platnosť).

**Jazyky, meny a DPH v EÚ (krok 4a)**

- **Viac jazykov:** adresy `/cz/…`, `/en/…` s preloženými adresami stránok, `hreflang`, prepínač jazyka,
  preklady obsahu v administrácii s prehľadom chýbajúcich prekladov, e-maily a doklady v jazyku zákazníka.
- **Strojový preklad** cez DeepL alebo Google so slovníkom pojmov, limitom a hromadným prekladom.
- **Viac mien:** kurzy z ECB alebo ručne, zaokrúhľovanie, ručné ceny v každej mene.
- **DPH v EÚ:** režim OSS a prenesenie daňovej povinnosti s overením IČ DPH vo VIES.

**Pilotná verzia (krok 3)**

- **Dane a zákony:** sadzby DPH (SK, CZ), ceny s DPH aj bez, rozpis DPH, neplatca DPH; výrobcovia a údaje
  o bezpečnosti výrobkov (GPSR); najnižšia cena za 30 dní pri zľave (Omnibus); pohyby skladu a upozornenie na nízky sklad.
- **Varianty a vlastné polia:** voliteľné vlastnosti (veľkosť, farba…), generovanie variantov s cenou, skladom
  a obrázkom; vlastné polia produktov s filtrami v kategórii.
- **Kupóny a doprava:** percentuálne a pevné zľavy, minimálna suma, počet použití, platnosť; cena dopravy podľa hmotnosti.
- **Doklady v PDF:** zálohová faktúra (v prílohe potvrdenia pri prevode), faktúra po zaplatení, dobropis;
  nemenné doklady s číselnými radmi, stiahnutie pre zákazníka aj administráciu.
- **Upraviteľné e-maily:** texty všetkých e-mailov s premennými, náhľadom a testovacím e-mailom;
  e-maily zákazníkovi pri zmene stavu objednávky.
- **Platby:** QR kód na platbu prevodom (PAY by square, QR Platba, EPC QR) na stránke objednávky aj v PDF;
  platba kartou, Apple Pay a Google Pay cez **Stripe** (stačí zadať kľúče).
- **Zákaznícke účty:** registrácia, prihlásenie, zabudnuté heslo, prehľad objednávok, uložené adresy,
  predvyplnená pokladňa, zrušenie účtu; správa zákazníkov v administrácii.
- **Právne minimum:** verzie obchodných podmienok a zaznamenaný súhlas pri objednávke a registrácii,
  podmienky v PDF v prílohe potvrdenia, lišta cookies (kódy analytiky až po súhlase),
  odstúpenie od zmluvy a reklamácie online so zákonnými lehotami.
- **Prevádzka:** zálohy v PHP (bez mysqldump), webový cron, prehliadač logov, stav systému,
  dvojfaktorové prihlásenie do administrácie, knižnica súborov.

**Obchod (krok 2)**

- **Katalóg:** kategórie a podkategórie, produkty s obrázkami, cenou a skladom, triedenie, vyhľadávanie bez diakritiky.
- **Košík a pokladňa na jednej stránke:** nákup bez registrácie, firemné údaje, iná doručovacia adresa,
  doprava (osobný odber, doprava s pevnou cenou, doprava zadarmo od sumy) a platba (prevod, dobierka,
  platba na predajni) s obmedzením, ktorá platba je dostupná pri ktorej doprave.
- **Objednávky:** číslovanie (zároveň variabilný symbol), odpis skladu, potvrdenie e-mailom zákazníkovi
  a upozornenie obchodníkovi, stránka objednávky pre zákazníka s platobnými údajmi.
- **Administrácia objednávok:** zoznam s filtrami, detail, zmena stavu (storno vráti tovar na sklad),
  označenie platby, interné poznámky a história, prehľad tržieb na nástenke.
- **Stránky:** obchodné podmienky, ochrana osobných údajov, doprava a platba, kontakt (vzorové kostry).
- **E-maily:** nastavenie SMTP servera v administrácii s testovacím e-mailom.
- **SEO:** titulky a popisy, kanonické adresy, Open Graph, štruktúrované dáta produktu, `sitemap.xml`, `robots.txt`.
- Pri inštalácii sa dá nahrať **ukážkový katalóg**.

**Základ (krok 1)**

- **Webový inštalátor** v slovenčine, češtine a angličtine: kontrola webhostingu, pripojenie k databáze,
  nastavenie obchodu a administrátora. Netreba SSH, Composer ani npm.
- **Administrácia** (Filament 5): nastavenia obchodu, značky (logo, favicon, hlavná farba) a firemných údajov,
  používatelia s rolami, prehľad modulov so zapínaním a vypínaním, profil s výberom jazyka administrácie.
- **Systém modulov:** každá funkcia je modul s `module.json`, závislosťami a vlastnými migráciami.
- **Šablóny vzhľadu:** obchod sa zobrazuje cez vymeniteľnú šablónu (`themes/default`) s podporou odvodených šablón.
- **Inštalačný balík** pre webhosting a automatické testy (GitHub Actions).

## Požiadavky na webhosting

| | Minimum |
|---|---|
| PHP | 8.3 alebo novšie (rozšírenia: pdo_mysql, mbstring, openssl, intl, curl, zip, fileinfo, sodium, bcmath, xml, dom, gd alebo imagick) |
| Databáza | MySQL 8.0+ alebo MariaDB 10.6+ (MySQL 5.7 s obmedzenou podporou) |
| Webserver | Apache s mod_rewrite alebo LiteSpeed |

## Inštalácia na webhosting

Postup krok za krokom: [`docs/INSTALL.md`](docs/INSTALL.md). Stručne:

1. Stiahnite inštalačný balík `eshop-<verzia>.zip` (GitHub Actions → beh CI → *Artifacts*, alebo `scripts/build-release.sh`).
2. Rozbaľte ho a nahrajte cez FTP na webhosting.
3. Otvorte adresu webu a prejdite sprievodcom inštalácie.

## Vývoj

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan serve
```

Potom otvorte `http://localhost:8000` a prejdite inštalátorom (potrebná je databáza MySQL/MariaDB).

```bash
php artisan test        # testy (SQLite v pamäti)
vendor/bin/pint         # štýl kódu
scripts/build-release.sh  # inštalačný balík → dist/
```

Architektúra, moduly a šablóny: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Licencie tretích strán

Postavené na [Laravel](https://laravel.com) a [Filament](https://filamentphp.com) (MIT). Preklady systémových
hlášok do slovenčiny a češtiny pochádzajú z projektu [Laravel Lang](https://laravel-lang.com) (MIT).
Písmo [Inter](https://rsms.me/inter/) (SIL Open Font License).
