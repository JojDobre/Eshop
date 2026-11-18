# Prehľad zmien

## 1.16.1 – Stránka údržby

- **Počas aktualizácie** návštevníci namiesto „503 Service Unavailable“ vidia stránku „Práve vylepšujeme obchod“
  (denný aj nočný vzhľad, názov obchodu, stránka sa po chvíli obnoví sama). Stránka je vopred pripravená,
  takže sa zobrazí aj počas výmeny súborov.
- **V administrácii** sa počas aktualizácie namiesto bieleho okna s chybou zobrazí nenápadné oznámenie
  „Prebieha aktualizácia obchodu“, ktoré po dokončení samo zmizne.

## 1.16.0 – Nová administrácia

- **Nový vzhľad administrácie** (denný aj nočný režim): pokojné povrchy, jemné linky, štítky namiesto prepínačov.
  Prepínač denného a nočného režimu je v hornej lište, voľba sa pamätá.
- **Produkty:** záložky Všetky / V obchode / Dochádza / Vypredané / Skryté s počtami, prehľad pod nadpisom,
  pod názvom SKU alebo počet variantov, kategórie ako štítky, sklad slovami s farebnou bodkou
  („Posledné 3 ks“, „Vypredané“), stav „V obchode“ / „Skrytý“. Akcie (upraviť, zobraziť/skryť, otvoriť v obchode,
  zmazať) sú v menu „…“, kliknutím na riadok sa produkt otvorí.
- **Všetky zoznamy** majú akcie v menu „…“ a záložky stavov tam, kde dávajú zmysel: objednávky, reklamácie,
  hodnotenia, články blogu, rezervácie, odberatelia newslettera a správy z formulárov.
- **Šablóny:** aktívna šablóna veľká hore s tlačidlom Prispôsobiť, ostatné ako karty s náhľadom; menej časté
  akcie (kontrola, export, demo, súbory) v menu „…“.
- **Prispôsobenie šablóny** úplne nanovo: horná lišta (stránka náhľadu, jazyk, uloženie – aj Ctrl+S), vľavo
  zoznam sekcií s preklikom do nastavení, vpravo živý náhľad v šírke počítača, tabletu alebo mobilu.
  Na mobile a tablete sa medzi nastaveniami a náhľadom prepína záložkami.
- **Správy z formulárov:** prehľadný detail (odosielateľ, e-mail a telefón na kliknutie, text správy v rámčeku),
  odpoveď s predvyplneným predmetom a označenie ako neprečítané.

## 1.15.0 – Minimálne množstvo a animované menu

- **Minimálne množstvo v objednávke** (Katalóg → Produkty → Sklad a Katalóg → Kategórie):
  - pri produkte: najmenej X kusov tohto produktu (varianty sa sčítajú),
  - pri kategórii: spoločné minimum pre produkty kategórie a podkategórií – druhy sa dajú kombinovať
    (napr. 3 + 7 vajec = 10 ks); produkty mimo kategórie (napr. klietka) sa nerátajú.
  Košík presne napíše, koľko kusov chýba, a pokladňu pustí až po splnení minima. Stránka produktu minimum
  ukáže vopred a pri vlastnom minime produktu predvyplní množstvo.
- **Farma: animované menu.** Mobilná ponuka sa plynulo vysunie, položky nabehnú postupne a ikona sa zmení na krížik
  (zatvára sa aj klávesom Esc). Podponuky na počítači sa jemne rozbalia. Pri vypnutých animáciách v systéme
  (prefers-reduced-motion) sa zobrazujú bez pohybu.

## 1.14.3 – Preklady bez prekvapení

- **Vymazaný text v predvolenom jazyku ostane prázdny.** Predtým sa namiesto neho zobrazil text iného jazyka
  (napr. slovenský popis v českom obchode). V ostatných jazykoch prázdny preklad naďalej znamená „použiť predvolený jazyk“.
