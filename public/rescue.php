<?php

/*
 * Záchranný skript e-shopu – funguje aj vtedy, keď sa obchod ani administrácia nenačítajú.
 *
 * Vie vrátiť poslednú aktualizáciu, vypnúť režim údržby, vymazať vyrovnávacie pamäte a obnoviť
 * databázu zo zálohy. Nepoužíva Laravel, iba PHP a PDO.
 *
 * Zapnutie: do súboru .env doplňte riadok ESHOP_RESCUE_KEY=<aspoň 24 náhodných znakov>.
 * Po použití riadok zmažte. Bez kľúča skript nič neurobí.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$env = [];

foreach (is_file($root.'/.env') ? (array) file($root.'/.env', FILE_IGNORE_NEW_LINES) : [] as $line) {
    if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/', $line, $m)) {
        $env[$m[1]] = trim($m[2], "\"'");
    }
}

// Texty v troch jazykoch (preklady Laravelu tu nie sú k dispozícii).
$lang = substr((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'sk'), 0, 2);
$texts = [
    'sk' => ['title' => 'Záchrana e-shopu', 'disabled' => 'Záchranný skript je vypnutý. Zapnete ho riadkom ESHOP_RESCUE_KEY=… (aspoň 24 znakov) v súbore .env.', 'key' => 'Záchranný kľúč', 'rollback' => 'Vrátiť poslednú aktualizáciu', 'up' => 'Vypnúť režim údržby', 'cache' => 'Vymazať vyrovnávacie pamäte', 'restore' => 'Obnoviť databázu zo zálohy', 'password' => 'Heslo zálohy (ak je zašifrovaná)', 'confirm' => 'Naozaj? Databáza sa prepíše.', 'wrong_key' => 'Nesprávny kľúč.', 'done' => 'Hotovo.', 'no_rollback' => 'Nie je čo vrátiť.', 'no_backups' => 'Žiadne zálohy.', 'failed' => 'Chyba: ', 'remove' => 'Po skončení zmažte ESHOP_RESCUE_KEY zo súboru .env.'],
    'cs' => ['title' => 'Záchrana e-shopu', 'disabled' => 'Záchranný skript je vypnutý. Zapnete ho řádkem ESHOP_RESCUE_KEY=… (alespoň 24 znaků) v souboru .env.', 'key' => 'Záchranný klíč', 'rollback' => 'Vrátit poslední aktualizaci', 'up' => 'Vypnout režim údržby', 'cache' => 'Vymazat mezipaměti', 'restore' => 'Obnovit databázi ze zálohy', 'password' => 'Heslo zálohy (pokud je zašifrovaná)', 'confirm' => 'Opravdu? Databáze se přepíše.', 'wrong_key' => 'Nesprávný klíč.', 'done' => 'Hotovo.', 'no_rollback' => 'Není co vrátit.', 'no_backups' => 'Žádné zálohy.', 'failed' => 'Chyba: ', 'remove' => 'Po skončení smažte ESHOP_RESCUE_KEY ze souboru .env.'],
    'en' => ['title' => 'E-shop rescue', 'disabled' => 'The rescue script is disabled. Enable it with ESHOP_RESCUE_KEY=… (at least 24 characters) in the .env file.', 'key' => 'Rescue key', 'rollback' => 'Roll back the last update', 'up' => 'Turn off maintenance mode', 'cache' => 'Clear caches', 'restore' => 'Restore the database from a backup', 'password' => 'Backup password (if encrypted)', 'confirm' => 'Are you sure? The database will be overwritten.', 'wrong_key' => 'Wrong key.', 'done' => 'Done.', 'no_rollback' => 'Nothing to roll back.', 'no_backups' => 'No backups.', 'failed' => 'Error: ', 'remove' => 'When finished, remove ESHOP_RESCUE_KEY from the .env file.'],
];
$t = $texts[$lang] ?? $texts['sk'];
$key = (string) ($env['ESHOP_RESCUE_KEY'] ?? '');
$enabled = strlen($key) >= 24;
$message = null;

header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

$backupDir = $root.'/storage/app/backups';
$updateDir = $root.'/storage/app/updates';
$backups = array_map('basename', glob($backupDir.'/eshop-*.zip') ?: []);
rsort($backups);

if ($enabled && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Pomalé porovnanie proti hádaniu kľúča.
    usleep(random_int(200000, 400000));

    if (! hash_equals($key, (string) ($_POST['key'] ?? ''))) {
        $message = $t['wrong_key'];
    } else {
        try {
            $message = match ((string) ($_POST['action'] ?? '')) {
                'rollback' => rescueRollback($root, $updateDir) ? $t['done'] : $t['no_rollback'],
                'up' => rescueUp($root) ?: $t['done'],
                'cache' => rescueCache($root) ?: $t['done'],
                'restore' => rescueRestore($env, $backupDir, (string) ($_POST['backup'] ?? ''), (string) ($_POST['password'] ?? '')) ?: $t['done'],
                default => null,
            };
        } catch (Throwable $e) {
            $message = $t['failed'].$e->getMessage();
        }
    }
}

function rescueRollback(string $root, string $dir): bool
{
    $info = is_file($dir.'/rollback.json') ? json_decode((string) file_get_contents($dir.'/rollback.json'), true) : null;

    if (! is_array($info) || ! is_file($dir.'/rollback.zip')) {
        return false;
    }

    $zip = new ZipArchive;
    $zip->open($dir.'/rollback.zip');
    $zip->extractTo($root);
    $zip->close();

    foreach ((array) ($info['new_files'] ?? []) as $file) {
        if (is_string($file) && ! str_contains($file, '..') && ! str_starts_with($file, 'storage/') && $file !== '.env' && is_file($root.'/'.$file)) {
            unlink($root.'/'.$file);
        }
    }

    unlink($dir.'/rollback.zip');
    unlink($dir.'/rollback.json');
    rescueCache($root);

    return true;
}

function rescueUp(string $root): string
{
    foreach (['down', 'maintenance.php'] as $file) {
        if (is_file($root.'/storage/framework/'.$file)) {
            unlink($root.'/storage/framework/'.$file);
        }
    }

    return '';
}

function rescueCache(string $root): string
{
    foreach (array_merge(glob($root.'/bootstrap/cache/*.php') ?: [], glob($root.'/storage/framework/views/*.php') ?: []) as $file) {
        unlink($file);
    }

    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    return '';
}

/**
 * @param  array<string, string>  $env
 */
