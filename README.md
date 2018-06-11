# Lightweight and highly flexible cart in Laravel 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Witify/laravel-cart.svg?style=flat-square)](https://packagist.org/packages/Witify/laravel-cart)
[![Build Status](https://img.shields.io/travis/Witify/laravel-cart/master.svg?style=flat-square)](https://travis-ci.org/Witify/laravel-cart)
[![Maintainability](https://api.codeclimate.com/v1/badges/8ed724e9f57baa80c964/maintainability)](https://codeclimate.com/github/Witify/laravel-cart/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/8ed724e9f57baa80c964/test_coverage)](https://codeclimate.com/github/Witify/laravel-cart/test_coverage)
[![Total Downloads](https://img.shields.io/packagist/dt/Witify/laravel-cart.svg?style=flat-square)](https://packagist.org/packages/witify/laravel-cart)

## Installation

You can install the package via composer:

```bash
composer require witify/laravel-cart
```

## Usage

For Laravel 5.1 to 5.4, add the service provider:
``` php

LaravelCartServiceProvider::class
```

### Configuration
To save cart into the database so you can retrieve it later, the package needs to know which database connection to use and what the name of the table is.
By default the package will use the default database connection and use a table named `carts`.
If you want to change these options, you'll have to publish the `config` file.

    php artisan vendor:publish --provider="Witify\LaravelCart\LaravelCartServiceProvider" --tag="config"

This will give you a `cart.php` config file in which you can make the changes.

To make your life easy, the package also includes a ready to use `migration` which you can publish by running:

    php artisan vendor:publish --provider="Witify\LaravelCart\LaravelCartServiceProvider" --tag="migrations"
    
This will place a `carts` table's migration file into `database/migrations` directory. Now all you have to do is run `php artisan migrate` to migrate your database


### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email info@witify.io instead of using the issue tracker.

## Credits

- [François Lévesque](https://github.com/francoislevesque)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