- **Prázdny formátovaný text** (editor po vymazaní nechá „<p></p>“) sa berie ako prázdny – v inom jazyku sa teda
  zobrazí text predvoleného jazyka, nie prázdne miesto.
- **Kontrola prekladov:** jazykové súbory jadra, modulov a šablón majú v slovenčine, češtine aj angličtine rovnaké texty
  (doplnený preklad šablóny Starter, zjednotené kroky aktualizácie). Nové automatické testy zastavia vydanie, ak by
  niektorý preklad chýbal alebo bol prázdny – aj v nastaveniach šablón a modulov.

## 1.14.2 – Mazanie blokov a výber z knižnice

- **Oprava: zmazaný blok ostával na stránke.** Po zmazaní posledného bloku (napr. obrázka) v stránke, kategórii
  alebo na úvodnej stránke sa namiesto neho zobrazili bloky iného jazyka (napr. pôvodné z dema). Teraz sa uloží
  „bez blokov“ a v administrácii vidíte presne tie bloky, ktoré sú na stránke, aj keď pochádzajú z iného jazyka.
- **Výber obrázkov z knižnice:** mriežka po 24 obrázkoch so stránkovaním a hľadaním podľa názvu, vybrané obrázky
  sú hore nad mriežkou a tlačidlo „Použiť vybrané“ je stále viditeľné – netreba posúvať na koniec zoznamu.

## 1.14.1 – Banner so zaoblenými rohmi

- **Banner kategórie a stránky** sa dá zobraziť v šírke stránky so zaoblenými rohmi, rovnako ako obrázok na úvodnej
  stránke (Vzhľad → Šírka banneru). V šablóne Farma má rovnaké zaoblenie ako jej úvodný banner.

## 1.14.0 – Objednať znova, pripnuté články a blok Časté otázky

- **Zákaznícky účet:** tlačidlo **Objednať znova** v zozname objednávok aj na detaile objednávky (položky sa pridajú
  do košíka v aktuálnych cenách, nedostupné sa preskočia). Stavy objednávok majú farebné štítky, napr. fialová
  „Pripravená na vyzdvihnutie“. Na mobile sú objednávky kartičky namiesto tabuľky.
- **Pripnutý článok v blogu:** prepínač pri článku, článok sa zobrazí ako veľký banner s fotkou nad zoznamom článkov.
- **Blok Časté otázky:** rozbaľovacie otázky s odpoveďami, rozloženie vedľa seba alebo pod sebou, s údajmi
  FAQPage pre vyhľadávače. Dá sa vložiť na úvodnú stránku, do stránok aj kategórií.
- **Vzhľad kategórie (aj stránky):** banner len s obrázkom bez nadpisu, vypnutie odkazov na podkategórie a bloky
  kategórie nad produktmi alebo pod nimi.
- **Šablóna Farma:** sekcia O nás má štyri vzhľady – fotka vedľa textu, fotka na pozadí s kartou, farebná karta
  s výhodami a text na stred so širokou fotkou.
- **Opravy:** pole s počtom kusov v košíku a na produkte je širšie (dvoj- a trojciferné čísla sa už neorezávajú),
  počítadlo košíka v hlavičke šablón sa pri väčších číslach rozšíri.

## 1.13.1 – Varianty sa vytvoria samé

- **Možnosti variantov sa zobrazia v obchode hneď:** po uložení možnosti (napr. Farba s hodnotami) sa varianty
  vytvoria samé. Predtým bolo treba kliknúť aj na „Vygenerovať varianty“ a bez toho sa možnosti na stránke produktu
  neukázali v žiadnej šablóne.
- Pri aktualizácii sa varianty doplnia aj existujúcim produktom, ktoré majú možnosti, ale žiadne varianty.
- Prvé varianty dostanú sklad produktu, aby po vytvorení neboli vypredané. Ceny a sklad upravíte pri variantoch.

## 1.13.0 – Šablóna Dvor pre farmy