function rescueRestore(array $env, string $dir, string $name, string $password): string
{
    if (! preg_match('/^eshop-[0-9-]+\.zip$/', $name) || ! is_file($dir.'/'.$name)) {
        throw new RuntimeException('backup');
    }

    $zip = new ZipArchive;
    $zip->open($dir.'/'.$name, ZipArchive::RDONLY);

    if ($password !== '') {
        $zip->setPassword($password);
    }

    $stream = $zip->getStream('database.sql');

    if ($stream === false) {
        throw new RuntimeException('database.sql');
    }

    $driver = $env['DB_CONNECTION'] ?? 'mysql';
    $dsn = $driver === 'sqlite'
        ? 'sqlite:'.($env['DB_DATABASE'] ?? '')
        : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? 'localhost', $env['DB_PORT'] ?? '3306', $env['DB_DATABASE'] ?? '');
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? null, $env['DB_PASSWORD'] ?? null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    @set_time_limit(0);

    if ($driver !== 'sqlite') {
        // Čistý stav, aby novšie tabuľky neprekážali.
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $prefix = $env['DB_PREFIX'] ?? '';

        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            if ($prefix === '' || str_starts_with((string) $table, $prefix)) {
                $pdo->exec('DROP TABLE `'.str_replace('`', '``', (string) $table).'`');
            }
        }
    }

    // Rovnaký princíp ako App\Core\Backup\SqlImporter: bodkočiarka v texte príkaz neukončí.
    $statement = '';
    $quote = null;
    $escaped = false;
    $backslash = $driver !== 'sqlite';

    while (! feof($stream)) {
        $chunk = (string) fread($stream, 1 << 16);
        $start = 0;
        $length = strlen($chunk);

        for ($i = 0; $i < $length; $i++) {
            $char = $chunk[$i];

            if ($escaped) {
                $escaped = false;
            } elseif ($quote !== null) {
                if ($backslash && $char === '\\' && $quote !== '`') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
            } elseif ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
            } elseif ($char === ';') {
                $statement .= substr($chunk, $start, $i - $start);
                $start = $i + 1;

                if (trim($statement) !== '' && ! ($driver === 'sqlite' && stripos(trim($statement), 'SET ') === 0)) {
                    $pdo->exec($statement);
                }

                $statement = '';
            }
        }

        $statement .= substr($chunk, $start);
    }

    if (trim($statement) !== '') {
        $pdo->exec($statement);
    }

    fclose($stream);
    $zip->close();

    return '';
}

?><!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= htmlspecialchars($t['title']) ?></title>
    <style>
        body { font: 16px/1.5 system-ui, sans-serif; max-width: 560px; margin: 40px auto; padding: 0 16px; color: #1f1d1a; }
        form { border: 1px solid #e5e2dc; border-radius: 12px; padding: 16px; margin: 12px 0; }
        input, select, button { font: inherit; padding: 8px 12px; border-radius: 8px; border: 1px solid #ccc; width: 100%; box-sizing: border-box; margin-top: 6px; }
        button { background: #1f1d1a; color: #fff; border: 0; cursor: pointer; }
        .msg { background: #fdf0e4; padding: 12px; border-radius: 8px; }
    </style>
</head>
<body>
<h1><?= htmlspecialchars($t['title']) ?></h1>
<?php if (! $enabled) { ?>
    <p><?= htmlspecialchars($t['disabled']) ?></p>
<?php } else { ?>
    <?php if ($message !== null) { ?><p class="msg"><?= htmlspecialchars($message) ?></p><?php } ?>
    <?php foreach (['rollback', 'up', 'cache'] as $action) { ?>
        <form method="post">
            <input type="hidden" name="action" value="<?= $action ?>">
            <label><?= htmlspecialchars($t['key']) ?><input type="password" name="key" required autocomplete="off"></label>
            <button type="submit"><?= htmlspecialchars($t[$action]) ?></button>
        </form>
    <?php } ?>
    <form method="post" onsubmit="return confirm(<?= htmlspecialchars(json_encode($t['confirm']), ENT_QUOTES) ?>)">
        <input type="hidden" name="action" value="restore">
        <?php if ($backups === []) { ?>
            <p><?= htmlspecialchars($t['no_backups']) ?></p>
        <?php } else { ?>
            <select name="backup"><?php foreach ($backups as $backup) { ?><option><?= htmlspecialchars($backup) ?></option><?php } ?></select>
            <label><?= htmlspecialchars($t['password']) ?><input type="password" name="password" autocomplete="off"></label>
            <label><?= htmlspecialchars($t['key']) ?><input type="password" name="key" required autocomplete="off"></label>
            <button type="submit"><?= htmlspecialchars($t['restore']) ?></button>
        <?php } ?>
    </form>
    <p><small><?= htmlspecialchars($t['remove']) ?></small></p>
<?php } ?>
</body>
</html>
