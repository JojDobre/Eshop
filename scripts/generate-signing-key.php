<?php

/*
 * Vytvorí pár kľúčov Ed25519 na podpisovanie aktualizácií.
 *
 * Súkromný kľúč uložte bezpečne (napr. ako tajomstvo ESHOP_SIGNING_KEY v GitHub Actions) a nikdy
 * ho nedávajte do repozitára. Verejný kľúč vložte do App\Core\Eshop::UPDATE_PUBLIC_KEY.
 *
 * Použitie: php scripts/generate-signing-key.php
 */
$pair = sodium_crypto_sign_keypair();

echo 'ESHOP_SIGNING_KEY='.base64_encode(sodium_crypto_sign_secretkey($pair)).PHP_EOL;
echo 'UPDATE_PUBLIC_KEY='.base64_encode(sodium_crypto_sign_publickey($pair)).PHP_EOL;
