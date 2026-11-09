# Prehľad zmien

## 1.2.1 – Oprava aktualizácie

- Oprava: aktualizácia nespustila migrácie modulu, ktorý v nej pribudol (napr. Kontaktné formuláre vo verzii 1.2.0),
  a administrácia potom hlásila chybu 500. Ak ste aktualizovali na 1.2.0, spustite raz `php artisan migrate --force`.

## 1.2.0 – Úpravy podľa testovania (časť 2)

- **Knižnica médií:** obrázky nahraté pri produktoch, variantoch, kategóriách, blogu, blokoch, v šablóne a v editore
  obsahu sa automaticky zaradia do knižnice (staršie sa doplnia pri jej otvorení). Pri každom poli s obrázkom je
  tlačidlo „Vybrať z knižnice“. Obrázok, ktorý sa niekde používa, sa nedá zmazať.
- **Kontaktné formuláre (nový modul):** vlastné formuláre s poľami (text, e-mail, telefón, dlhý text, výber,
  zaškrtávacie políčko, dátum, číslo), blok „Formulár“ na ľubovoľnú stránku, správy v administrácii (Obsah → Správy)
  s počtom neprečítaných, upozornenie e-mailom s možnosťou odpovedať. Predvolený formulár „Kontakt“ sa pridá
  na stránku Kontakt automaticky.
- **Šablóna Farma:**
  - sekcie úvodnej stránky (baner, kategórie, produkty, baner s textom, pruh s tipom, O nás, hodnotenia, časté
    otázky a bloky z Obsah → Úvodná stránka a bloky) sa dajú ľubovoľne zoradiť, odstrániť aj zopakovať a každá
    má rozloženie: predvolené, obrátené (obrázok na druhej strane), užšie alebo na celú šírku,
  - formátovaný text v sekcii O nás, v baneri a v odpovediach na časté otázky,
  - baner iba s obrázkom (bez nadpisu a textu), upraviteľné výhody v sekcii O nás (ikona, nadpis, text),
  - text vedľa loga a voliteľne názov obchodu pri logu, funkčné rozloženie hlavičky „logo v strede“,
    vyhľadávanie vycentrované na stred stránky,
  - animácie odkazov, tlačidiel a obrázkov pri prejdení myšou, tlačidlo Kontakt pri častých otázkach vedie
    na stránku Kontakt.
- Prispôsobenie šablón: nové typy nastavení – viacriadkový text, formátovaný text a zoradenie sekcií.

## 1.1.0 – Úpravy podľa testovania (časť 1)

- **Menu administrácie:** nastavenia zoskupené pod Obchod, Doprava (s jednotlivými dopravcami), Platby, DPH,
  E-maily a Funkcie obchodu; ľubovoľnú položku menu sa dá skryť (Nastavenia → Obchod → Menu administrácie).
  Stránka Moduly sa zmestí na šírku okna.
- **Produkty:** vlastné štítky s farbou, vlastné stavy dostupnosti (aj „nedá sa kúpiť“), vlastné pole zobrazené
  pod stavom dostupnosti (napr. krajina pôvodu), nastavenia zobrazenia (text „vrátane DPH“, kód, stav a počet
  kusov, štítok Novinka, popis pod produktom alebo vedľa fotiek).
- **Editor obsahu:** obrázky, farba a zvýraznenie textu, zarovnanie, oddeľovač, stĺpce, rozbaľovacie časti;
  odkaz na YouTube/Vimeo v samostatnom riadku sa zobrazí ako video.
- Kurzor ruky na tlačidlách, rovnomerné odsadenie šípky v rozbaľovacích zoznamoch.
- Oprava: do košíka sa dal pridať rezervovaný alebo nedostupný produkt.

## 1.0.0 – Produkčná verzia (krok 5)

