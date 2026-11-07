# Inštalácia na webhosting

Tento návod je pre obchodníka alebo správcu webu. Netreba SSH ani príkazový riadok.

## 1. Čo budete potrebovať

- webhosting s **PHP 8.3+** a databázou **MySQL 8.0+** alebo **MariaDB 10.6+**,
- prístup cez **FTP** (napr. program FileZilla),
- **prázdnu databázu** a jej prístupové údaje (server, názov databázy, používateľ, heslo) –
  vytvoríte ju v administrácii webhostingu.

> Tip: v administrácii webhostingu skontrolujte verziu PHP. Ak je nižšia ako 8.3, zvýšte ju (väčšinou je to jeden prepínač).

## 2. Nahratie súborov

1. Rozbaľte `eshop-<verzia>.zip` vo svojom počítači. Vznikne priečinok `eshop`.
2. Obsah priečinka `eshop` nahrajte cez FTP:
   - **Odporúčané:** ak webhosting dovoľuje nastaviť *koreňový adresár domény* (DocumentRoot),
     nahrajte súbory napr. do `/eshop` a koreňový adresár domény nastavte na `/eshop/public`.
   - **Jednoduchšie:** nahrajte obsah priamo do koreňového adresára webu (napr. `www`, `public_html`).
     Súbor `.htaccess` sa postará o zvyšok a neverejné súbory zablokuje.
   - E-shop môže bežať aj v podadresári, napr. `https://mojweb.sk/obchod`.
3. Skontrolujte, že sa nahrali aj skryté súbory začínajúce bodkou (`.htaccess`, `.env.example`).

## 3. Sprievodca inštaláciou

Otvorte adresu webu v prehliadači. Automaticky sa spustí sprievodca:

1. **Vitajte** – výber jazyka inštalátora.
2. **Požiadavky** – kontrola PHP, rozšírení a práv na zápis. Ak niečo chýba, sprievodca ukáže čo.
   Adresáre `storage/`, `bootstrap/cache/` a `public/uploads/` musia byť zapisovateľné
   (väčšinou sú; ak nie, nastavte im cez FTP práva 755 alebo 775).
3. **Databáza** – zadajte údaje k databáze. Sprievodca overí pripojenie a verziu.
   *Prefix tabuliek* vyplňte iba vtedy, ak v databáze beží aj iná aplikácia.
4. **Obchod a administrátor** – názov obchodu, kontaktný e-mail, jazyk, mena, krajina
   a prihlasovacie údaje administrátora (heslo aspoň 10 znakov, písmená aj čísla).
5. **Hotovo** – odkaz do administrácie (`/admin`) a do obchodu.

Po inštalácii je sprievodca automaticky vypnutý.

## 4. Cron (odporúčané)

Cron spúšťa pravidelné úlohy (neskôr e-maily, kurzy mien, zálohy). V administrácii webhostingu
nastavte spúšťanie **každú minútu**. Presný príkaz zobrazí posledný krok inštalátora, napríklad:

```
* * * * * /usr/bin/php /cesta/k/eshopu/artisan schedule:run >> /dev/null 2>&1
```

## 5. Po inštalácii

V administrácii (`/admin`) nastavte:

- **Nastavenia → Značka:** logo, favicon a hlavnú farbu,
- **Nastavenia → Firemné údaje:** obchodné meno, IČO, DIČ, IČ DPH, adresu a bankový účet,
- **Nastavenia → E-maily:** SMTP server (údaje k e-mailovej schránke z webhostingu) a odošlite testovací e-mail.
  Bez toho zákazníci nedostanú potvrdenie objednávky.
- **Nastavenia → Doprava a Platby:** ceny dopravy, hranicu pre dopravu zadarmo, adresu predajne pri osobnom odbere.
- **Obsah → Stránky:** doplňte obchodné podmienky a ochranu osobných údajov (predvolene je tam iba kostra).
- **Katalóg:** kategórie a produkty (ukážkové produkty môžete zmazať).
- **Systém → Používatelia:** ďalšie účty pre kolegov.

## Riešenie problémov

| Problém | Riešenie |
|---|---|
| Biela stránka alebo chyba 500 | Skontrolujte verziu PHP (8.3+) a súbor `storage/logs/laravel-<dátum>.log`. |
| Chyba 404 na každej stránke | Webhosting nepodporuje `.htaccess` / mod_rewrite. Kontaktujte podporu webhostingu. |
| „Adresár nie je zapisovateľný" | Nastavte cez FTP práva 775 pre `storage`, `bootstrap/cache` a `public/uploads`. |
| Obchod nefunguje po zapnutí modulu | Do súboru `.env` doplňte `ESHOP_SAFE_MODE=true` (záchranný režim), v administrácii modul vypnite a riadok potom odstráňte. |
| Chcem inštaláciu zopakovať | Zmažte súbory `.env` a `storage/app/eshop/installed.json` a použite prázdnu databázu. |
