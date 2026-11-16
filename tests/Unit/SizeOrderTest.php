<?php

namespace Tests\Unit;

use App\Helpers\SizeOrder;
use PHPUnit\Framework\TestCase;

class SizeOrderTest extends TestCase
{
    public function test_numeric_then_clothing_sizes_and_aliases(): void
    {
        $sizes = ['L', '11', '2XL', '5XS', 'M', '1', '6XL', 'XL', '10', 'XS', '4XS', '6XS', '3XS', '2XS', 'S', '3XL', '4XL', '5XL', '2'];
        usort($sizes, fn ($a, $b) => SizeOrder::key($a) <=> SizeOrder::key($b));
        $this->assertSame(['1', '2', '10', '11', '6XS', '5XS', '4XS', '3XS', '2XS', 'XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', '6XL'], $sizes);
        $this->assertSame(SizeOrder::key('2XS'), SizeOrder::key(' xxs '));
        $this->assertSame(SizeOrder::key('3XL'), SizeOrder::key('XXXL'));
        $this->assertLessThan(SizeOrder::key('10'), SizeOrder::key('9,5'));
        $this->assertGreaterThan(SizeOrder::key('6XL'), SizeOrder::key('UNI'));
    }
}
