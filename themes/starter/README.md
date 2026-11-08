# Šablóna Starter

Minimálna kostra **odvodenej šablóny** (child theme). Všetko, čo tu chýba, sa prevezme
z rodičovskej šablóny `default`. Upravujete iba to, čo chcete zmeniť, a aktualizácia
rodiča vaše úpravy neprepíše.

## Ako začať

1. Skopírujte priečinok `themes/starter` napr. do `themes/moja-sablona`
   (alebo v administrácii: **Obsah → Vzhľad → Vytvoriť odvodenú šablónu**).
2. V `theme.json` zmeňte `name` (malé písmená, číslice, pomlčky) a `title`.
3. Aktivujte šablónu v **Obsah → Vzhľad**. Pred aktiváciou si ju pozrite cez **Náhľad**.
4. Šablónu prenesiete do iného obchodu ako ZIP (**Exportovať ZIP** / **Nahrať šablónu**).

## Štruktúra

```
theme.json        názov, verzia, rodič, kompatibilita, nastavenia, ponuky, oblasti, štýly
screenshot.png    náhľad v administrácii (nepovinné, pomer 16 : 10)
layouts/          rozloženie stránky (layouts/app.blade.php)
templates/        stránky: home, category, product, cart, checkout, page, search, …
partials/         časti stránky: header, footer, product-card, …
blocks/           vzhľad blokov: banner, text, image, video, benefits, html, products, categories
assets/           obrázky, písma, hotové CSS a JS (adresa: theme_asset('custom.css'))
lang/{sk,cs,en}/  texty šablóny (__('theme::subor.kluc')); súbor s rovnakým názvom ako
                  u rodiča prepíše iba zmenené texty
```

## theme.json

| Kľúč | Význam |
|---|---|
| `parent` | Rodičovská šablóna, z ktorej sa preberajú chýbajúce súbory. |
| `requires.eshop` | Verzie jadra, s ktorými šablóna funguje, napr. `^0.8`. |
| `settings` | Nastavenia v **Prispôsobiť** (typy `color`, `select`, `toggle`, `text`, `number`, `image`). V šablóne: `$themeOptions->get('kluc')`. |
| `menus` | Miesta na ponuky, napr. `header`, `footer`. V šablóne: `$menus->items('header')`. |
| `areas` | Oblasti na bloky. V šablóne: `{!! block_area('nazov') !!}`. |
| `stylesheets`, `scripts` | Hotové súbory z `assets/` (bez kompilácie). |
| `vite` | Iba pre šablóny dodávané s obchodom (kompilujú sa pri vydaní). |

## Výber šablóny podľa obsahu

Obchod najprv hľadá konkrétnejší súbor, potom všeobecný:

- produkt: `templates/product-{adresa}`, `templates/product-type-{id typu}`, `templates/product`
- kategória: `templates/category-{adresa}`, `templates/category`
- stránka: `templates/page-{adresa}`, `templates/page` (ukážka: `templates/page-o-nas.blade.php`)

Adresa je adresa v predvolenom jazyku obchodu.

## Pravidlá

- Texty nepíšte napevno, používajte preklady v `lang/`.
- Rozloženie musí obsahovať lištu cookies (`@include('theme::partials.cookie-bar')`) a päta
  odkazy na právne dokumenty. Skontroluje to **Obsah → Vzhľad → Skontrolovať**.
- Šablóny obsahujú PHP kód (Blade) – nahrávajte iba šablóny z dôveryhodných zdrojov.
- Na hostingu sa nič nekompiluje: vlastné štýly dodajte ako hotový CSS súbor v `assets/`.
