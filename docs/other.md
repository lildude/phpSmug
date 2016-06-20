
# Other Notes

## Access SmugMug via a Proxy

Accessing SmugMug with phpSmug through a proxy is possible by passing the `proxy` option when instantiating the client:

```php
$options = [
    'AppName' => 'My Cool App/1.0 (http://app.com)',
    'proxy' => 'http://[proxy_address]:[port]',
];
$client = new phpSmug\Client('[YOUR_API_KEY]', $options));
```

All your requests will pass through the specified proxy on the specified port.

If you need a username and password to access your proxy, you can include them in the URL in the form: `http://[username]:[password]@[proxy_address]:[port]`.

# Examples

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

[![MIT License badge]()]() phpSmug is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
