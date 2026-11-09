# Príručka pre obchodníkov

Ako obchod nastaviť a prevádzkovať. Inštalácia je v [INSTALL.md](INSTALL.md), požiadavky na hosting
v [HOSTING.md](HOSTING.md).

## Prvé kroky po inštalácii

1. **Nastavenia → Všeobecné:** názov obchodu, kontaktný e-mail a telefón, jazyky a meny.
2. **Nastavenia → Firma:** obchodné meno, adresa, IČO, DIČ, IČ DPH a bankový účet (zobrazia sa na
   faktúrach, v pätičke a pri platbe prevodom).
3. **Nastavenia → Značka:** logo, ikona a hlavná farba obchodu.
4. **Nastavenia → E-maily:** odosielanie cez SMTP (bez neho e-maily neodchádzajú).
5. **Obsah → Stránky:** doplňte obchodné podmienky, ochranu osobných údajov, dopravu a platbu,
   kontakt a vyhlásenie o prístupnosti. Vzorové texty treba prispôsobiť a dať skontrolovať právnikovi.
6. **Predaj → Doprava a Platby:** zapnite spôsoby dopravy (kuriéri, výdajné miesta, osobný odber)
   a platby (prevod s QR kódom, dobierka, karta cez Stripe).
7. **Systém → Stav systému:** skontrolujte, či beží cron a či je všetko zelené.

## Veľkosť obchodu a funkcie

**Nastavenia → Funkcie obchodu** zapína funkcie podľa veľkosti obchodu (malý, stredný, veľký) alebo
jednotlivo. Moduly (kuriéri, blog, rezervácie, hodnotenia, účtovné exporty…) zapnete v
**Systém → Moduly**. Každá funkcia má vlastné nastavenia na mieste, kam odkazuje.

## Produkty a sklad

- **Katalóg → Produkty:** názov, popis, fotky, cena (aj v ďalších menách a pre zákaznícke skupiny),
  sklad, varianty (veľkosť, farba), doplnkové voľby, vlastné polia, údaje o výrobcovi (GPSR) a SEO.
- **Kategórie** môžu mať obrázok, popis a vlastné bloky. Pri zmene adresy sa stará adresa presmeruje.
- **Typy produktov** určujú, či tovar potrebuje dopravu, či je to jedinečný kus alebo digitálny produkt.
- **Import a export** (Katalóg → Import / Export): CSV a XLSX vrátane variantov a kategórií.

## Objednávky

V **Predaj → Objednávky** meníte stav, vystavujete faktúry a dobropisy, tlačíte štítky zásielok
a posielate zákazníkovi e-maily. Texty e-mailov upravíte v **Obsah → E-mailové šablóny**.
Reklamácie a odstúpenia sú v **Predaj → Reklamácie** (protokol v PDF, dobropis jedným klikom).

## Vzhľad obchodu

**Vzhľad → Šablóny:** výber šablóny s náhľadom (Default, Fresh, Farma). **Prispôsobiť** mení farby,
písmo, banery, oznam v hornom pruhu a texty úvodnej stránky so živým náhľadom. Úvodnú stránku,
stránky aj kategórie môžete poskladať z blokov (**Vzhľad → Bloky**). Ponuky v hlavičke a pätičke
upravíte v **Obsah → Ponuky**.

## Marketing

Newsletter s dvojitým potvrdením (Mailchimp, Ecomail), pripomienka opusteného košíka, XML feedy
(Heureka, Google, Facebook), meracie kódy (GA4, Google Ads, Meta Pixel – až po súhlase s cookies),
kupóny, darčekové poukážky, hodnotenia produktov a porovnávač.

## Zákonné povinnosti

- **Súhlasy a cookies:** lišta cookies s výberom kategórií, záznam súhlasu s obchodnými podmienkami.
- **Ochrana údajov (GDPR):** **Nastavenia → Ochrana údajov** – ako dlho sa uchovávajú logy, IP adresy,
  košíky, objednávky a ďalšie údaje. Zákazník si môže účet zrušiť sám.
- **DPH a OSS:** sadzby podľa krajín, prenesenie daňovej povinnosti s overením IČ DPH (VIES).
- **Omnibus:** najnižšia cena za posledných 30 dní pri zľave.
- **Evidencia obalov (EPR):** **Predaj → Evidencia obalov** – hmotnosť obalov pri produktoch a zásielkach,
  výkaz za štvrťrok alebo rok pre organizáciu zodpovednosti výrobcov.
- **Prístupnosť:** šablóny spĺňajú WCAG 2.1 AA, doplňte vyhlásenie o prístupnosti.

## Prevádzka

- **Zálohy** (Systém → Zálohy): automaticky, s heslom a kópiou na externé úložisko. Po každej zálohe sa
  overí, že sa dá obnoviť.
- **Aktualizácie** (Systém → Aktualizácie): podpísané balíky, záloha pred inštaláciou, vrátenie jedným klikom.
- **Logy** a **Stav systému** ukážu chyby a čo treba opraviť.
- **Používatelia a role:** správcom dajte iba oprávnenia, ktoré potrebujú, a zapnite si dvojfaktorové
  prihlásenie (Profil).
