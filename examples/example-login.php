<html>
<head>
<title>phpSmug Email/Password Login Example</title>
</head>
<body>

<?php
/* Last updated with phpSmug 2.0
 *
 * This example file shows you how to get a list of albums from your own gallery, 
 * using your email address and password to authenticate and then display 
 * thumbnails of all the images in the first album found.
 *
 * You'll need to replace:
 * - <API KEY> with one provided by SmugMug: http://www.smugmug.com/hack/apikeys 
 * - <APP NAME/VER (URL)> with your application name, version and URL
 * - <EMAILADDRESS> with your email address
 * - <PASSWORD> with your SmugMug password
 *
 * The <APP NAME/VER (URL)> is NOT required, but it's encouraged as it will
 * allow SmugMug diagnose any issues users may have with your application if
 * they request help on the SmugMug forums.
 *
 * You can see this example in action at http://phpsmug.com/examples/
 */
require_once("../phpSmug.php");

try {
	$f = new phpSmug("APIKey=<API KEY>", "AppName=<APP NAME/VER (URL)>");
	// Login With EmailAddress and Password
	$f->login("EmailAddress=<EMAILADDRESS>", "Password=<PASSWORD>");	
	// Get list of  albums
	$albums = $f->albums_get();	
	// Get list of images and other useful information
	$images = $f->images_get("AlbumID={$albums['0']['id']}", "AlbumKey={$albums['0']['Key']}", "Heavy=1");
	$images = ($f->APIVer == "1.2.2") ? $images['Images'] : $images;
	// Display the thumbnails and link to the medium image for each image
	foreach ($images as $image) {
		echo '<a href="'.$image['MediumURL'].'"><img src="'.$image['TinyURL'].'" title="'.$image['Caption'].'" alt="'.$image['id'].'" /></a>';
	}
}
catch (Exception $e) {
	echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
}
?>
</body>
</html>
