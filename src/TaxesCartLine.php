<?php

namespace Witify\LaravelCart;

use Illuminate\Support\Collection;

class TaxesCartLine implements Contracts\CartLineHandler
{
    static public function handle(Cart $cart, float $currentTotal) : float
    {
        return $currentTotal * 0.15;
    }
}
