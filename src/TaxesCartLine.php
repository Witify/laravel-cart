<?php

namespace Witify\LaravelCart;

use Illuminate\Support\Collection;

class TaxesCartLine implements Contracts\CartLineHandler
{
    static public function handle(Cart $cart) : float
    {
        return $cart->subtotal() * 0.15;
    }
}
