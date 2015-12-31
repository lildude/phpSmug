# Requirements

* PHP >= 5.5.0,
* [Guzzle 6](https://github.com/guzzle/guzzle) library and the [Guzzle OAuth1 Subscriber](https://github.com/guzzle/oauth-subscriber),
* (optional) [PHPUnit](https://phpunit.de/) and [php-cs-fixer](http://cs.sensiolabs.org/) to run tests.

# Installation

The recommended method of installing phpSmug is using [Composer](http://getcomposer.org) by adding the following to your project's `composer.json`:

```json
{
    "require": {
        "guzzlehttp/oauth-subscriber": "0.3.*"
    }
}
```

If you don't have Composer installed, you can download it using:

```bash
$ curl -s http://getcomposer.org/installer | php
```

... and then use it to install phpSmug and all dependencies using:

```bash
$ php composer.phar install
```

If you have Composer installed, you can install phpSmug by running the following from within your project directory:

```bash
$ composer install
```
