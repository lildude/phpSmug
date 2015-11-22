<?php
/* Last updated with phpSmug 4.0
 *
 * This example file shows you how to display the first 25 images in the first
 * public gallery found for a particular user without authenticating.
 *
 * You'll want to set the following variables below:
 *
 * - $APIKey with one provided by SmugMug: http://www.smugmug.com/hack/apikeys
 * - $AppName with your application name, version and URL, eg
 * - $username with the username you wish to query.
 *
 * The $AppName is NOT required, but it's encouraged as it will allow SmugMug to
 * diagnose any issues users may have with your application if they request help
 * on the SmugMug forums. A good format to use is "APP NAME/VER (URL)".
 *
 */

$APIKey = 'YOUR_API_KEY';
$AppName = 'YOUR_APP_NAME/VER (URL)';
$username = 'A_USERNAME';
?>
<html>
<head>
    <title>phpSmug First Album Example</title>
    <style type="text/css">
        body { background-color: #fff; color: #444; font-family: sans-serif; }
        div { width: 750px; margin: 0 auto; text-align: center; }
        img { border: 0;}
    </style>
</head>
<body>
    <div>
        <a href="http://phpsmug.com"><img src="phpSmug-logo.svg" /></a>
        <h2>phpSmug First Album Example</h2>
<?php

require_once 'vendor/autoload.php';

try {
    $options = [
      'AppName' => $AppName,
      '_verbosity' => 1, # Reduce verbosity to reduce the amount of data in the response and to make using it easier.
    ];

    $client = new phpSmug\Client($APIKey, $options);

    // Get the first public album
    $albums = $client->get("user/{$username}!albums", array('count' => 1));
    // Get the first 25 photos in the album
    $images = $client->get($albums->Album[0]->Uris->AlbumImages, array('count' => 25));
    // Display the image thumbnails.
    foreach ($images->AlbumImage as $image) {
        printf('<a href="%s"><img src="%s" title="%s" alt="%s" width="150" height="150" /></a>', $image->WebUri, $image->ThumbnailUrl, $image->Title, $image->ImageKey);
    }
} catch (Exception $e) {
    printf('%s (Error Code: %d)', $e->getMessage(), $e->getCode());
}
?>
    </div>
</body>
</html>