- **Nová šablóna Dvor** pre farmy a predaj farmárskych produktov: fotky v tvare vajíčka, kategórie v tvare
  stodoly, dni rozvozu a odberu ako lístky, produkty s cenovkou a balením, týždenná debnička s veľkosťami,
  adopcia zvieratiek, pozvánka na deň otvorených dverí a príbeh farmy s recenziami.
- **Balenie na karte produktu** (napr. „3,20 € / 10 ks“, „5,90 € / 250 g“) sa berie z typu produktu a množstva –
  netreba ho písať do názvu.
- **Adopcia zvieratiek** sú bežné produkty z vybranej kategórie (cena = mesačný príspevok); s typom produktu bez
  dopravy sa v pokladni nepýta doprava.
- **Päť paliet vrátane tmavej** (Lúka, Paradajka, Obloha, Noc, Jemná) alebo farba značky a tri písma nadpisov.
- **Hotové demo „Dvor Hrabovec“:** 17 produktov s typmi produktov (vajcia, mlieko, syry, zelenina, med),
  debnička vo veľkostiach, štyri zvieratká na adopciu, stránka s rezerváciou na deň otvorených dverí, texty v troch jazykoch.
- **Oprava dema Kapitola:** po importe sa v Nových vydaniach ukážu knihy (import dema nezachováva čas vytvorenia,
  poradie určuje ID).

## 1.12.0 – Šablóna Kapitola pre kníhkupectvá

- **Nová šablóna Kapitola** pre kníhkupectvá, papiernictvá a predaj e-kníh v štýle útulného kníhkupectva: úvod
  s fotkou cez celú šírku, vyhľadávaním podľa názvu či autora, žánrami a kartou „Kniha mesiaca“ s citátom,
  bestsellery na poličke s poradím, nové vydania, nálady ako veľké fotky, sekcia e-kníh a tlačiteľných súborov,
  papiernictvo a „Čo práve čítame“ s odporúčaniami kníhkupcov a pozvánkou na podujatie.
- **Karty pre knihy:** celá obálka s tieňom ako kniha, autor pod názvom, pri digitálnych produktoch označenie
  „Na stiahnutie“. Aj na detaile produktu sa obálka zobrazí celá, bez orezania.
- **Štyri palety vrátane tmavej** (Bordó, Les, Večer, Jemná) alebo farba značky a tri písma nadpisov.
- **Hotové demo:** 19 produktov – knihy (jedna vo väzbách pevná / brožovaná), e-knihy, plánovač a šablóna na
  stiahnutie, papiernictvo, darčekové balenie a záložka ako doplnkové voľby, texty v troch jazykoch.
- **Demo balíky** pri digitálnych produktoch zapnú funkciu Digitálne produkty.

## 1.11.0 – Šablóna Lupeň pre kvetinárstva

- **Nová šablóna Lupeň** pre kvetinárstva a predajne rastlín: tvar lupeňa na fotkách a blokoch, úvod s panelom
  „Kedy chcete doručiť?“ a odpočtom do uzávierky doručenia v ten istý deň, príležitosti ako vysoké fotky, kytice
  s veľkosťami a cenami priamo na karte (kliknutím na veľkosť sa pridá do košíka), výhody, predplatné, izbové
  rastliny z vybranej kategórie, ako to funguje a recenzie s fotkami.
- **Štyri palety vrátane tmavej** (Ruža, Lúka, Noc, Jemná) alebo farba značky a tri písma nadpisov. Tmavá paleta
  prispôsobí aj detail produktu, košík a formuláre.
- **Hotové demo „Margaréta“:** 12 produktov (kytice s veľkosťami, ruže podľa počtu kusov, rastliny), doplnkové
  voľby kartička / váza / pralinky, dátum doručenia a príjemca v pokladni, predplatné s formulárom, texty v troch jazykoch.
