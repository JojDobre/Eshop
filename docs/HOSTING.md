# Webhosting

## Požiadavky

| | Minimum |
|---|---|
| PHP | 8.3 alebo novšie (8.3, 8.4, 8.5 sú testované v CI) |
| Rozšírenia PHP | pdo_mysql, mbstring, intl, zip, bcmath, gd, sodium, openssl, curl, fileinfo |
| Databáza | MySQL 8.0+ alebo MariaDB 10.6+ (testované MySQL 8.0, 8.4, MariaDB 10.6, 11.4) |
| Webserver | Apache s `mod_rewrite` a `.htaccess` (odporúčané), alebo nginx |
| Cron | odporúčaný (každú minútu); bez neho sa použije webový cron pri návštevách |
| Limity | `memory_limit` aspoň 256M, `max_execution_time` aspoň 60 s, nahrávanie súborov aspoň 32M |

Netreba Redis, SSH, Composer ani Node.js – balík obsahuje všetko hotové.

## Apache

Ak sa dá, nastavte koreňový adresár webu (DocumentRoot) na `public/`. Inak nahrajte celý balík do
koreňa webu – súbor `.htaccess` v koreni presmeruje požiadavky do `public/` a zablokuje neverejné
adresáre. `public/uploads/.htaccess` zakazuje spúšťanie skriptov v nahraných súboroch.

## nginx

```nginx
server {
    server_name obchod.sk;
    root /var/www/eshop/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Nahrané súbory sa nikdy nespúšťajú (náhrada za public/uploads/.htaccess).
    location ^~ /uploads/ {
        location ~* \.(php\d?|phtml|phar|pl|py|cgi|sh|html?|svg|js)$ { deny all; }
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

## Cron

```bash
* * * * * php /cesta/k/eshop/artisan schedule:run >> /dev/null 2>&1
```

Cron spúšťa zálohy, mazanie starých údajov, pripomienky rezervácií, opustené košíky, kurzy mien
a synchronizáciu výdajných miest. Či beží, ukazuje **Systém → Stav systému**.

## Overené webhostingy

Postup inštalácie bol overený na čistej inštalácii z balíka (Apache aj vstavaný server PHP) s MySQL 8.0
a MariaDB 10.6/11.4. Výsledky z konkrétnych komerčných webhostingov (WebSupport, Wedos, Active24, Forpsi,
Hostinger) doplňte sem po nasadení – jediné, čo sa medzi nimi líši, je nastavenie verzie PHP, DocumentRoot
a cronu v ich administrácii.
