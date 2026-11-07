<?php

namespace Modules\Catalog;

use RuntimeException;

/**
 * Na sklade nie je dosť tovaru (správa obsahuje názov produktu).
 */
class InsufficientStock extends RuntimeException
{
    public function __construct(public readonly string $productName)
    {
        parent::__construct($productName);
    }
}
