
# phpSmug

[![Coverage Status](https://coveralls.io/repos/lildude/phpSmug/badge.svg?branch=main&service=github)](https://coveralls.io/github/lildude/phpSmug?branch=main) ![Test Status](https://github.com/lildude/phpSmug/workflows/Run%20Tests/badge.svg)

phpSmug is a simple object orientated wrapper for the new SmugMug API v2, written in PHP.

The intention of this class is to allow PHP application developers quick and easy interaction with the SmugMug API, without having to worry about the finer details of the API.

Not already a SmugMug user? Here, have a **$5 discount** off your first year on me by [registering](https://secure.smugmug.com/signup.mg?Coupon=2ZxFXMC19qOxU) using this code:

**[2ZxFXMC19qOxU](https://secure.smugmug.com/signup.mg?Coupon=2ZxFXMC19qOxU)**

The development of phpSmug takes place in my free time. If you find phpSmug useful and found it has saved you a lot of time, consider sponsoring this project.

---

**Note: Due to significant changes in the SmugMug API, phpSmug 4.0.0 and later is not backwardly compatible with the SmugMug API v1.x.x releases.**

## Requirements

* PHP >= 7.3.0,
* [Guzzle 6](https://github.com/guzzle/guzzle) library and the [Guzzle OAuth1 Subscriber](https://github.com/guzzle/oauth-subscriber),
* (optional) [PHPUnit](https://phpunit.de/) and [php-cs-fixer](http://cs.sensiolabs.org/) to run tests.

## Installation

The recommended method of installing phpSmug is using [Composer](http://getcomposer.org). If you have Composer installed, you can install phpSmug and all its dependencies from within your project directory:

```bash
composer require lildude/phpsmug
```

Alternatively, you can add the following to your project's `composer.json`:

```json
{
    "require": {
        "lildude/phpsmug": "^4.0"
    }
}
```

.. and then run `composer update` from within your project directory.

If you don't have Composer installed, you can download it using:

```bash
curl -s http://getcomposer.org/installer | php
```

## Basic Usage of the phpSmug Client

`phpSmug` follows the PSR-1, PSR-2 and PSR-4 conventions, which means you can easily use Composer's [autoloading](https://getcomposer.org/doc/01-basic-usage.md#autoloading) to integrate `phpSmug` into your projects.

```php
<?php

// This file is generated by Composer
require_once 'vendor/autoload.php';

// Optional, but definitely nice to have, options
$options = [
    'AppName'   => 'My Cool App/1.0 (http://app.com)',
];
$client = new phpSmug\Client("[YOUR_API_KEY]", $options));
$albums = $client->get('user/[your_username]!albums');
```

From the `$client` object, you can access to all the SmugMug 2.0 API methods.

## Documentation

See the [`docs` directory](docs/) or <https://lildude.github.io/phpSmug/> for more detailed documentation.

## Examples

phpSmug comes with 3 examples to help get you on your way. All 3 examples perform the same thing, just using differing authentication methods. They all show thumbnails of the first album found for the respective authentication methods:

- `example.php` illustrates anonymous, unauthenticated access.
- `example-oauth.php` illustrates an OAuth login.
- `example-external-links.php` illustrates displaying private images.

## Getting Help

The best way to get help with implementing phpSmug into your projects is to open an [issue](https://github.com/lildude/phpSmug/issues).  This allows you to easily search for other issues where others may have asked to the same questions or hit the same problems and if they haven't, your issue will add to the resources available to others at a later date.

Please don't be shy. If you've got a question, problem or are just curious about something, there's a very good chance someone else is too, so go ahead and open an issue and ask.

## Contributing

Found a bug or want to make phpSmug even better? Please feel free to open a pull request with your changes, but be sure to check out the [CONTRIBUTING.md](CONTRIBUTING.md) first for some tips and guidelines. No pull request is too small.

## Changes

All notable changes to this project are documented in [CHANGELOG.md](CHANGELOG.md).

## License

phpSmug is licensed under the MIT License - see the LICENSE file for details
