<?php

namespace Modules\Cart;

use RuntimeException;

/**
 * Produkt sa nedá pridať do košíka (nie je dostupný, nie je dosť kusov na sklade a pod.).
 * Správa je už preložená pre zákazníka.
 */
class CartException extends RuntimeException {}
