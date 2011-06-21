phpSmug 3.4 - PHP Wrapper for the SmugMug API
=============================================

Written by Colin Seymour
Project Homepage: http://phpSmug.com/

phpSmug is a PHP wrapper class for the SmugMug API and is based on work done by
Dan Coulter in phpFlickr (http://www.phpflickr.com) .

Released under GNU General Public License version 3
(http://www.gnu.org/licenses/gpl.html)

Copyright (C) 2008 Colin Seymour

     This file is part of phpSmug.

     phpSmug is free software: you can redistribute it and/or modify it under
     the terms of the GNU General Public License as published by the Free
     Software Foundation, either version 3 of the License, or (at your option)
     any later version.

     phpSmug is distributed in the hope that it will be useful, but WITHOUT ANY
     WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     details.

     You should have received a copy of the GNU General Public License along
     with phpSmug.  If not, see <http://www.gnu.org/licenses/>.

For more information about the class and upcoming tools and applications using
phpSmug, visit `http://phpsmug.com/'.

Please help support the maintenance and development of phpSmug by making a
donation (http://phpsmug.com/donate).



What's New in phpSmug 3.4
=========================

Only a single small change in phpSmug 3.4: phpSmug was neglecting to set the
appropriate "hidden" upload header when uploading photos that should be marked
as hidden. phpSmug 3.4 now resolves this and "hidden" uploads should now show
up as hidden when they arrive on SmugMug.



Requirements
============

phpSmug is written in PHP and utilises functionality supplied with PHP 5.2 and
later and optionally PEAR.

From a PHP perspective, the only requirement is PHP 5.2 compiled with GD and
optionally, curl support enabled.

If you wish to use a database for caching, you will also need the following
PEAR packages:

   * MDB2 2.5.0b3 (http://pear.php.net/package/MDB2) or later.

   * The corresponding MDB2_Driver_*
     (http://pear.php.net/search.php?q=MDB2_Driver&in=packages&setPerPage=20)
     for the database you wish to use.

Please consult the above links for details on installing the PEAR modules.



Installation
============

Copy the files from the installation package into a folder on your server.
They need to be readable by your web server.  You can put them into an include
folder defined in your php.ini file, if you like, though it's not required.



Usage
=====

To use phpSmug, all you have to do is include the file in your PHP scripts and
create an instance.  For example:

     require_once("phpSmug/phpSmug.php");
     $f = new phpSmug(... arguments go here ...);

The constructor takes up to five arguments depending on which SmugMug API
endpoint and authentication mechanism you wish to use:

   * `APIKey' - Required for ALL endpoints

     This is the API key you have obtained for your application from
     `http://www.smugmug.com/hack/apikeys'.

   * `OAuthSecret' - Required for OAuth authentication (1.2.2 endpoint ONLY)
     Default: NULL

     This is the secret assigned to your API key and is displayed in the
     Settings tab of the SmugMug Control Panel. If no secret is displayed,
     select "change" next to the API key your application will use and click
     "save".  A secret will be generated for you.

     If you are not using OAuth authentication, then you don't need to worry
     about this argument.

   * `AppName' - Optional
     Default: NULL

     This is the name, version and URL of the application you have built using
     the phpSmug. There is no required format, but something like:

          "My Cool App/1.0 (http://my.url.com)"

     ... would be very useful.

     Whilst this isn't obligatory, it is recommended as it helps SmugMug
     identify the application that is calling the API in the event one of your
     users reporting a problem on the SmugMug forums.

   * `APIVer' - Optional
     Default: 1.2.0

     Use this to set the endpoint you wish to use.  The default is 1.2.0 as
     this is the latest "Stable" endpoint provided by SmugMug.

Arguments to all phpSmug methods must be provided as a series of strings or an
associative array. For example:

Arguments as strings:

     $f = new phpSmug("APIKey=12345678",
     	"AppName=My Cool App/1.0 (http://app.com)",
     	"APIVer=1.2.2");

Arguments as an associative array:

     $f = new phpSmug(array("APIKey" => "12345678",
     	"AppName" => "My Cool App/1.0 (http://app.com)",
     	"APIVer" => "1.2.2")
     	);

Naturally, you can predefine the array before instantiating the object and just
pass the array variable.

phpSmug implements all methods and arguments as documented in the SmugMug API
documentation (http://wiki.smugmug.net/display/SmugMug/API).

To call a method, remove the "smugmug." part of the name and replace any
fullstops with underscores. For example, instead of `smugmug.images.get', you
would call `images_get()'.

Remember: *ALL* function names and arguments *ARE* case sensitive.

There is no need to pass the `SessionID' or `oauth_token*' arguments to the
various methods as phpSmug will automatically pass these, where applicable,
unless otherwise documented. The exception is when using phpSmug to go through
the OAuth authorization process detailed later.

`images_upload()' does not use the API for uploading, but instead HTTP PUT as
recommended by SmugMug at `http://wiki.smugmug.net/display/SmugMug/Uploading'

HTTP PUT has been chosen as it's quicker, easier to use and more reliable than
the other methods.



Authentication
==============

You must authenticate with SmugMug in order to use the API.

With the release of version 1.2.2 of the SmugMug API, there are now two methods
to authenticate with SmugMug: standard email/password or userid/hash or OAuth.
phpSmug allows you to implement either method in your application.

Note: The 1.3.0 API endpoint only support OAuth authentication.



   * Method 1: EmailAddress/Password or UserID/Hash:

     This sets up your session ID required for interaction with the API using
     this authentication method.

     `login()' without any arguments will log you in anonymously and will allow
     you to access any public gallery, image or sharegroup.

     If you wish to access private albums and images, upload or change
     settings, you will need to login by providing either an
     EmailAddress/Password or UserID/Hash combination to login.

     For example, to login using an email address and password:

          $f->login("EmailAddress=you@domain.com", "Password=secretpassword");

     To login using a UserID and password hash (obtained from a previous
     Email/Password login):

          $f->login("UserID=<value>", "PasswordHash=<value>");

     Both methods will use HTTPS/SSL to ensure your username and password
     information is encrypted.

     Using a UserID and hash is probably the most secure method as your email
     and password can not be determined from the hash.  However, in order to
     obtain the hash and UserID, you need to login at least once using
     `login()' with the EmailAddress/Password combination.



   * Method 2: OAuth:

     This is the most secure of all the methods as your username and password
     are only ever entered on the SmugMug website.  If you've used Flickr's
     API, this very similar to the authorisation functionality Flickr uses.

     Authenticating using OAuth is a 3 step process.

   * First, you need to request an unauthorised request token:

          $resp = $f->auth_getRequestToken();

     Once you've obtained the token, you need to use it to direct the user to
     SmugMug to authorise your application.  You can do this in a variety of
     ways. It's up to you as the application developer to choose which method
     suits you. Ultimately, you need to direct the user to
     `http://api.smugmug.com/services/oauth/authorize.mg' with the required
     "Access", "Permissions" and the "oauth_token" arguments.

     phpSmug provides a simple method that generates a link you can use for
     redirection or for the user to click (it also takes care of passing the
     OAuth token too):

          echo '<a href="'.$f->authorize("Access=[Public|Full]", "Permissions=[Read|Add|Modify]").'">Authorize</a>';

     "Public" and "Read" are the default options for Access and Permissions
     respectively, so you can leave them out if you only need these permissions.

     Once the user has authorized your application, you will need to request
     the access token and access token secret (once again phpSmug takes care of
     passing the relevant OAuth token):

          $token = $f->auth_getAccessToken();

     You will need to save the token and token secret returned by the
     `auth_getAccessToken()' call in your own location for later use.

     Once you've saved the token and token secret, you will no longer need to
     use any of the authentication methods above.  Simply call
     `setToken("id=<value>", "Secret=<value>")' and pass the token ID and token
     secret immediately after instantiating your object instance.

     For example:

          $f = new phpSmug(array("APIKey" => "12345678",
          	"AppName" => "My Cool App/1.0 (http://app.com)",
          	"APIVer" => "1.2.2")
          	);
          $f->setToken("id=<value>", "Secret=<value>");

     By default, phpSmug uses the HMAC-SHA1 signature method. This is the most
     secure signature method.  If you wish to use PLAINTEXT, simply set the
     `oauth_signature_method' class variable to `PLAINTEXT'.

          $f->oauth_signature_method = 'PLAINTEXT';




Caching
=======

Caching can be very important to a project as it can drastically improve the
performance of your application.

phpSmug has caching functionality that can cache data to a database or files,
you just need to enable it.

It is recommended that caching is enabled immediately after a new phpSmug
instance is created, and before any other phpSmug methods are called.

To enable caching, use the `enableCache()' function.

The `enableCache()' function takes 4 arguments:


   * `type' - Required
     This is "db" for database or "fs" for filesystem.



   * `dsn' - Required for type=db
     This a PEAR::MDB2 DSN connection string, for example:

          mysql://user:password@server/database

     phpSmug uses the MDB2 PEAR module to interact with the database if you use
     database based caching.  phpSmug does *NOT* supply the necessary PEAR
     modules.  If you with to use a database for caching, you will need to
     download and install PEAR, the MDB2 PEAR module and the corresponding
     database driver yourself.  See MDB2 Manual
     (http://pear.php.net/manual/en/package.database.mdb2.intro.php) for
     details.



   * `cache_dir' - Required for type=fs

     This is the folder/directory that the web server has write access to. This
     directory must already exist.

     Use absolute paths for best results as relative paths may have unexpected
     behaviour. They'll usually work, you'll just want to test them.

     You may not want to allow the world to view the files that are created
     during caching.  If you want to hide this information, either make sure
     that your permissions are set correctly, or prevent the webserver from
     displaying *.cache files.

     In Apache, you can specify this in the configuration files or in a
     .htaccess file with the following directives:

          <FilesMatch "\.cache$">
             Deny from all
          </FilesMatch>

     Alternatively, you can specify a directory that is outside of the web
     server's document root.



   * `cache_expire' - Optional
     Default: 3600

     This is the maximum age of each cache entry in seconds.



   * `table' - Optional
     Default: smugmug_cache

     This is the database table name that will be used for storing the cached
     data.  This is only applicable for database (db) caching and will be
     ignored if included for filesystem (fs) caching.

     If the table does not exist, phpSmug will attempt to create it.

Each of the caching methods can be enabled as follows:

Filesystem based cache:

     $f->enableCache("type=fs", "cache_dir=/tmp", "cache_expire=86400" );

Database based cache:

     $f->enableCache("type=db", "dsn=mysql://USERNAME:PASSWORD_database", "cache_expire=86400");

If you have caching enabled, and you make changes, it's a good idea to call
`clearCache()' to refresh the cache so your changes are reflected immediately.



Uploading
=========

Uploading is very easy.  You can either upload an image from your local system,
or from a location on the web.

In order to upload, you will need to have logged into SmugMug and have the
album ID of the album you wish to upload to.

Then it's just a matter of calling the method with the various optional
parameters.

For example, upload a local file using:

     $f->images_upload("AlbumID=123456", "File=/path/to/image.jpg");

... or from a remote site using:

     $f->images_uploadFromURL("AlbumID=123456", "URL=http://my.site.com/image.jpg");

If you want the file to have a specific name on SmugMug, then add the optional
"FileName" argument.  If you don't specify a filename, the source filename will
be used.

You can find a list of optional parameters, like caption and keywords on the
API documentation page.



Replacing Photos
================

Replacing photos is identical to uploading.  The only difference is you need to
specify the ImageID of the image you wish to replace.



Other Notes
===========

   * By default, phpSmug will attempt to use Curl to communicate with the
     SmugMug API endpoint if it's available.  If not, it will revert to using
     sockets based communication using `fsockopen()'.  If you wish to force the
     use of sockets, you can do so using the phpSmug supplied `setAdapter()'
     right after instantiating your instance:

          $f = new phpSmug("APIKey=<value>");
          $f->setAdapter("socket");

     Valid arguments are "curl" (default) and "socket".

   * Some people will need to use phpSmug from behind a proxy server.  You can
     use the `setProxy()' method to set the appropriate proxy settings.

     For example:

          $f = new phpSmug("APIKey=<value>");
          $f->setProxy("server=<proxy_server>", "port=<value>");

     All your calls will then pass through the specified proxy on the specified
     port.

     If your proxy server requires a username and password, then add those
     options to the `setProxy()' method arguments too.

     For example:

          $f = new phpSmug("APIKey=<value>");
          $f->setProxy("server=<proxy_server>",
              "port=<value>",
              "username=<proxy_username>",
              "password=<proxy_password>");

   * By default phpSmug only uses HTTPS for authentication related methods like
     all the `login*()' and `*Token()' methods.  You can however force the use
     of HTTPS for ALL API calls, with the exception of uploads, by calling
     `setSecureOnly()' immediately after instantiating the object.

     For example:

     $f = new phpSmug("APIKey=<value>"); $f->setSecureOnly();

     NOTE: Forcing the use of HTTPS for ALL API communication may have an
     impact on performance as HTTPS is inherently slower than HTTP NOTE:
     phpSmug only implements this functionality for OAuth authenticated access.
     The actual `login*()' methods will continue to use HTTPS, but none of the
     other methods will if you authentication using basic login authentication.

   * If phpSmug encounters an error, or SmugMug returns a "Fail" response, an
     exception will be thrown and your application will stop executing. If
     there is a problem with communicating with the endpoint, a
     HttpRequestException will be thrown.  If an error is detected elsewhere, a
     PhpSmugException will be thrown.

     It is recommended that you configure your application to catch exceptions
     from phpSmug.

   * SmugMug occasionally puts the SmugMug site into read-only mode in order to
     carry out maintenance.  SmugMug's mode is now stored in the mode object
     variable (eg `$f->mode' for easy checking of SmugMug's status.  Note, this
     is not set for `login()' methods as the API doesn't return the mode for
     logins because you can't login when SmugMug is in read-only mode.  If
     SmugMug is not in read-only mode, this variable is empty.



Examples
========

phpSmug comes with 3 examples to help get you on your way.  All 3 examples
perform the same thing, just using differing authentication methods.  They all
show thumbnails of the first album found for the respective authentication
methods:

   * `example-login.php' illustrates a username/password login

   * `example-anon.php' illustrates an anonymous login

   * `example-oauth.php' illustrates an OAuth login

You can see the anonymous and OAuth login examples in action at
`http://phpsmug.com/examples'.


And that's all folks.

Keep up to date on developments and enhancements to phpSmug on it's new
dedicated site at `http://phpsmug.com/'.

If you encounter any problems with phpSmug, please check the list of known
issues with phpSmug and the API itself at `http://phpsmug.com/bugs'.  If your
issue is not there, please leave a comment on the revision page at
`http://phpSmug.com/phpSmug-32'.

If you are using phpSmug and wish to let the world know, drop me a line via the
contact form at `http://phpsmug.com/about' and I'll add a link and brief
description to the sidebar on `http://phpsmug.com/'.

If you use and find phpSmug useful, please help support its maintenance and
development by making a donation (http://phpsmug.com/donate).

This document is also available online at `http://phpsmug.com/docs'.

Change History
==============

   * 3.4 - 21 Jun '11


        * Added missing hidden header for image uploads that should be hidden.
          Fixes Ticket #12.

   * 3.3 - 3 Jun '11


        * Worked around bizarre behaviour in the way PHP's implode() and
          http_build_query() handle associative array keys with empty values.
          Fixes Ticket #11.

   * 3.2 - 30 May '11


        * Improved support for the 1.3.0 API endpoint (Ticket #10)

        * Implemented the ability to force all API communication to occur
          securely over HTTPS. OAuth Only. (Ticket #9)

        * phpSmug now uses the documented secure.smugmug.com API endpoints
          (Ticket #8)

        * Updated OAuth example to use new Album URL and to remove its use of
          the deprecated session_unregister() PHP function.

   * 3.1 - 28 Mar '11


        * phpSmug now defaults to using the 1.2.2 API endpoint. All earlier
          endpoints are still available, but technically deprecated by SmugMug.

        * Removed erroneous re-instantiation of processor when setting adapter.

        * Corrected check for safe_dir OR open_basedir so fails over to socket
          connection correctly

        * Improved connection settings

   * 3.0 - 13 Nov '10


        * The setProxy() method now allows you to set a proxy username and
          password.

        * OAuth token setting now works correctly again (Ticket #7).

        * phpSmug no longer depends on PEAR so no longer ships any PEAR modules.

        * phpSmug is now 100% PHP 5 E_STRICT compliant (Ticket #2).

        * phpSmug is now licensed under the GPLv3 license.

   * 2.2 - 21 Jul '10


        * https is forced for all calls that use OAuth with the PLAINTEXT
          signature method. WARNING: Uploads are however rejected by the API if
          you use PLAINTEXT (which is NOT the default).

        * Failed upload responses and smugmug.auth.* method responses are no
          longer cached.

        * Upload filenames are now encoded to ensure spaces and non-ascii
          characters are correctly handled.

        * images_upload() now honours any earlier setProxy() calls so uploads
          can occur through that proxy.

        * clearCache() now takes a boolean argument to state whether you want
          the cache location to be removed when the cache is cleared. Default
          is FALSE, ie the cache location will NOT be removed

        * Added methods to handle calling of the various login.* methods
          offered by the API when using these instead of the single login()
          method offered by phpSmug. (Ticket #6)

        * For my own benefit, I've now implemented a full PHPUnit test suite
          that checks all functionality of phpSmug.

   * 2.1 - 27 Sep '09


        * Changed image_upload method to upload to upload.smugmug.com instead
          of api.smugmug.com. SmugMug made changes to enforce the use of
          upload.smugmug.com as uploading to api.smugmug.com was causing
          problems. (Ticket #5)

        * Resolved issue with recaching of cached data (Ticket #4).

        * SmugMug's mode (ie read-only etc) is now stored in $obj->mode for
          easy checking of SmugMug's status.

        * Corrected "login with hash" example in the README file.

   * 2.0.2 - 22 Feb '09


        * Tidied up code so phpSmug.php is E_STRICT compliant and doesn't
          report any notice messages.

        * Force error log level to be lower than E_STRICT due to limitation of
          PEAR modules (See notes in Ticket #2).

        * Resolved over-zealous clearCache() function (Ticket #3).

   * 2.0.1 - 7 Nov '08


        * Resolved issue where error code was not passed to Exception() line
          350 (Ticket #1)

   * 2.0 - 30 Oct '08


        * Removed die_on_error functionality in favour of exceptions

        * Remove getErrorCode() and getErrorMsg() functions as no longer
          provide die_on_error functionality. Error codes and msgs are passed
          via Exception.

        * Tidied up PEAR pkgs included to only include the bare minimum (these
          are provided to ease implementation after all)

        * Updated HTTP_Request to 1.4.3

        * Added OAuth support for 1.2.2 endpoint.  Defaults to using HMAC-SHA1
          as it's the most secure with minimal perf issues.

        * Initial phpSmug 2.0 - Obsoletes ALL previous versions of phpSmug.