- **Demo balíky** prenášajú aj vlastné polia objednávky (pokladňa) a zapnú ich.

## 1.10.0 – Šablóna Butik pre módu

- **Nová šablóna Butik** pre butiky a módne značky v štýle magazínu: veľké fotky na celú šírku, úzke verzálky,
  posúvajúci sa pruh s výhodami, logo v strede, úvodný banner s dlaždicou a farebným výpredajom, novinky so
  záložkami podľa kategórií, kategórie ako veľké fotky, shop the look s bodmi na fotke, lookbook, „prečo my“
  a fotky z Instagramu.
- **Karty produktov pre módu:** druhá fotka pri prejdení myšou, farby variantov ako bodky a rýchle pridanie
  podľa veľkosti priamo z karty (vypredané veľkosti sú prečiarknuté).
- **Štyri palety** (Čierna, Kobalt, Ružová, Jemná) alebo farba značky a tri písma nadpisov (Anton, Syne, Archivo).
- **Hotové demo „Atelier“:** 14 produktov v 4 kategóriách s farbami a veľkosťami (73 variantov), tabuľka veľkostí,
  náš príbeh, shop the look, menu a texty v troch jazykoch.
- Oprava: súhlas pri odbere noviniek bol v šablóne Esencia na farebnom pozadí málo čitateľný.

## 1.9.0 – Šablóna Esencia pre kozmetiku a drogériu

- **Nová šablóna Esencia** pre kozmetiku a drogériu: moderný vzhľad na bielom pozadí s výraznými farbami,
  úvodný banner s nálepkou a dvoma akciami, rýchly výber podľa potreby, mozaika kategórií, bestsellery s objemami,
  cenou za liter a tlačidlom do košíka, výhody, rutina v 3 krokoch, zložky, banner s kvízom, recenzie
  s označením overeného nákupu a články z blogu. Ikony namiesto emoji.
- **Štyri farebné palety** na výber v Prispôsobení (Koral, Lila, Mäta, Jemná) alebo farba značky obchodu, a tri
  písma nadpisov. Paleta sa použije v celom obchode vrátane košíka a pokladne, text na farbe má vždy dostatočný kontrast.
- **Hotové demo „Lúka“:** 12 produktov v 5 kategóriách s výrobcami, objemami a cenou za liter, rutina, kvíz typu
  pleti s formulárom, blog s radami, menu a texty v troch jazykoch.
- **Košík:** pridanie viacerých produktov naraz (rutina v 3 krokoch) a suma košíka v hlavičke, ktorá sa
  aktualizuje aj pri pridaní bez obnovenia stránky.
- **Demo balíky:** prenášajú typy produktov (použije sa existujúci typ s rovnakým názvom) a pri importe zapnú
  funkcie katalógu, ktoré obsah dema používa (typy produktov, doplnkové voľby, predobjednávky).

## 1.8.0 – Šablóna Štúdio pre služby a rezervácie, preklady textov šablóny

- **Nová šablóna Štúdio** pre kaderníctva, kozmetiku, masáže, nechtové štúdiá a ďalšie služby s online rezerváciou:
  úvod s rezervačným panelom (služba + deň), čísla o štúdiu, služby s fotkami, dĺžkou a cenou s filtrom podľa
  kategórie, o štúdiu, tím, ako funguje rezervácia, darčeková poukážka a kozmetika domov, recenzie, časté otázky
  a kontakt s otváracími hodinami, navigáciou a tlačidlom Zavolať. Elegantné písmo Instrument Serif a Manrope.
- **Nové rezervačné stránky v Štúdiu:** cenník zoskupený podľa kategórií, výber dňa s počtom voľných termínov,
  časy rozdelené na doobeda / poobede / večer, súhrn rezervácie vedľa formulára a nové potvrdenie s pridaním do
  kalendára a navigáciou.
