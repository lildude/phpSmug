<html>
<head>
<title>phpSmug Anonymous Login Example</title>
</head>
<body>

<?php
/* Last updated with phpSmug 2.0
 *
 * This example file shows you how to get a list of public albums for a 
 * particular SmugMug user, using their nickname, and then display thumbnails of
 * all the public images in the first album found.
 *
 * You'll need to replace:
 * - <API KEY> with one provided by SmugMug: http://www.smugmug.com/hack/apikeys 
 * - <APP NAME/VER (URL)> with your application name, version and URL
 * - <NICKNAME> with a SmugMug nickname.
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
	// Login Anonymously
	$f->login();	
	// Get list of public albums
	$albums = $f->albums_get('NickName=<NICKNAME>');	
	// Get list of public images and other useful information
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
