<?php 
/**
 * Copyright (c) 2008 Colin Seymour
 *
 * This file is part of phpSmug.
 *
 * phpSmug is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * phpSmug is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with phpSmug.  If not, see <http://www.gnu.org/licenses/>.
 */
 ?>
<html>
<head>
	<title>phpSmug Anonymous Login Example</title>
	<style type="text/css">
		body { background-color: #fff; color: #444; }
		div { width: 600px; margin: 0 auto; text-align: center; }
		img { border: 0;}
	</style>
</head>
<body>
	<div>
		<a href="http://phpsmug.com"><img src="phpSmug-logo.png" /></a>
		<h2>phpSmug Anonymous Login Example</h2>
<?php
/* Last updated with phpSmug 3.0
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
require_once( "../phpSmug.php" );

try {
	$f = new phpSmug( "APIKey=<API KEY>", "AppName=<APP NAME/VER (URL)>" );
	// Login Anonymously
	$f->login();	
	// Get list of public albums
	$albums = $f->albums_get( 'NickName=<NICKNAME>' );	
	// Get list of public images and other useful information
	$images = $f->images_get( "AlbumID={$albums['0']['id']}", "AlbumKey={$albums['0']['Key']}", "Heavy=1" );
	$images = ( $f->APIVer == "1.2.2" ) ? $images['Images'] : $images;
	// Display the thumbnails and link to the medium image for each image
	foreach ( $images as $image ) {
		echo '<a href="'.$image['MediumURL'].'"><img src="'.$image['TinyURL'].'" title="'.$image['Caption'].'" alt="'.$image['id'].'" /></a>';
	}
}
catch ( Exception $e ) {
	echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
}
?>
	</div>
</body>
</html>