- **Rezervácie:** služby majú fotku a kategóriu (Rezervácie → Služby), počty voľných termínov v jednotlivých dňoch.
- **Hotové demo „Štúdio Mira“:** 10 služieb v 4 kategóriách, 4 zamestnankyne s rozvrhom, darčeková poukážka
  s hodnotami, kozmetika, storno podmienky, menu a nastavenia vzhľadu v troch jazykoch. Demo zapne modul Rezervácie.
- **Preklady textov šablóny:** v Prispôsobení prepnete Jazyk textov a nadpisy či odseky úvodnej stránky preložíte
  do každého jazyka obchodu; náhľad sa otvorí v danom jazyku. Demo Dielňa má teraz texty aj v češtine a angličtine.
- Demo balíky ukladajú odkazy na stránky obchodu ako relatívne adresy (fungujú aj na inej doméne) a obsahujú fotky
  služieb.

## 1.7.0 – Šablóna Dielňa a demo balíky

- **Nová šablóna Dielňa** pre handmade a remeselné obchody: úvod s fotkou v oblúku, otáčajúcou sa pečiatkou a ručne
  písanými poznámkami, pruh s výhodami, kategórie podľa remesla, novinky, príbeh tvorcu s podpisom, postup výroby,
  formulár na výrobu na mieru, recenzie, galéria a odber noviniek. Teplé farby (nastaviteľné v Prispôsobení), pätkové
  písmo Fraunces, ručné písmo Caveat. Všetky texty majú predvolené znenie v slovenčine, češtine a angličtine.
- **Hotové demo „Hlina & Vosk“** k šablóne Dielňa: 15 produktov s variantmi (glazúra, veľkosť) a doplnkami (vyrytý
  text, darčekové balenie), 6 kategórií s fotkami, stránky O mne a Výroba na mieru s formulárom, blog, menu a nastavenia
  vzhľadu – v troch jazykoch a s cenami v EUR aj CZK. Nahráte ho jedným klikom vo Vzhľad → Šablóny.
- **Demo balíky pre všetky šablóny:** Nahrať demo (dodané so šablónou alebo zo súboru) a Exportovať obsah ako demo.
  Režim Nahradiť (iba bez objednávok) alebo Pridať, záloha pred nahratím, prepojenie produktov, kategórií, formulárov
  a uložených sekcií v blokoch a menu. Právne stránky, logo, doprava ani platby sa neprepíšu.
- Oprava: položka menu odkazujúca na stránku nemala názov.

## 1.6.0 – Automatické aktualizácie, Google Disk, rýchle obrázky, uložené sekcie

- **Automatické aktualizácie:** Systém → Aktualizácie zistí novú verziu na GitHube a nainštaluje ju jedným
  kliknutím (stiahnutie, kontrola SHA-256 a podpisu, záloha, vrátenie pri chybe). V nastaveniach zapnete e-mail
  o novej verzii alebo automatickú inštaláciu každú noc. Odznak v menu ukáže dostupnú verziu.
- **Zálohy na Google Disk:** prepojenie Google účtu cez OAuth, zálohy sa nahrávajú po častiach do vlastného
  priečinka, staré kópie sa mažú podľa nastavenia.
- **Rýchlejšie a ostré obrázky:** pre každý obrázok sa vytvoria WebP verzie od 320 po 2560 px a prehliadač si
  vyberie veľkosť podľa obrazovky aj hustoty pixelov (ostré aj na mobile s Retina displejom, najväčšou možnosťou je
  vždy originál). Fotku produktu otvoríte kliknutím v plnej kvalite cez celú obrazovku (šípky, Esc). Dlhšie
  ukladanie obrázkov v prehliadači.
- **Uložené sekcie:** ľubovoľný blok uložíte do knižnice (ikona záložky pri bloku) a vložíte blokom „Uložená
  sekcia“ na úvodnú stránku, stránky, kategórie či články – úprava v knižnici (Obsah → Bloky → Uložené sekcie)
  sa prejaví všade.
