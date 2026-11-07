<?php

namespace Modules\Orders;

use RuntimeException;

/**
 * Objednávku nie je možné vytvoriť (zmenil sa sklad, nedostupná doprava a pod.).
 * Správa je už preložená pre zákazníka.
 */
class CheckoutException extends RuntimeException {}
