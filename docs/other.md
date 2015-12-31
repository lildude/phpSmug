
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

And that's all folks.

Keep up to date on developments and enhancements to phpSmug at <http://phpsmug.com/>.