- **Šablóna Farma** pre malé obchody s domácimi produktmi: oznam v hornom pruhu (štítok, zľavový kód, odkaz,
  dátum platnosti, dá sa zavrieť), hlavička s vyhľadávaním a košíkom, úvodný baner, kategórie v dlaždiciach,
  karty produktov s pridaním do košíka jedným klikom a stavom skladu, baner, pruh s tipom, sekcia o obchode
  s výhodami, hodnotenia zákazníkov a časté otázky (aj ako štruktúrované dáta). Všetko sa nastavuje
  v Vzhľad → Prispôsobiť, písmo Nunito je uložené na hostingu.
- **Bezpečnosť:** bezpečnostné hlavičky a HSTS, čistenie obsahu z editora (bez skriptov), prísnejšie
  nahrávanie súborov, nahratie šablóny iba pre hlavného správcu, test oprávnení všetkých sekcií administrácie
  (prehľad v `docs/SECURITY.md`).
- **Zálohy:** obnova zo zálohy v administrácii (aj nahratej z iného hostingu, s prevzatím kľúča aplikácie),
  automatický test obnovy po každej zálohe, šifrovanie heslom (AES-256), kópia na externé úložisko (FTP, SFTP,
  S3-kompatibilné), denné alebo týždenné zálohy, príkazy `eshop:backup` a `eshop:restore`.
- **Aktualizácie:** podpísané balíky (Ed25519, SHA-256), kontrola kompatibility, režim údržby, záloha,
  automatické vrátenie pri chybe, ručné vrátenie jedným klikom a záchranný skript `public/rescue.php`.
  Prechody databázy zo všetkých vydaných verzií sa overujú v CI (`scripts/test-upgrades.sh`).
- **Ochrana údajov (GDPR):** automatické mazanie a anonymizácia podľa nastaviteľných lehôt – logy, IP adresy,
  košíky, objednávky po zákonnej lehote, neaktívne účty, nepotvrdené prihlásenia na newsletter, rezervácie,
  exportované súbory (Nastavenia → Ochrana údajov). Moduly pridávajú vlastné pravidlá.
- **Evidencia obalov (EPR)** (nový modul): hmotnosť obalu podľa materiálu pri produkte a pri zásielke, výkaz
  za obdobie v kg a export CSV pre organizáciu zodpovednosti výrobcov.
- **Prístupnosť:** vyhlásenie o prístupnosti ako predvolená právna stránka (Akt o prístupnosti), opravy
  kontrastu a popisov v šablónach Default a Farma, kontrola WCAG 2.1 AA skriptom `scripts/a11y-check.cjs`.
- **Testovanie a výkon:** E2E testy v prehliadači na čistej inštalácii z balíka (inštalátor, nákup bez
  registrácie, s účtom, firemný nákup, administrácia), záťažový test s 10 000 produktmi a 50 000 objednávkami
  (`docs/PERFORMANCE.md`). Nastavenia sa načítavajú z cache – stránka kategórie zo 150 na 25 dotazov.
- **Dokumentácia:** príručka pre obchodníkov, pre vývojárov, požiadavky na hosting (Apache, nginx, cron).
- **Verzie:** jadro, všetky moduly a šablóny 1.0.0, závislosti `^1.0`.
- Oprava: záloha na zdieľanom hostingu zlyhala, ak mal používateľ databázy prístup aj k iným databázam.
- Oprava: farba značky z nastavení sa v šablónach Default a Fresh neprejavila, lebo ju prepísali predvolené štýly.

## 0.11.0 – Rezervácie, dáta a doklady (krok 4f)

- **Reklamácie a odstúpenie:** výsledok vybavenia (oprava, výmena, zľava, vrátenie peňazí, zamietnutie) a vrátená
  suma, reklamačný protokol v PDF, dobropis jedným klikom, upozornenie e-mailom pred uplynutím lehoty, export
  prehľadu do CSV, formulár bez účtu (číslo objednávky a e-mail) a informácia o alternatívnom riešení sporov.
- **Účtovné exporty** (nový modul): XML za obdobie pre **Pohodu** (faktúry, dobropisy, prijaté objednávky)
  a **Money S3** (faktúry a dobropisy), so sadzbami DPH, údajmi odberateľa a cudzou menou. Ďalšie formáty
  sa dajú doplniť ako exportér.
