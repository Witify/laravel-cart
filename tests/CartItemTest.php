<?php

namespace Witify\LaravelCart\Tests;

use Orchestra\Testbench\TestCase;
use Witify\LaravelCart\CartItem;
use Witify\LaravelCart\Tests\Fixtures\BuyableProduct;

class CartItemTest extends TestCase
{
    private $cartItem, $buyable;

    public function setUp()
    {
        parent::setUp();
        $this->buyable = new BuyableProduct();
        $this->cartItem = CartItem::fromBuyable($this->buyable);
    }

    public function test_can_show_quantity()
    {
        $this->assertEquals($this->cartItem->quantity, 1);
    }

    public function test_can_show_id()
    {
        $this->assertEquals($this->cartItem->id, $this->buyable->getBuyableIdentifier());
    }

    public function test_can_show_name()
    {
        $this->assertEquals($this->cartItem->name, $this->buyable->getBuyableDescription());
    }

    public function test_can_show_total()
    {
        $this->assertEquals($this->cartItem->total(), $this->buyable->getBuyablePrice() * 1);
    }

    public function test_can_udpate_quantity()
    {
        $qty = 2;
        $this->cartItem->setQuantity($qty);
        $this->assertEquals($this->cartItem->quantity, $qty);
        $this->assertEquals($this->cartItem->total(), $this->buyable->getBuyablePrice() * $qty);
    }

    public function test_can_udpate_invalid_quantity()
    {
        try {
            $qty = null;
            $this->cartItem->setQuantity($qty);
            $this->fail("Expected exception not thrown");
        } catch(\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    public function test_can_update_product()
    {
        $this->assertEquals($this->cartItem->id, $this->buyable->getBuyableIdentifier());
        $this->assertEquals($this->cartItem->name, $this->buyable->getBuyableDescription());
        $this->assertEquals($this->cartItem->price, $this->buyable->getBuyablePrice());

        $newBuyable = new BuyableProduct(13, 'Custom name', 123.00);
        $this->cartItem->updateFromBuyable($newBuyable);

        $this->assertEquals($this->cartItem->id, 13);
        $this->assertEquals($this->cartItem->name, 'Custom name');
        $this->assertEquals($this->cartItem->price, 123.00);
    }

    public function test_can_udpate_from_array()
    {
        $this->cartItem = CartItem::fromArray([
            'id' => 12,
            'name' => 'cool name',
            'price' => 66.99,
            'options' => []
        ]);

        $this->assertEquals($this->cartItem->id, 12);
        $this->assertEquals($this->cartItem->name, 'cool name');
        $this->assertEquals($this->cartItem->price, 66.99);
    }

    public function test_cannot_udpate_from_array_with_invalid_array()
    {
        try {
            $this->cartItem = CartItem::fromArray([]);
    
            $this->fail("Expected exception not thrown");
        } catch(\Exception $e) {
            $this->assertInstanceOf(\InvalidArgumentException::class, $e);
        }
    }

    public function test_it_can_be_cast_to_an_array()
    {
        $options = ['size' => 'XL', 'color' => 'red'];
        $this->cartItem = CartItem::fromBuyable($this->buyable, $options);
        $this->cartItem->setQuantity(2);
        $this->assertEquals([
            'id' => $this->buyable->getBuyableIdentifier($options),
            'name' => $this->buyable->getBuyableDescription($options),
            'price' => $this->buyable->getBuyablePrice($options),
            'row_id' => '07d5da5550494c62daf9993cf954303f',
            'quantity' => 2,
            'options' => [
                'size' => 'XL',
                'color' => 'red'
            ],
            'total' => 30.00,
        ], $this->cartItem->toArray());
    }

    public function test_it_can_be_cast_to_json()
    {
        $options = ['size' => 'XL', 'color' => 'red'];
        $this->cartItem = CartItem::fromBuyable($this->buyable, $options);
        $this->cartItem->setQuantity(2);
        $this->assertJson($this->cartItem->toJson());
        $json = '{"row_id":"07d5da5550494c62daf9993cf954303f","id":1,"name":"Item name","quantity":2,"price":15,"options":{"size":"XL","color":"red"},"total":30}';
        $this->assertEquals($json, $this->cartItem->toJson());
    }
}
