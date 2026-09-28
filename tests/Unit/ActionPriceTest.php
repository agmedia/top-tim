<?php

namespace Tests\Unit;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class ActionPriceTest extends TestCase
{
    /** @test */
    public function percentage_action_reduces_the_regular_price(): void
    {
        $this->assertEqualsWithDelta(80.0, Helper::calculateDiscountPrice(100, 20, 'P'), 0.001);
    }

    /** @test */
    public function fixed_action_value_is_the_final_sale_price(): void
    {
        $this->assertEqualsWithDelta(94.9, Helper::calculateDiscountPrice(158.4, 94.9, 'F'), 0.001);
    }

    /** @test */
    public function fixed_action_cannot_raise_the_regular_price(): void
    {
        $this->assertEqualsWithDelta(100.0, Helper::calculateDiscountPrice(100, 120, 'F'), 0.001);
    }
}