- **Import a export** (nový modul): export objednávok, produktov (aj vlastné polia), skladu, zákazníkov a dokladov
  do CSV (oddeľovač, UTF-8 alebo Windows-1250), XLSX, JSON a XML s výberom stĺpcov a obdobia, uložené šablóny
  posielané e-mailom (denne, týždenne, mesačne), ZIP s PDF dokladmi za obdobie. Import produktov (varianty,
  kategórie, ceny, sklad, vlastné polia) a zákazníkov z CSV a XLSX s priradením stĺpcov, náhľadom a kontrolou
  chýb, spracovaný po častiach v rámci časového limitu webhostingu.
- **Rezervácie** (nový modul, predvolene vypnutý): služby s trvaním, prestávkou po termíne, kapacitou
  (skupinové termíny) a cenou; zamestnanci a miestnosti s týždenným rozvrhom (viac úsekov za deň), sviatky
  a dovolenky; výber voľného termínu bez JavaScriptu, ochrana proti dvojitej rezervácii, platba na mieste,
  záloha alebo celá suma vopred, zrušenie zákazníkom do nastavenej lehoty, pripomienka e-mailom, pridanie do
  kalendára (.ics) a kalendárový odber pre obchod (iCal). Administrácia so zoznamom, kalendárom (deň, týždeň,
  mesiac) a ručnou rezerváciou. Rezervácia jedinečného kusu (napr. zviera) na nastavený počet dní – počas nej
  sa nedá kúpiť.
- Pre vývojárov: `Product::addAvailabilityCheck()` – moduly môžu obmedziť dostupnosť produktu. Verzie: jadro 0.11.0,
  catalog 0.9.0, returns 0.3.0, šablóna default 0.8.0, nové moduly accounting, transfer a reservations 0.1.0.

## 0.10.0 – Predaj a marketing (krok 4e)

- **Zákaznícke skupiny a B2B ceny:** skupiny so zľavou, vlastné ceny produktov a variantov pre skupinu
  v každej mene, predvolená skupina pre návštevníkov, zobrazenie cien bez DPH pre firmy. Zapína sa
  v Nastavenia → Funkcie obchodu.
- **Množstevné zľavy** pri produkte (pre všetkých alebo pre skupinu) s tabuľkou na stránke produktu.
- **Blokovanie zákazníkov:** zablokovaný zákazník sa neprihlási a neobjedná ani bez prihlásenia.
- **Darčekové poukážky:** predaj ako produkt (kód príde e-mailom po zaplatení), ručné vydanie v administrácii,
  uplatnenie kódu v košíku, čerpanie po častiach, platnosť, vrátenie zostatku pri zrušení objednávky.
  Poukážka je platba (zníži sumu na úhradu vrátane QR platby, dobierky a Stripe), nie zľava.
- **Hodnotenia produktov** (nový modul): hviezdičky, nadpis, text, výhody a nevýhody, označenie „Overený nákup“,
  schvaľovanie, odpoveď obchodu, režim „iba overené nákupy“, ochrana proti spamu, priemer a rozdelenie
  hviezdičiek, hviezdičky vo výpise produktov, štruktúrované dáta pre Google, informácia o overovaní
  (Omnibus) a žiadosť o hodnotenie e-mailom po dokončení objednávky (odkaz bez prihlásenia).
- **Porovnávač produktov 2.0:** produkty vedľa seba (na mobile 2 s posúvaním), zvýraznenie rozdielov
  a „iba rozdiely“, výber vlastností podľa skupín, zvýraznenie najlepšej hodnoty (nastavuje sa pri vlastnom
  poli), lepkavá hlavička s cenou a tlačidlom do košíka, skupiny podľa typu produktu, zdieľanie odkazom a tlač.
