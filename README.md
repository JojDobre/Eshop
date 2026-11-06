# E-shop

Univerzálny e-shop pre bežný webhosting: oblečenie, nábytok, potraviny, služby, zvieratá aj digitálne produkty.
Jedna inštalácia = jeden obchod s vlastným názvom, logom, farbami a šablónou vzhľadu.

> **Stav:** krok 1 – technický prototyp (verzia 0.1.0). Obchod zatiaľ nepredáva, obsahuje inštalátor,
> administráciu, nastavenia, používateľov, systém modulov a prvú šablónu vzhľadu. Plán: [`PLAN.md`](PLAN.md).

## Čo je hotové

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
