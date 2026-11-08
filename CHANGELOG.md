# Prehľad zmien

## 0.10.0 – Predaj a marketing (krok 4e, v príprave)

- **Zákaznícke skupiny a B2B ceny:** skupiny so zľavou, vlastné ceny produktov a variantov pre skupinu
  v každej mene, predvolená skupina pre návštevníkov, zobrazenie cien bez DPH pre firmy. Zapína sa
  v Nastavenia → Funkcie obchodu.
- **Množstevné zľavy** pri produkte (pre všetkých alebo pre skupinu) s tabuľkou na stránke produktu.
- **Blokovanie zákazníkov:** zablokovaný zákazník sa neprihlási a neobjedná ani bez prihlásenia.
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
