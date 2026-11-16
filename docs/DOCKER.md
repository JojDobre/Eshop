# E-shop v Dockeri (lokálne na počítači)

Na skúšanie obchodu na vlastnom počítači bez webhostingu. Potrebujete
[Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows, macOS) alebo Docker s Compose (Linux).

## Spustenie

V adresári e-shopu (kde je `compose.yaml`):

```bash
docker compose up -d --build
```

Prvé zostavenie trvá niekoľko minút (stiahnu sa PHP knižnice a zostavia sa štýly). Potom otvorte
**http://localhost:8080** – spustí sa webový inštalátor:

| Pole v inštalátore | Hodnota |
|---|---|
| Server databázy | `db` (je predvyplnený) |
| Databáza | `eshop` |
| Používateľ | `eshop` |
| Heslo | `eshop` |

Administrácia je potom na **http://localhost:8080/admin**. Hotové demo nahráte v Vzhľad → Šablóny → Nahrať demo.

## Čo sa kde ukladá

- Celý obchod (súbor `.env` z inštalátora, nahraté obrázky, nastavenia) je v zväzku `app`, databáza v zväzku `db`.
  Po `docker compose down` a opätovnom `up` zostane všetko tak, ako ste to nechali.
- Aktualizácie robte rovnako ako na hostingu: v administrácii (Systém → Aktualizácie). Nové zostavenie obrazu
  (`--build`) už nainštalovaný obchod v zväzku `app` neprepíše.

## Užitočné príkazy

Zastavenie (dáta zostanú):

```bash
docker compose down
```

Príkaz `artisan` v kontajneri (napr. zoznam migrácií):

```bash
docker compose exec -u www-data app php artisan migrate:status
```

Úplne nová inštalácia – **zmaže obchod aj databázu**:

```bash
docker compose down -v
```

Ak je port 8080 obsadený, zmeňte v `compose.yaml` riadok `"8080:80"` napr. na `"8090:80"`.

## Poznámky

- E-maily sa v lokálnom obchode neodosielajú, kým v administrácii nenastavíte SMTP server.
- Platby kartou a kuriérov skúšajte len v testovacom režime príslušnej služby.
- Docker je len na skúšanie a vývoj. Na ostrý obchod použite inštalačný balík na webhostingu (`docs/INSTALL.md`).
