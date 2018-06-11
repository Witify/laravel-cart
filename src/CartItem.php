<?php

namespace Witify\LaravelCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Witify\LaravelCart\Contracts\Buyable;

class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    public $rowId;

    /**
     * The id of the buyable in the cart item.
     *
     * @var string|int
     */
    public $id;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $quantity;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The price of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The options for this cart item.
     *
     * @var array
     */
    public $options;

    /**
     * CartItem constructor.
     *
     * @param string|int $id
     * @param string $name
     * @param float $price
     * @param array   $options
     */
    public function __construct($id, string $name, float $price, array $options = [])
    {
        $this->quantity = 1;
        $this->id  = $id;
        $this->name  = $name;
        $this->price = $price;
        $this->rowId = $this->generateRowId($this->id, $options);
        $this->options  = new CartItemOptions($options);
    }

    /**
     * Construct the CartItem from an array
     *
     * @param array $data
     * @return void
     */
    static public function fromArray(array $data)
    {
        if (array_key_exists('id', $data) && array_key_exists('name', $data) && array_key_exists('price', $data) && array_key_exists('options', $data) && array_key_exists('quantity', $data)) {
            return (new self($data['id'], $data['name'], $data['price'], $data['options']))->setQuantity($data['quantity']);
        }
        throw new \InvalidArgumentException('Please supply a valid array');
    }

    /**
     * CartItem constructor.
     *
     * @param Buyable $item
     * @param array   $options
     */
    static public function fromBuyable(Buyable $buyable, array $options = [])
    {
        return new self(
            $buyable->getBuyableIdentifier($options),
            $buyable->getBuyableDescription($options),
            floatval($buyable->getBuyablePrice($options)),
            $options
        );
    }

    /**
     * Set the quantity for this cart item.
     *
     * @param int|float $quantity
     */
    public function setQuantity($quantity)
    {
        if(empty($quantity) || ! is_numeric($quantity))
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * Update the cart item from a Buyable.
     *
     * @param \Gloudemans\Shoppingcart\Contracts\Buyable $item
     * @return void
     */
    public function updateFromBuyable(Buyable $buyable, array $options = [])
    {
        $this->id       = $buyable->getBuyableIdentifier($options);
        $this->name     = $buyable->getBuyableDescription($options);
        $this->price    = $buyable->getBuyablePrice($options);
        $this->options  = new CartItemOptions($options);
    }

    public function total() : float
    {
        return $this->quantity * $this->price;
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array  $options
     * @return string
     */
    protected function generateRowId($id, array $options)
    {
        ksort($options);
        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'row_id'     => $this->rowId,
            'id' => $this->id,
            'name'       => $this->name,
            'quantity'   => $this->quantity,
            'price'      => $this->price,
            'options'    => $this->options->toArray(),
            'total'      => $this->total()
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
        return json_encode($this->toArray(), $options);
    }
}
