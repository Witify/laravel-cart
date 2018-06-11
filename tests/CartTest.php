<?php

namespace Witify\LaravelCart\Tests;

use Carbon\Carbon;

use Witify\LaravelCart\Cart;

use Orchestra\Testbench\TestCase;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Witify\LaravelCart\CartableInterface;
use Witify\LaravelCart\Tests\Fixtures\User;
use Witify\LaravelCart\LaravelCartServiceProvider;
use Witify\LaravelCart\Tests\Fixtures\BuyableProduct;
use Illuminate\Auth\Events\Login;

class CartTest extends TestCase
{
    private $cart, $buyable, $user;

    /**
     * Set the package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [LaravelCartServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cart.database.connection', 'testing');
        $app['config']->set('cart.database.table', 'carts');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->app->afterResolving('migrator', function ($migrator) {
            $migrator->path(realpath(__DIR__.'/../database/migrations'));
        });

        $this->buyable = new BuyableProduct();
        $this->cart = new Cart();
        $this->user = new User([
            'id' => 20,
            'email' => 't@t.com',
            'name' => 'robin',
            'password' => 'secret'
        ]);
    }

    private function createComplexCart()
    {
        $this->cart->addCartLine('TPS', function($total, $subtotal) {
            return round($subtotal * 0.05, 2);
        });

        $this->cart->addCartLine('TVQ', function($total, $subtotal) {
            return round($subtotal * 0.09975, 2);
        });

        $cartItem = $this->cart->add(new BuyableProduct());
        $cartItem = $this->cart->add(new BuyableProduct(2, 'Item 2', 213.51), 1, ['size' => 'XL', 'color' => 'red']);
        $cartItem = $this->cart->add(new BuyableProduct(3, 'Item 3', 55.79));
        $cartItem = $this->cart->add(new BuyableProduct(4, 'Item 4', 865.12));
    }

    public function test_can_show_total()
    {
        $this->assertEquals($this->cart->total(), 0);
    }

    public function test_can_add_cart_item()
    {
        $qty = 3;
        $options = ['size' => 'XL'];
        $this->cart->add($this->buyable, $qty, $options);

        $this->assertEquals($this->cart->total(), $this->buyable->getBuyablePrice($options) * $qty);
        $this->assertEquals($this->cart->count(), 3);
    }

    public function test_can_add_cart_item_twice()
    {
        $qty = 3;
        $options = ['size' => 'XL'];
        $this->cart->add($this->buyable, $qty, $options);

        $this->assertEquals($this->cart->count(), 3);
        $this->assertEquals($this->cart->items()->first()->quantity, 3);

        $this->cart->add($this->buyable, $qty, $options);

        $this->assertEquals($this->cart->count(), 6);
        $this->assertEquals($this->cart->items()->first()->quantity, 6);

        // Different option
        $this->cart->add($this->buyable, $qty, ['size' => 'M']);

        $this->assertEquals($this->cart->count(), 9);
        $this->assertEquals($this->cart->items()->first()->quantity, 6);
    }

    public function test_can_remove_cart_item()
    {
        $qty = 3;
        $options = ['size' => 'XL'];
        $cartItem = $this->cart->add($this->buyable, $qty, $options);

        $this->assertEquals($this->cart->count(), 3);

        $this->cart->remove($cartItem->rowId);

        $this->assertEquals($this->cart->count(), 0);
    }

    public function test_can_update_cart_item()
    {
        $qty = 3;
        $options = ['size' => 'XL'];
        $cartItem = $this->cart->add($this->buyable, $qty, $options);

        $this->assertEquals($this->cart->items()->first()->quantity, $qty);
        $this->assertEquals($this->cart->items()->first()->name, $this->buyable->getBuyableDescription());
        $this->assertEquals($this->cart->items()->first()->price, $this->buyable->getBuyablePrice($options));
        $this->assertEquals($this->cart->items()->first()->options['size'], $options['size']);

        $newQty = 10;
        $newBuyable = new BuyableProduct(13, 'Custom name', 123.00);
        $newOptions = ['size' => 'SM'];
        $cartItem = $this->cart->update($cartItem->rowId, $newBuyable, $newQty, $newOptions);

        $this->assertEquals($this->cart->items()->first()->quantity, $newQty);
        $this->assertEquals($this->cart->items()->first()->name, $newBuyable->getBuyableDescription());
        $this->assertEquals($this->cart->items()->first()->price, $newBuyable->getBuyablePrice($newOptions));
        $this->assertEquals($this->cart->items()->first()->options['size'], $newOptions['size']);
    }

    public function test_can_add_cart_line()
    {
        $cartItem = $this->cart->add($this->buyable);

        $tax = 0.15;
        $this->cart->addCartLine('taxes', function($total) use ($tax) {
            return $total * $tax;
        });

        $this->assertEquals($this->cart->total(), $this->cart->items()->first()->total() * (1 + $tax));
    }

    public function test_can_add_multiple_cart_lines()
    {
        $cartItem = $this->cart->add($this->buyable);
        $cartItem = $this->cart->add($this->buyable);

        $tax1 = 0.25;
        $this->cart->addCartLine('taxes 1', function($total) use ($tax1) {
            return $total * $tax1;
        });

        $tax2 = 0.71;
        $this->cart->addCartLine('taxes 2', function($total, $subtotal) use ($tax2) {
            return $subtotal * $tax2;
        });

        $fee = 3;
        $this->cart->addCartLine('fee', function($total) use ($fee) {
            return $fee;
        });

        $shipping = 10;
        $this->cart->addCartLine('shipping', function($total) use ($shipping) {
            return $shipping;
        });

        $finalTax = 0.11;
        $this->cart->addCartLine('final taxes', function($total) use ($finalTax) {
            return $total * $finalTax;
        });

        $itemSubTotal = $this->cart->items()->first()->total();

        $total = round(($itemSubTotal + $itemSubTotal * $tax1 + $itemSubTotal * $tax2 + $fee + $shipping) * (1 + $finalTax), 2);

        $this->assertEquals($this->cart->total(), $total);
    }

    public function test_can_save_to_session()
    {
        $this->createComplexCart();

        $this->assertEquals(
            session('cart'),
            $this->cart->toArray()
        );
    }

    public function test_can_retreive_cart_from_session()
    {
        $this->createComplexCart();
        
        $cart = new Cart(); // Should retrieve from session

        $cart->addCartLine('TPS', function($total, $subtotal) {
            return round($subtotal * 0.05, 2);
        });

        $cart->addCartLine('TVQ', function($total, $subtotal) {
            return round($subtotal * 0.09975, 2);
        });

        $this->assertEquals($cart->count(), 4);
        $this->assertEquals(
            session('cart'),
            $cart->toArray()
        );
    }

    public function test_updated_at_is_updated()
    {
        Carbon::setTestNow('2018-01-01 10:00:00');

        $this->cart = new Cart(); // Reinstantiate reflect Carbon change in class
        $this->cart->add($this->buyable);
        $time1 = $this->cart->updatedAt;

        Carbon::setTestNow('2018-01-01 12:00:00');

        $this->cart = new Cart(); // Reinstantiate reflect Carbon change in class
        $this->cart->add($this->buyable);
        $time2 = $this->cart->updatedAt;

        $this->assertTrue($time2 > $time1);
        $this->assertTrue($time1 == new Carbon('2018-01-01 10:00:00'));
        $this->assertTrue($time2 == new Carbon('2018-01-01 12:00:00'));
    }

    public function test_store_to_db()
    {
        $this->loadLaravelMigrations(['--database' => 'testing']);
        $this->artisan('migrate', ['--database' => 'testing']);

        $this->actingAs($this->user);

        $this->createComplexCart();

        $this->assertEquals(DB::table('carts')->count(), 1);
        $this->assertEquals(DB::table('carts')->first()->content, $this->cart->toJson());
    }

    public function test_login_vs_logout()
    {
        $this->loadLaravelMigrations(['--database' => 'testing']);
        $this->artisan('migrate', ['--database' => 'testing']);

        // Guest - add one product

        Carbon::setTestNow('2018-01-01 10:00:00');
        $this->cart = new Cart();
        $this->cart->add($this->buyable);
        
        $this->assertEquals(DB::table('carts')->count(), 0);
        $this->assertTrue(session()->has('cart'));

        // Login - add one product

        $this->actingAs($this->user);
        event(new Login($this->user, false)); // Forcing event fire
        Carbon::setTestNow('2018-01-01 11:00:00');
        $this->cart = new Cart();
        $this->cart->add(new BuyableProduct(3));
        
        $this->assertEquals(DB::table('carts')->count(), 1);
        $this->assertEquals(DB::table('carts')->first()->content, $this->cart->toJson());
        $this->assertEquals(count(json_decode(DB::table('carts')->first()->content, true)['items']), 2);

        // Guest

        Auth::logout();
        Carbon::setTestNow('2018-01-01 13:00:00');
        $this->cart = new Cart();
        $this->assertEquals($this->cart->count(), 1);

        // Guest adds 3 products

        $this->cart->add(new BuyableProduct(4));
        $this->cart->add(new BuyableProduct(5));
        $this->cart->add(new BuyableProduct(6));

        // Login -> The session cart has replaced the database cart

        $this->actingAs($this->user);
        event(new Login($this->user, false)); // Forcing event fire

        $this->assertEquals(count(json_decode(DB::table('carts')->first()->content, true)['items']), 4);
    }
}
