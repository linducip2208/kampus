<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_decimal_amount_is_converted_without_binary_float_rounding(): void
    {
        $this->assertSame(300000050, Money::toMinorUnits('3000000.50'));
        $this->assertSame('3000000.50', Money::fromMinorUnits(300000050));
    }

    public function test_negative_and_more_than_two_decimal_places_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::toMinorUnits('-1.00');
    }
}
