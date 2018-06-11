<?php

namespace Witify\LaravelCart\Tests\Fixtures;

use Witify\LaravelCart\Contracts\Buyable;

class BuyableProduct implements Buyable
{
    /**
     * @var int|string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var float
     */
    public $price;

    /**
     * BuyableProduct constructor.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     */
    public function __construct($id = 1, $name = 'Item name', $price = 10.00)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

    /**
     * Get the identifier of the Buyable item.
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = [])
    {
        return $this->id;
    }

    /**
     * Get the description or title of the Buyable item.
     *
     * @return string
     */
    public function getBuyableDescription($options = [])
    {
        return $this->name;
    }

    /**
     * Get the price of the Buyable item.
     *
     * @return float
     */
    public function getBuyablePrice($options = [])
    {
        $price = $this->price;
        foreach ($options as $option) {
            if ($option == 'XL') {
                $price += 5;
            }
        }
        return $price;
    }
}