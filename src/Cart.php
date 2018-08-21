<?php

namespace Witify\LaravelCart;

use Closure;

use Carbon\Carbon;

use Witify\LaravelCart\CartItem;
use Witify\LaravelCart\Contracts\Buyable;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\DB;

class Cart implements Arrayable, Jsonable
{
    const DEFAULT_INSTANCE = 'default';
    const SESSION_PREFIX = 'cart';
    
    public $updatedAt;
    public $items;

    private $cartLines;

    public function __construct()
    {
        $this->items = collect();
        $this->cartLines = collect();

        $this->addCartLines();

        $this->retrieveCart();
    }

    public function add(Buyable $buyable, int $quantity = 1, array $options = []) : CartItem
    {
        $cartItem = $this->createCartItem($buyable, $quantity, $options);

        // Update quantity if exact same product is already present
        if ($this->items->has($cartItem->rowId)) {
            $cartItem = $this->items[$cartItem->rowId];
            $cartItem->setQuantity($cartItem->quantity + $quantity);
        } else {
            $this->items->put($cartItem->rowId, $cartItem);
        }

        $this->save();

        return $cartItem;
    }

    public function remove(string $rowId)
    {
        $this->items->forget($rowId);

        $this->save();
    }

    public function update(string $rowId, Buyable $buyable, int $quantity = 1, array $options = [])
    {
        $this->remove($rowId);

        return $this->add($buyable, $quantity, $options);
    }

    public function count() : int
    {
        return $this->items->sum(function($item) {
            return $item->quantity;
        });
    }

    public function any() : bool
    {
        return $this->count() > 0;
    }

    public function isEmpty() : bool
    {
        return $this->count() == 0;
    }

    public function empty()
    {
        $this->items = collect();
    }

    public function subtotal() : float
    {
        return $this->items->sum(function($item) {
            return $item->total();
        });
    }

    public function total() : float
    {
        return round($this->getCartLines()['total'], 2);
    }

    public function items()
    {
        return $this->items;
    }

    private function addCartLines()
    {
        foreach(config('cart.lines') as $key => $callback) {
            $this->addCartLine($key, $callback);
        }
    }

    private function addCartLine(string $lineName, Closure $callback)
    {
        $this->cartLines->put($lineName, $callback);
    }

    public function getCartLines()
    {
        $priceLines = collect();
        $subtotal = $this->subtotal();
        $total = $subtotal;
        foreach($this->cartLines as $key => $callback) {
            $priceLines[$key] = $callback($total, $subtotal, $this->items);
            $total += $priceLines[$key];
        }
        $priceLines['total'] = $total;
        return $priceLines;
    }

    /**
     * Saves the cart
     *
     * @return void
     */
    public function save()
    {
        $this->updatedAt = Carbon::now();
        
        if (Auth::check()) {
            $this->saveToDatabase();
        } else {
            $this->saveToSession();
        }
    }
    
    /**
     * Saves the cart to the session
     *
     * @return void
     */
    public function saveToSession()
    {
        session([self::SESSION_PREFIX => $this->toArray()]);
    }

    /**
     * Saves the cart to the database
     *
     * @return void
     */
    public function saveToDatabase()
    {
        if (DB::table(config('cart.database.table'))->where('user_id', Auth::user()->id)->count() == 0) {
            DB::table(config('cart.database.table'))->insert([
                'user_id' => Auth::user()->id,
                'content' => $this->toJson(),
                'updated_at' => $this->updatedAt,
                'created_at' => $this->updatedAt
            ]);
        } else {
            DB::table(config('cart.database.table'))
            ->where('user_id', Auth::user()->id)->update([
                'content' => $this->toJson(),
                'updated_at' => $this->updatedAt
            ]);
        }
    }

    /**
     * Retrieves the cart
     *
     * @return void
     */
    private function retrieveCart()
    {
        $cartData = $this->retrieveCartFromSession();

        if (Auth::check()) {
            $cartDataFromDatabase = $this->retrieveCartFromDatabase();
            if ($cartDataFromDatabase !== null) {
                $cartData = $cartDataFromDatabase;
            }
        }

        $this->setUp($cartData);
    }

    /**
     * Setup Cart class
     *
     * @param array $cartData
     * @return void
     */
    private function setUp(array $cartData)
    {
        $this->items = collect($cartData['items'])->map(function($item) {
            return CartItem::fromArray($item);
        });

        $this->updatedAt = new Carbon($cartData['updated_at']);
    }

    /**
     * Retrieves the cart from session
     *
     * @return void
     */
    private function retrieveCartFromSession()
    {
        return session(self::SESSION_PREFIX, $this->defaultCart());
    }

    /**
     * Retrieves the cart from database
     *
     * @return void
     */
    private function retrieveCartFromDatabase()
    {
        $cart = DB::table(config('cart.database.table'))->where('user_id', Auth::user()->id)->first();
        if ($cart !== null) {
            return json_decode($cart->content, true);
        }
        return null;
    }

    /**
     * Creates a default cart
     *
     * @return void
     */
    private function defaultCart()
    {
        $this->updatedAt = Carbon::now();
        return $this->toArray();
    }

    public function updateDatabaseCart()
    {
        $databaseCart = $this->retrieveCartFromDatabase();
        $sessionCart = $this->retrieveCartFromSession();

        if ($databaseCart === null) {
            $this->setUp($sessionCart);
            $this->saveToDatabase();
        } else {
            if ($databaseCart['updated_at'] < $sessionCart['updated_at']) {
                $this->setUp($sessionCart);
                $this->saveToDatabase();
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Utils
    |--------------------------------------------------------------------------
    */

    private function createCartItem(Buyable $buyable, int $quantity, array $options)
    {
        $cartItem = CartItem::fromBuyable($buyable, $options);
        $cartItem->setQuantity($quantity);
        return $cartItem;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'items' => $this->items->toArray(),
            'lines' => $this->getCartLines()->toArray(),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }

}
