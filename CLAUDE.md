# Pokyny pre vývoj e-shopu

Univerzálny e-shop pre bežný webhosting. Plán projektu: `PLAN.md`. Architektúra: `docs/ARCHITECTURE.md`.

## Princípy

- Staviame e-shop, nie framework. Používaj Laravel a overené balíky (Events, Queue, Migrations,
  Policies, Service Container, Filesystem). Vlastnú vrstvu pridávaj iba kvôli modulárnosti.
- Musí bežať na lacnom webhostingu: PHP 8.3+, MySQL 8.0+ / MariaDB 10.6+, bez Redis, bez
  procesov bežiacich na pozadí, bez symbolických odkazov. Na hostingu sa nič nekompiluje.
- Obchodná logika patrí do akcií a služieb, nie do kontrolérov ani Filament stránok.
- Texty v rozhraní nikdy nepíš napevno: `lang/{sk,cs,en}` (jadro), `themes/*/lang` (šablóna).
- Komentáre v kóde píšeme po slovensky, názvy tried a metód po anglicky.

## Štruktúra

- `app/Core` – jadro: moduly (`Modules`), inštalátor (`Installer`), šablóny (`Themes`)
- `modules/*` – interné moduly (`module.json` + Service Provider odvodený od `ModuleServiceProvider`)
- `themes/*` – šablóny vzhľadu obchodu (`theme.json`, Blade šablóny pod menným priestorom `theme::`)
- `app/Filament` – administrácia (Filament 5)

## Príkazy

- Testy: `php artisan test` (predvolene SQLite v pamäti; CI aj na MySQL)
- Štýl kódu: `vendor/bin/pint`
- Štýly a skripty: `npm run build`
- Inštalačný balík: `scripts/build-release.sh`
