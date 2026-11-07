<?php

namespace Tests\Unit\Shop;

use Modules\Tax\TaxCalculator;
use PHPUnit\Framework\TestCase;

class TaxCalculatorTest extends TestCase
{
    public function test_net_from_gross(): void
    {
        $this->assertSame(1000, TaxCalculator::netFromGross(1230, 23));
        $this->assertSame(1230, TaxCalculator::netFromGross(1230, 0));
    }

    public function test_breakdown_groups_by_rate_and_avoids_rounding_drift(): void
    {
        $breakdown = TaxCalculator::breakdown([
            ['gross' => 333, 'rate' => 23.0],
            ['gross' => 333, 'rate' => 23.0],
            ['gross' => 333, 'rate' => 23.0],
            ['gross' => 500, 'rate' => 5.0],
        ]);

        $this->assertSame(['23.00', '5.00'], array_keys($breakdown));
        $this->assertSame(999, $breakdown['23.00']['gross']);
        $this->assertSame(812, $breakdown['23.00']['net']);
        $this->assertSame(187, $breakdown['23.00']['vat']);
        $this->assertSame(476, $breakdown['5.00']['net']);
    }
}