- **Zobrazenie podľa zariadenia:** pri každom bloku a sekcii voľba Všetky zariadenia / Iba počítač / Iba mobil.
- **Hotové šablóny sekcií:** tlačidlo „Vložiť hotovú sekciu“ pod blokmi – úvodný banner, výhody obchodu,
  vlastnosti v stĺpcoch, novinky, akcie, recenzie, o nás, výzva na akciu, časté otázky, kontakt.
- **Otestovaný celý nákup** (55 automatických kontrol v prehliadači) a opravy: limity požiadaviek sa už nesčítavajú
  naprieč akciami (po pridaní viacerých produktov do košíka sa nedal uplatniť kód poukážky ani odoslať objednávka),
  košík ukazuje sumu po zľave a platbu poukážkou a dopravu zadarmo počíta zo sumy po zľave ako pokladňa, chyby
  v inštalátore a formulároch majú čitateľné názvy polí, v objednávke sa rozlišuje spôsob a stav platby, opravená
  lišta oznamu v šablóne Farma.

## 1.5.0 – Skladanie vlastných sekcií, vzhľad stránok

- **Vlastné sekcie ako v page builderi:** nový blok „Sekcia so stĺpcami“ (1 až 4 stĺpce, rôzne pomery šírky,
  pozadie – svetlé, farba obchodu, tmavé alebo obrázok –, šírka, odsadenie, zarovnanie). Do každého stĺpca vložíte
  ďalšie bloky. Nové prvky: Nadpis (s podnadpisom, veľkosťou a zarovnaním), Obrázok s textom, Tlačidlá, Karta
  s ikonou a Medzera / čiara. Bloky fungujú na úvodnej stránke, v stránkach, kategóriách aj článkoch.
- **Vzhľad stránok a kategórií:** klasický nadpis, banner s obrázkom (nadpis a text na obrázku, tri výšky) alebo bez
  nadpisu (stránka iba z blokov), šírka textu, zobrazenie navigácie; pri kategórii aj skrytie popisu (banner
  použije obrázok kategórie).
- Jazyk a mena v mobilnom menu ako tlačidlá vedľa seba (namiesto rozbaľovacieho zoznamu, ktorý vypadával mimo
  obrazovku).

## 1.4.0 – Bloky v prispôsobení šablóny, rozloženie blogu

- **Bloky úvodnej stránky priamo v Prispôsobení šablóny** (Vzhľad → Prispôsobiť → Bloky úvodnej stránky) so živým
  náhľadom: produkty z kategórie, text, obrázok, video, formulár a ďalšie. Zobrazia sa na mieste sekcie „Bloky
  z obsahu“. Stránka Úvodná stránka a bloky (ďalšie jazyky a oblasti šablóny) je teraz pod Vzhľadom.
- **Bloky preberajú štýl šablóny:** nadpisy, odkazy „Zobraziť všetko“ a rozostupy blokov vyzerajú vo Farme rovnako
  ako jej vlastné sekcie. Blok produktov jednej kategórie má odkaz na celú kategóriu.
- **Názov obchodu v hlavičke vždy celý** (bez „…“): písmo sa podľa miesta zmenší, prípadne sa zalomí. Na mobile sú
  prepínače meny a jazyka v menu.
- **Rozloženie blogu v Prispôsobení šablóny (skupina Blog):** poradie sekcií zoznamu (nadpis, kategórie, zvýraznený
  článok, články), mriežka / zoznam / veľké karty, počet stĺpcov, bočný panel s kategóriami, štítkami a najnovšími
  článkami, perex a dátum; pri článku hlavička (klasická, obrázok cez celú šírku, obrázok vedľa nadpisu, bez obrázka),
  šírka textu a poradie sekcií (obsah, produkty, štítky, komentáre, súvisiace články).
- Oprava: obrázky nastavené v prispôsobení šablóny sa v živom náhľade zobrazujú aj pred uložením.