- **Newsletter** (nový modul Marketing): prihlásenie v päte a v pokladni s potvrdením e-mailom (double opt-in),
  dôkaz súhlasu, odhlásenie odkazom, export CSV, synchronizácia s Mailchimp alebo Ecomail.
- **Opustené košíky:** e-mail zadaný v pokladni sa uloží ku košíku, po nastavenom čase príde jedna pripomienka
  s odkazom, ktorý obnoví košík aj v inom zariadení.
- **XML feedy** pre Heureku, Google Merchant a Facebook/Instagram katalóg (varianty, parametre, EAN, kategórie,
  akciové ceny, jazyk a mena v parametroch, obnova raz za hodinu). Nové pole **EAN** pri produkte.
- **Meracie kódy** Google Analytics 4, Google Ads a Meta Pixel až po súhlase s cookies, vrátane konverzie nákupu.
- **Pokročilé SEO:** presmerovania 301 (ručné aj automatické pri zmene adresy produktu, kategórie, stránky
  či článku, so skracovaním reťazí a počítadlom), šablóny titulku a popisu pre produkty a kategórie,
  štruktúrované dáta BreadcrumbList, Organization a WebSite s vyhľadávaním, „noindex“ pre filtrované výpisy,
  doplnkové riadky robots.txt.
- **PPL** (oficiálne API myAPI2): PPL Parcel na adresu (aj Business a Connect do EÚ), ParcelShopy a ParcelBoxy,
  dobierka, štítky, sledovanie.
- **SPS** (WebShip API): kuriér na adresu, dobierka, avízo príjemcovi, štítok, sledovanie podľa čísla objednávky.
- **Vlastné spôsoby dopravy:** neobmedzený počet s vlastným názvom, cenou, dodacou lehotou, krajinami
  doručenia, PSČ a rozsahom sumy objednávky. **Vlastné výdajné miesta** (predajne, partnerské miesta
  s otváracími hodinami) s vyhľadávaním v pokladni.
- **Balíkobot:** jedno napojenie na ďalších kuriérov (DPD, Geis, Toptrans, WE|DO, 123kuriér, Gebrüder Weiss…)
  s výberom kuriéra a služby pri spôsobe dopravy, výdajnými miestami, štítkami a sledovaním.

## 0.9.0 – Doprava a obsah (krok 4d)

- **Kuriéri:** jednotné rozhranie kuriéra, zásielky a štítky priamo z objednávky, hromadné vytvorenie zásielok
  a tlač štítkov pre vybrané objednávky, sledovacie číslo v e-maile a na stránke objednávky, pravidelná kontrola
  stavu cez webový cron a automatické vybavenie objednávky po doručení. Pri výpadkoch API kuriéra sa chyba
  zapíše do logu a kuriér sa po opakovaných výpadkoch dočasne neskúša.
- **Packeta:** výdajné miesta a Z-BOXy s mapou Packeta, doručenie domov, dobierka, štítky, sledovanie.
- **GLS** (MyGLS): ParcelShopy a GLS boxy (zoznam sa aktualizuje každý deň), doručenie domov s upozornením
  príjemcu, dobierka, štítky, sledovanie, testovacie prostredie.
- **Česká pošta** (B2B API): Balík Do ruky a Balíkovna (pošty, partnerské pobočky a BOXy, zoznam sa
  aktualizuje každý deň), dobierka, štítky aj hromadne, sledovanie stavu, testovacie prostredie.
- **Slovenská pošta** (API ePH): balík alebo Expres kuriér na adresu, dobierka na účet, poistenie, štítok,
  automatické odoslanie podacieho hárku a odkaz na sledovanie zásielky.
- **DPD:** sledovacie číslo z portálu DPD a odkaz na sledovanie (API DPD nie je verejne dokumentované).
- **Vlastný kuriér** bez napojenia so sledovacím číslom a vzorom adresy sledovania (SPS, pošta, vlastný rozvoz).
- **Doprava:** nový typ „výdajné miesto alebo box“, skutočné náklady na dopravu, vlastný rozvoz podľa PSČ,
  vylúčenie typov produktov a kategórií (napr. zvieratá iba osobne).
