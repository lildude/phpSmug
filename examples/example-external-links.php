<?php
if (session_id() == '') {
    session_start();
}
require_once 'vendor/autoload.php';

/* Last updated with phpSmug 4.0
 *
 * This example file shows you how authenticate using OAuth and then display
 * images within the first gallery found.  This example is the same as that in
 * example-oauth.php, with the exception that it signs the image URLs with your
 * OAuth credentials using "$client->signResource()". See line 87 below.
 *
 * This is how you can display images from galleries that have the
 * gallery "Visibility" set to "Private (Only Me)".
 *
 * You'll want to set the following variables below:
 *
 * - $APIKey with one provided by SmugMug: http://www.smugmug.com/hack/apikeys
 * - $OAuthSecret with one provided when you obtained your API key
 * - $AppName with your application name, version and URL, eg
 *
 * The $AppName is NOT required, but it's encouraged as it will allow SmugMug to
 * diagnose any issues users may have with your application if they request help
 * on the SmugMug forums. A good format to use is "APP NAME/VER (URL)".
 *
 */

$APIKey = 'YOUR_API_KEY';
$OAuthSecret = 'YOUR_OAUTH_SECRET';
$AppName = 'YOUR_APP_NAME/VER (URL)';
?>
<html>
<head>
    <title>phpSmug External Links Example</title>
    <style type="text/css">
        body { background-color: #fff; color: #444; font-family: sans-serif }
        div { width: 750px; margin: 0 auto; text-align: center; }
        img { border: 0;}
    </style>
</head>
<body>
    <div>
        <a href="http://phpsmug.com"><img src="phpSmug-logo.svg" /></a>
        <h1>External Links Example</h1>
<?php

try {
    $options = [
        'AppName' => $AppName,
        '_verbosity' => 1, # Reduce verbosity to reduce the amount of data in the response and to make using it easier.
        'OAuthSecret' => $OAuthSecret, # You need to pass your OAuthSecret in order to authenticate with OAuth.
    ];

    $client = new phpSmug\Client($APIKey, $options);

    // Perform the 3 step OAuth Authorisation process.
    // NOTE: This is a very simplified example that does NOT store the final token.
    // You will need to ensure your application does.
    if (!isset($_SESSION['SmugGalReqToken'])) {

        // Step 1: Get a request token using an optional callback URL back to ourselves
        $callback = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['SCRIPT_NAME'];
        $request_token = $client->getRequestToken($callback);
        $_SESSION['SmugGalReqToken'] = serialize($request_token);

        // Step 2: Get the User to login to SmugMug and authorise this demo
        echo '<p>Click <a href="'.$client->getAuthorizeURL().'"><strong>HERE</strong></a> to Authorize This Demo.</p>';
        // Alternatively, automatically direct your visitor by commenting out the above line in favour of this:
        //header("Location:".$client->getAuthorizeURL());
    } else {
        $reqToken = unserialize($_SESSION['SmugGalReqToken']);
        unset($_SESSION['SmugGalReqToken']);

        // Step 3: Use the Request token obtained in step 1 to get an access token
        $client->setToken($reqToken['oauth_token'], $reqToken['oauth_token_secret']);
        $oauth_verifier = $_GET['oauth_verifier'];  // This comes back with the callback request.
        $token = $client->getAccessToken($oauth_verifier);  // The results of this call is what your application needs to store.
        // Get the username of the authenticated user
        $username = $client->get('!authuser')->User->NickName;
        // Get the first public album
        $albums = $client->get("user/{$username}!albums", array('count' => 1));
        // Get the first 25 photos in the album
        $images = $client->get($albums->Album[0]->Uris->AlbumImages, array('count' => 25));
        // Display the image thumbnails.
        foreach ($images->AlbumImage as $image) {
            printf('<a href="%s"><img src="%s" title="%s" alt="%s" width="150" height="150" /></a>', $image->WebUri, $client->signResource($image->ThumbnailUrl), $image->Title, $image->ImageKey);
        }
    }
} catch (Exception $e) {
    echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
}
?>
    </div>
</body>
</html>
