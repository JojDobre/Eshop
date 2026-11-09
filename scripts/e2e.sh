#!/usr/bin/env bash
#
# E2E test inštalačného balíka: rozbalí dist/eshop-<verzia>.zip do dočasného adresára, spustí ho
# vstavaným serverom PHP a prejde inštaláciu, nákupy a administráciu v prehliadači (Playwright).
#
# Použitie: DB_USERNAME=… DB_PASSWORD=… scripts/e2e.sh [balík.zip]
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ZIP="${1:-$(ls -t "$ROOT"/dist/eshop-[0-9]*.zip | head -1)}"
DB_HOST="${DB_HOST:-127.0.0.1}"; DB_PORT="${DB_PORT:-3306}"; DB_NAME="${DB_NAME:-eshop_e2e}"
DB_USERNAME="${DB_USERNAME:-root}"; DB_PASSWORD="${DB_PASSWORD:-}"
PORT="${E2E_PORT:-8199}"
WORK="$(mktemp -d)"

cleanup() { pkill -f "php -S 127.0.0.1:$PORT" 2>/dev/null || true; rm -rf "$WORK"; }
trap cleanup EXIT

mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" ${DB_PASSWORD:+-p"$DB_PASSWORD"} -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
unzip -q "$ZIP" -d "$WORK"
(cd "$WORK/eshop/public" && php -S "127.0.0.1:$PORT" "$WORK/eshop/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php" > "$WORK/server.log" 2>&1) &
SERVER=$!
sleep 1

node "$ROOT/scripts/e2e.cjs" "http://127.0.0.1:$PORT" "$DB_HOST" "$DB_PORT" "$DB_NAME" "$DB_USERNAME" "$DB_PASSWORD"
node "$ROOT/scripts/a11y-check.cjs" "http://127.0.0.1:$PORT" / /kosik
