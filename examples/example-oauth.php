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
if (session_id() == "") { @session_start(); }
?>
<html>
<head>
	<title>phpSmug OAuth Login Example</title>
	<style type="text/css">
		body { background-color: #fff; color: #444; }
		div { width: 600px; margin: 0 auto; text-align: center; }
		img { border: 0;}
	</style>
</head>
<body>
	<div>
		<a href="http://phpsmug.com"><img src="phpSmug-logo.png" /></a>
		<h2>phpSmug OAuth Login Example</h2>
<?php
/* Last updated with phpSmug 3.0
 *
 * This example file shows you how to get a list of public albums for a 
 * particular SmugMug user, using their nickname, and then display thumbnails of
 * all the public images in the first album found.
 *
 * You'll want to replace:
 * - <API KEY> with one provided by SmugMug: http://www.smugmug.com/hack/apikeys 
 * - <APP NAME/VER (URL)> with your application name, version and URL
 * - <OAUTH SECRET> with the OAuth Secret associated with your API Key.
 *
 * The <APP NAME/VER (URL)> is NOT required, but it's encouraged as it will
 * allow SmugMug diagnose any issues users may have with your application if
 * they request help on the SmugMug forums.
 *
 * You can see this example in action at http://phpsmug.com/examples/
 */
require_once( "../phpSmug.php" );

try {
	$f = new phpSmug("APIKey=<API KEY>", "AppName=<APP NAME/VER (URL)>", "OAuthSecret=<OAUTH SECRET>");

	// Perform the 3 step OAuth Authorisation process.
	// NOTE: This is a very simplified example that does NOT store the final token. 
	// You will need to ensure your application does.
	if ( ! isset( $_SESSION['SmugGalReqToken'] ) ) {
		// Step 1: Get a Request Token
		$d = $f->auth_getRequestToken();
		$_SESSION['SmugGalReqToken'] = serialize( $d );

		// Step 2: Get the User to login to SmugMug and Authorise this demo
		echo "<p>Click <a href='".$f->authorize()."' target='_blank'><strong>HERE</strong></a> to Authorize This Demo.</p>";
        echo "<p>A new window/tab will open asking you to login to SmugMug (if not already logged in).  Once you've logged it, SmugMug will redirect you to a page asking you to approve the access (it's read only) to your public photos.  Approve the request and come back to this page and click REFRESH below.</p>";
        echo "<p><a href='".$_SERVER['PHP_SELF']."'><strong>REFRESH</strong></a></p>";
	} else {
		$reqToken = unserialize( $_SESSION['SmugGalReqToken'] );
		unset( $_SESSION['SmugGalReqToken'] );

		// Step 3: Use the Request token obtained in step 1 to get an access token
		$f->setToken("id={$reqToken['Token']['id']}", "Secret={$reqToken['Token']['Secret']}");
		$token = $f->auth_getAccessToken();	// The results of this call is what your application needs to store.
		
		// Set the Access token for use by phpSmug.   
		$f->setToken( "id={$token['Token']['id']}", "Secret={$token['Token']['Secret']}" );

		// Get list of public albums
		$albums = $f->albums_get( 'Heavy=True' );	
		// Get list of public images and other useful information
		$images = $f->images_get( "AlbumID={$albums['0']['id']}", "AlbumKey={$albums['0']['Key']}", "Heavy=1" );
		// Display the thumbnails and link to the Album page for each image
		foreach ( $images['Images'] as $image ) {
			echo '<a href="'.$image['URL'].'"><img src="'.$image['TinyURL'].'" title="'.$image['Caption'].'" alt="'.$image['id'].'" /></a>';
		}
	}
}
catch ( Exception $e ) {
	echo "{$e->getMessage()} (Error Code: {$e->getCode()})";
}
?>
	</div>
</body>
</html>
