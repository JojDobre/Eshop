#!/usr/bin/env bash
#
# Overí prechod databázy zo všetkých vydaných verzií na aktuálnu: pre každé vydanie vytvorí čistú
# databázu, spustí migrácie danej verzie a potom migrácie aktuálneho kódu.
#
# Použitie: DB_USERNAME=root DB_PASSWORD=… scripts/test-upgrades.sh
# (potrebuje MySQL/MariaDB a právo vytvárať databázy)
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_NAME="eshop_upgrade_test"
WORK="$(mktemp -d)"
trap 'git -C "$ROOT" worktree prune; rm -rf "$WORK"' EXIT

mysql_cmd() { mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" ${DB_PASSWORD:+-p"$DB_PASSWORD"} "$@"; }

# Vydania: commity „Vydanie …“ a „Verzia …“ (od pilotnej verzie 0.5.0).
mapfile -t RELEASES < <(git -C "$ROOT" log --reverse --format='%H %s' | grep -iE '(vydanie|verzia) 0\.[0-9]+\.[0-9]+' | awk '{print $1}')
STATUS=0

for COMMIT in "${RELEASES[@]}"; do
    VERSION="$(git -C "$ROOT" show "$COMMIT:app/Core/Eshop.php" | sed -n "s/.*const VERSION = '\(.*\)';.*/\1/p")"
    echo "→ $VERSION → aktuálna verzia"

    mysql_cmd -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    TREE="$WORK/$VERSION"
    git -C "$ROOT" worktree add -q --detach "$TREE" "$COMMIT"
    ln -s "$ROOT/vendor" "$TREE/vendor"
    STATE="$WORK/state-$VERSION"
    mkdir -p "$STATE"
    echo "{\"version\":\"$VERSION\",\"installed_at\":\"2026-01-01T00:00:00+00:00\"}" > "$STATE/installed.json"

    ENVVARS=(APP_ENV=testing APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" DB_CONNECTION=mysql DB_HOST="$DB_HOST" DB_PORT="$DB_PORT"
             DB_DATABASE="$DB_NAME" DB_USERNAME="$DB_USERNAME" DB_PASSWORD="$DB_PASSWORD" ESHOP_STATE_PATH="$STATE" CACHE_STORE=array SESSION_DRIVER=array)

    if ! (cd "$TREE" && env "${ENVVARS[@]}" php artisan migrate --force --no-interaction > "$WORK/old.log" 2>&1); then
        echo "  ✗ migrácie verzie $VERSION zlyhali"; tail -5 "$WORK/old.log"; STATUS=1; continue
    fi

    if (cd "$ROOT" && env "${ENVVARS[@]}" php artisan migrate --force --no-interaction > "$WORK/new.log" 2>&1); then
        TABLES="$(mysql_cmd -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME'")"
        echo "  ✓ v poriadku ($TABLES tabuliek)"
    else
        echo "  ✗ prechod z $VERSION zlyhal"; tail -15 "$WORK/new.log"; STATUS=1
    fi

    git -C "$ROOT" worktree remove --force "$TREE"
done

mysql_cmd -e "DROP DATABASE IF EXISTS $DB_NAME"
exit $STATUS
