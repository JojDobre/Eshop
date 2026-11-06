#!/usr/bin/env bash
#
# Zostaví inštalačný balík e-shopu (ZIP) na nahratie na webhosting cez FTP.
#
# Balík obsahuje všetky PHP knižnice (vendor/) aj hotové štýly a skripty (public/build),
# takže na webhostingu netreba spúšťať Composer ani npm.
#
# Použitie:  scripts/build-release.sh            → dist/eshop-<verzia>.zip
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="$(sed -n "s/.*const VERSION = '\(.*\)';.*/\1/p" "$ROOT/app/Core/Eshop.php")"
DIST="$ROOT/dist"
STAGE="$(mktemp -d)"
TARGET="$STAGE/eshop"

trap 'rm -rf "$STAGE"' EXIT

echo "→ Zostavujem e-shop $VERSION"

# 1. Kópia zdrojových súborov z gitu (bez lokálnych súborov, .env a pod.).
mkdir -p "$TARGET"
git -C "$ROOT" archive HEAD | tar -x -C "$TARGET"

cd "$TARGET"

# 2. PHP knižnice bez vývojových nástrojov.
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet

# Vyčistenie knižníc: história gitu, testy a dokumentácia na webhostingu nie sú potrebné.
find vendor -type d \( -name .git -o -name .github -o -name tests -o -name Tests -o -name docs \) -prune -exec rm -rf {} +

# 3. Štýly a skripty.
npm ci --no-audit --no-fund --silent
npm run build --silent
rm -rf node_modules

# 4. Súbory administrácie (Filament) do public/.
APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" APP_ENV=production php artisan filament:assets --quiet

# 5. Odstránenie súborov, ktoré na webhostingu nepatria.
rm -rf tests scripts .github .editorconfig .gitattributes phpunit.xml package.json package-lock.json \
       vite.config.js .npmrc CLAUDE.md
rm -f storage/logs/*.log bootstrap/cache/*.php
find storage -name '*.json' -path '*eshop*' -delete

# 6. Adresáre, ktoré musia existovať a byť zapisovateľné.
mkdir -p storage/app/private storage/app/public storage/framework/{cache/data,sessions,views,testing} storage/logs bootstrap/cache public/uploads

# 7. Balík.
mkdir -p "$DIST"
ZIP="$DIST/eshop-$VERSION.zip"
rm -f "$ZIP"
(cd "$STAGE" && zip -qr -9 "$ZIP" eshop)

echo "✓ Hotovo: $ZIP ($(du -h "$ZIP" | cut -f1))"
