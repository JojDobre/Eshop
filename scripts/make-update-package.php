<?php

/*
 * Z inštalačného balíka vytvorí aktualizačný balík (package.zip + manifest.json) a podpíše ho
 * kľúčom z premennej prostredia ESHOP_SIGNING_KEY. Bez kľúča vznikne nepodpísaný balík.
 *
 * Použitie: php scripts/make-update-package.php dist/eshop-1.0.0.zip [poznámky]
 */
$install = $argv[1] ?? exit("Použitie: php scripts/make-update-package.php <inštalačný-balík.zip>\n");
$source = file_get_contents(__DIR__.'/../app/Core/Eshop.php');
preg_match("/const VERSION = '([^']+)'/", $source, $version);
preg_match("/const MIN_PHP_VERSION = '([^']+)'/", $source, $php);

$sha256 = hash_file('sha256', $install);
$manifest = [
    'version' => $version[1],
    'sha256' => $sha256,
    'requires' => ['php' => $php[1]],
    'notes' => $argv[2] ?? null,
    'signature' => null,
];

$secret = getenv('ESHOP_SIGNING_KEY');

if (is_string($secret) && $secret !== '') {
    $key = base64_decode($secret, true);

    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
        fwrite(STDERR, "ESHOP_SIGNING_KEY nie je platný kľúč.\n");
        exit(1);
    }

    $manifest['signature'] = base64_encode(sodium_crypto_sign_detached('eshop-update:'.$version[1].':'.$sha256, $key));
} else {
    fwrite(STDERR, "Upozornenie: ESHOP_SIGNING_KEY nie je nastavený, balík nebude podpísaný.\n");
}

$target = dirname($install).'/eshop-update-'.$version[1].'.zip';
$zip = new ZipArchive;
$zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFile($install, 'package.zip');
$zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$zip->setCompressionName('package.zip', ZipArchive::CM_STORE);
$zip->close();

echo "✓ Aktualizačný balík: {$target}".($manifest['signature'] ? ' (podpísaný)' : ' (nepodpísaný)').PHP_EOL;
