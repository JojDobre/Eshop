<?php

namespace Modules\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Orders\Models\Order;

class OrderPlaced
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
