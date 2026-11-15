<?php

namespace App\Core\Demo;

use RuntimeException;

/** Chyba pri importe dema (neplatný balík, nepovolený režim…). Správa je určená používateľovi. */
class DemoException extends RuntimeException {}
