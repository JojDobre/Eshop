# Bezpečnosť

Prehľad bezpečnostného auditu pred verziou 1.0 (podľa OWASP Top 10) a odporúčania pre prevádzku.

## Čo e-shop zabezpečuje

| Oblasť (OWASP) | Riešenie |
|---|---|
| Kontrola prístupu (A01) | Každá sekcia administrácie vyžaduje oprávnenie (test `AdminAuthorizationTest` overuje všetky stránky a zdroje). Objednávky, reklamácie a rezervácie bez prihlásenia sú dostupné iba cez náhodný 40-znakový token. Digitálne súbory sa sťahujú cez podpísané odkazy. |
| Kryptografia (A02) | Heslá cez bcrypt, citlivé nastavenia (API kľúče kuriérov, platobných brán) šifrované kľúčom aplikácie, cookie relácie iba cez HTTPS, ak je adresa obchodu `https://`, hlavička HSTS. |
| Injekcia (A03) | Databáza iba cez Eloquent a viazané parametre (surové SQL bez vstupu používateľa). Výstup v šablónach sa escapuje, obsah z editora prechádza cez `safe_html()` (odstráni skripty, obsluhu udalostí, `javascript:` odkazy, iframe mimo YouTube/Vimeo). |
| Návrh (A04) | Pokladňa, kupóny, poukážky a rezervácie počítajú ceny na serveri, sklad a termíny sa zamykajú v transakcii (`lockForUpdate`). |
| Konfigurácia (A05) | Bezpečnostné hlavičky (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`), stránka Stav systému upozorní na zapnutý ladiaci režim, `.htaccess` blokuje prístup k neverejným adresárom. |
| Zastarané súčasti (A06) | `composer audit` a `npm audit` bez nálezov (pri vydaní 1.0). Kontrolujte ich pri každej aktualizácii. |
| Prihlásenie (A07) | Obmedzenie počtu pokusov o prihlásenie, registráciu a obnovu hesla, dvojfaktorové prihlásenie do administrácie, deaktivácia používateľov. |
| Integrita (A08) | Aktualizácie sú podpísané (Ed25519) a pred nasadením sa overuje podpis aj kontrolný súčet. Webhooky platobných brán overujú podpis. |
| Logovanie (A09) | Denné logy, záznam zmien objednávok a skladu, záznam súhlasov. |
| SSRF (A10) | Volania externých API iba na pevné adresy služieb (kuriéri, platby, preklady), nie na adresy zadané zákazníkom. |

## Nahrávanie súborov

- Obrázky iba PNG, JPEG, WebP (knižnica súborov aj GIF a PDF), s limitom veľkosti.
- `public/uploads/.htaccess` vypína spúšťanie PHP a blokuje skripty, HTML a SVG.
- Import dát a digitálne produkty sa ukladajú na súkromný disk mimo verejného adresára.
- Šablónu zo ZIP súboru (obsahuje spustiteľný kód Blade) môže nahrať iba hlavný správca.
  Editor súborov šablóny je tiež iba pre hlavného správcu a je predvolene vypnutý.

## Vedomé obmedzenia

- **Content-Security-Policy** sa neposiela: Alpine.js, meracie kódy a vložené kódy obchodníka by
  s prísnou politikou nefungovali. Dá sa doplniť na úrovni webservera.
- **Blok „HTML“** a kódy v nastaveniach cookies vkladajú obchodníkovi kód bez úprav – sú dostupné iba
  používateľom s oprávnením na obsah, resp. nastavenia.
- Na webserveri **nginx** sa `.htaccess` nepoužije – pravidlá z `public/uploads/.htaccess` treba prepísať
  do konfigurácie servera (pozri `docs/HOSTING.md`).

## Odporúčania pre prevádzku

1. Nastavte koreňový adresár webu na `public/` (ak to webhosting dovolí).
2. V súbore `.env` majte `APP_DEBUG=false` a adresu obchodu s `https://`.
3. Zapnite dvojfaktorové prihlásenie pre všetkých správcov.
4. Pravidelne aktualizujte e-shop a zálohujte na externé úložisko.

Bezpečnostný problém nahláste e-mailom autorovi projektu, nie verejne.