## 1.3.1 – Opravy jazykov a triedenia

- Oprava: po zmene predvoleného jazyka obchodu (napr. zo slovenčiny na češtinu) produkty, kategórie a stránky
  prišli o názvy, popisy a adresy. Obsah sa teraz pri zmene jazyka doplní do nového jazyka z pôvodného (preklady
  potom upravíte v administrácii); aktualizácia to opraví aj v obchode, kde sa jazyk už zmenil.
- Oprava: text bez prekladu sa v ďalšom jazyku obchodu zobrazí v predvolenom jazyku (predtým zostal prázdny).
- Oprava: triedenie podľa ceny v inej mene (napr. EUR popri CZK) radilo zle produkty, ktorých cena sa do meny
  prepočítava kurzom. Teraz sa triedi podľa ceny, ktorú zákazník vidí. Aj triedenie podľa názvu berie
  názov v predvolenom jazyku, keď preklad chýba.

## 1.3.0 – Nový vzhľad administrácie

- **Moderný a plne responzívny vzhľad administrácie:** čisté karty, prehľadné menu, výrazné hlavné tlačidlá,
  akcie v tabuľkách ako ikony s popisom. Tabuľky sa na mobile a v úzkom priestore zobrazia ako karty s popiskami
  stĺpcov (na tablete v dvoch stĺpcoch) – nikde netreba posúvať riadky do strany. Prešli sme všetky stránky
  administrácie v šírke počítača, tabletu aj mobilu.
- **Kratšie menu:** menej používané položky sú pod hlavnou položkou (napr. Štítky, Parametre a Typy produktov pod
  Produktmi, Doklady a Vrátenie pod Objednávkami, Formuláre pod Správami).
- **Nástenka:** rýchle akcie (pridať produkt, nové objednávky, správy, hodnotenia na schválenie, úvodná stránka,
  vzhľad) s počtom položiek, ktoré čakajú na vybavenie.
- **Funkcie sa zapínajú na jednom mieste** (Nastavenia → Funkcie obchodu). Stránky nastavení funkcie už nemajú druhý
  prepínač; keď je funkcia vypnutá, zobrazia upozornenie s odkazom na zapnutie.
- **Editor:** tlačidlo Video (YouTube, Vimeo); editor aj v krátkom popise produktu.
- **Prispôsobenie šablóny:** skupiny nastavení sa dajú zbaliť, sekcie úvodnej stránky v kompaktnej tabuľke, šablóny
  môžu skryť zdedené nastavenia, ktoré nepoužívajú (Farma už neukazuje druhý oznamový pruh ani tvar tlačidiel).
- Dátumy v slovenskom formáte s kalendárom, kalendár rezervácií na mobile po dňoch pod sebou, gramaticky správne
  texty prázdnych tabuliek a nových záznamov, kratší odkaz „Z knižnice“, opravené rozloženie nastavení Obchod a Značka.

## 1.2.3 – Oprava nahrávania veľkých súborov

- Oprava: aktualizačný balík (vyše 30 MB) ani väčšiu zálohu sa nedalo nahrať v administrácii – knižnica Livewire
  predvolene prijme najviac 12 MB. Limit je teraz daný poľom (aktualizácia 500 MB, záloha 2 GB) a nastavením PHP.

## 1.2.2 – Košík bez obnovenia stránky

- Pridanie do košíka (tlačidlo „+“ na karte produktu, detail produktu, porovnanie) prebehne bez obnovenia stránky:
  tlačidlo ukáže potvrdenie, počet v košíku v hlavičke sa hneď zmení. Bez JavaScriptu funguje ako doteraz.
- Upozornenia obchodu (pridanie do košíka, chyby, potvrdenia) sú plávajúce okná vpravo dole, na mobile dole.
- Šablóna Farma na mobile: bez riadku s kategóriami pod hlavičkou (sú v menu), tlačidlo menu je vpravo.

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
