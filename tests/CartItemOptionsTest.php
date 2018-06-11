<?php

namespace Witify\LaravelCart\Tests;

use Orchestra\Testbench\TestCase;
use Witify\LaravelCart\CartItemOptions;
use Witify\LaravelCart\Tests\Fixtures\BuyableProduct;

class CartItemOptionsTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_get_function()
    {
        $cartOptions = new CartItemOptions();
        $cartOptions->put('size', 'XL');
        $this->assertEquals($cartOptions->size, 'XL');
    }
}