- **Blog:** články z blokov v každom jazyku, kategórie, štítky, čas čítania, obsah článku, koncepty,
  plánované zverejnenie, náhľad, história verzií, komentáre so schvaľovaním a ochranou proti spamu, RSS.
  Prepojenie s produktmi oboma smermi a s kategóriami katalógu, blok „Články blogu“.
- Nové bloky **Citát**, **Galéria** a **Výzva s tlačidlom**.

## 0.8.0 – Veľkosť obchodu a šablóny (krok 4c)

- **Veľkosť obchodu:** malý, stredný, veľký alebo vlastný – v inštalátore aj v Nastavenia → Funkcie obchodu,
  s prehľadom, čo sa zapne a vypne. Každá funkcia sa dá ďalej zapnúť alebo vypnúť samostatne.
- **Obsah → Vzhľad:** zoznam šablón s náhľadom, aktivácia, **náhľad pred aktiváciou** (vidí ho iba
  administrátor), nahratie a export ZIP, vytvorenie **odvodenej šablóny**, kontrola šablóny (lišta cookies,
  právne odkazy, prístupnosť).
- **Prispôsobenie so živým náhľadom:** vľavo nastavenia šablóny, vpravo stránka, ktorá sa mení hneď pri úprave.
- **Bloky:** úvodná stránka, stránky, kategórie a oblasti šablóny sa skladajú z blokov (banner, produkty,
  kategórie, text, obrázok, video, výhody obchodu, vlastný HTML), v každom jazyku.
- **Ponuky (menu)** v hlavičke a päte: vlastné odkazy, stránky, kategórie a produkty s podpoložkami.
- **Výber šablóny podľa obsahu** (napr. vlastná šablóna pre jednu kategóriu alebo typ produktu)
  a preklady šablóny, ktoré odvodená šablóna dedí od rodiča.
- **Editor súborov šablóny** so zálohami (iba hlavný administrátor, predvolene vypnutý).
- Nové šablóny **Fresh** (výrazné vyhľadávanie a lišta kategórií) a **Starter** (kostra pre vývojárov).
- Výber šablóny sa presunul z Nastavenia → Obchod do Obsah → Vzhľad.

## 0.7.0 – Rozšírený katalóg a sklad (krok 4b)

Všetky novinky sa zapínajú v **Nastavenia → Funkcie obchodu** (predvolene vypnuté) a ich parametre
sa menia v **Nastavenia → Katalóg**. Vypnutím sa nič nemaže.

- **Typy produktov:** sady vlastných polí podľa typu, merná jednotka a cena za kg/l/m, produkty bez dopravy
  (služby, digitálne), jedinečný kus (napr. zviera) – dá sa kúpiť iba raz.
- **Vlastné polia** aj pre kategórie, výrobcov, zákazníkov a objednávky (pole v pokladni), textové
  hodnoty v každom jazyku.
- **Doplnkové voľby** k produktu s príplatkom a voliteľným textom (gravírovanie, darčekové balenie).
- **Balíčky** viacerých produktov: dostupnosť a odpis skladu podľa zložiek, zobrazenie úspory.
- **Viac skladov** s poradím výdaja, **šarže** s dátumom výroby a spotreby, výdaj od najskoršej expirácie
  (FEFO), príjem, inventúra, presun a odpis, šarže na položkách objednávky (dohľadateľnosť).
- **Expirácia:** tovar sa prestane predávať po dátume (alebo X dní pred ním), automatická zľava v lehote
  upozornenia, dátum spotreby na webe.
- **Predobjednávky** s očakávaným dátumom a limitom kusov, aj čiastočné.
- **Digitálne produkty:** súbory na súkromnom disku, odkazy s limitom stiahnutí a platnosťou,
  upraviteľný e-mail s odkazmi.
- Odpis skladu si pamätá, odkiaľ sa tovar vydal – storno vráti presne to isté (aj zložky balíčka a šarže).

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
