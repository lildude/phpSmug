
# Authentication

SmugMug's API allows read-only unauthenticated access to all public information.  If you need to access private information or make changes, you need to authorize your application.

SmugMug's latest API only offers the option of using OAuth for authentication.

Authenticating using OAuth is a 3 step process.

## Step 1: Obtain a Request Token

First, you need to request a request token:

```php
$callback_url = "http://example.com/your/cool/app.php";
$request_token = $client->getRequestToken($callback_url);
```

The response will be an array containing the request token and secret.  Store this in a location you can access later when it comes to requesting the access token.

The `$callback_url` is used to redirect the user back to your application once they've approved the access request.

## Step 2: Direct the User to Authorize Your Application

Once you’ve obtained the request token, you need to use it to direct the user to SmugMug to authorize your application. You can do this in a variety of ways. It’s up to you as the application developer to choose which method suits you. Ultimately, you need to direct the user to https://secure.smugmug.com/services/oauth/1.0a/getRequestToken with the required `Access`, `Permissions` and the `oauth_token` query parameters.

phpSmug provides a simple method `$client->getAuthorizeURL()` that generates the URL you can use for redirection or for the user to click. It also takes care of passing the OAuth token too:

```php
echo '<a href="'.$client->getAuthorizeURL().'">Authorize</a>';
```

If you don't pass any options to this method, SmugMug's [default public read access](https://api.smugmug.com/api/v2/doc/tutorial/authorization.html) is requested.  If you need greater access or permissions, pass an array of the access or permissions you require:

```php
$perms = [
    'Access' => 'Full',
    'Permissions' => 'Modify',
];
echo '<a href="'.$client->getAuthorizeURL($perms).'">Authorize</a>';
```

Once the user has authorized your application, they will be redirected back to the callback URL you used in `getRequestToken()` above with an additional `oauth_verifier` query parameter.

## Step 3: Request the Access Token

Now you have the request token, `oauth_verifier` and your user has approved the access your application has requested, you need to request the access token using `getAccessToken()`:

```php
$client->setToken($request_token['oauth_token'], $request_token['oauth_token_secret']);  // Saved somewhere in step 1.
$oauth_verifier = $_GET['oauth_verifier'];  // This comes back with the callback request.
$access_token = $client->getAccessToken($oauth_verifier); // The results of this call is what your application needs to store indefinitely.
```

You will need to save the token and token secret returned by the `getAccessToken()` call in your own location for later use.

Once you’ve saved the token and token secret, you will no longer need to use any of the authentication methods above. Simply call `$client->setToken();` and pass the token and secret immediately after instantiating your object instance.

For example:

```php
$options = [
    'AppName' => 'My Cool App/1.0 (http://app.com)',
];
$client = new phpSmug\Client('[YOUR_API_KEY]', $options));
$client->setToken('[OAUTH_TOKEN]', '[OAUTH_TOKEN_SECRET]');
$albums = $client->get('user/[YOUR_USERNAME]!albums');
```

You can see how to implement this three step process into your own application in the `example-oauth.php` example.
