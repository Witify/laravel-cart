<?php

namespace Witify\LaravelCart\Contracts;

use Illuminate\Support\Collection;
use Witify\LaravelCart\Cart;

interface CartLineHandler
{
    /**
     * Handles the cart line
     *
     * @return float
     */
    static public function handle(Cart $cart, float $currentTotal) : float;
}
