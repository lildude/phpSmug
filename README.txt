phpSmug 2.1 - PHP Wrapper for the SmugMug API
=============================================

Written by Colin Seymour
Project Homepage: http://phpSmug.com/

Released under GNU Lesser General Public License
(http://www.gnu.org/copyleft/lgpl.html)

For more information about the class and upcoming tools and applications using
phpSmug, visit `http://phpsmug.com/'.

phpSmug is a PHP wrapper class for the SmugMug API and is based on work done by
Dan Coulter in phpFlickr (http://www.phpflickr.com) .


********************************************************************************

                                   *WARNING*

phpSmug 2.0 is *NOT* a drop in replacement for phpSmug 1.0.x of 1.1.x.
Please ensure you read this document for details on how phpSmug 2.x now
functions.

********************************************************************************



What's New in phpSmug 2.0
=========================

For those who've used phpSmug before, things have changed with phpSmug 2.0 and
hopefully it's for the good and won't be too much trouble to adapt your
applications for.

If you've not used phpSmug before, you can skip this section and move onto the
*Note Installation:: section below.

   * Method Arguments:

     The general functionality is the same, however the method of passing
     arguments to methods has changed.

     Now when you pass arguments to a method, you need to pass them either as a
     series of strings, for example:

          $f->images_getInfo("ImageID=<value>", "ImageKey=<value>");

     ... or as an array ...

          $f->images_getInfo(array("ImageID" => "<value>", "ImageKey" => "<value>"));

     This is a deliberate design decision to keep things consistent and is
     actually due to work I've done to ease the development and maintenance of
     phpSmug.

     You'll see phpSmug 2.0 is considerably smaller than previous versions.
     This is because phpSmug now uses PHP 5's `__call()' method to dynamically
     create the API calls for methods that are not explicitly declared.

     As a result of this, phpSmug 2.0 and later will definitely NOT work with
     PHP4.

     It also has the added bonus in that you no longer need to have empty
     arguments in your method calls: you only need to pass what's required or
     what you need.

   * SmugMug API Endpoint Compatibility

     phpSmug 2.0 defaults to using the only stable endpoint provided by
     SmugMug: the 1.2.0 endpoint.  However, it is fully functional with the
     later endpoint revisions, unless otherwise documented.

     To use a later version of the endpoint, just set the version when
     instantiating the instance using `APIver'.

   * All `smugmug.login.*' Methods Handled by a Single `login()' Method

     To simplfy things even further, I've consolidated all the
     `smugmug.login.*' API methods into a single `login()' method.

     phpSmug will determine which API method you wish to use from the arguments
     passed when calling the method. If not arguments are passed, phpSmug will
     login anonymously.

   * phpSmug now throws exceptions on error

     In order to take full advantage of PHP5 functionality and make phpSmug
     behave more like a proper PHP class, I've removed the "die_on_error"
     functionality and instead turned to using exceptions. It's up to you as
     the application developer to catch the exceptions and turn it into
     something useful for your users.  All the examples supplied with phpSmug
     now catch the exceptions.





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

Strings:

     $f = new phpSmug("APIKey=12345678", "AppName=My Cool App/1.0 (http://app.com)", "APIVer=1.2.2");

Associative Array:

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

          echo '<a href="'.$f->authorize("Access=[Public|Full]", "Permissions=[Read|Add|Modify]");.'">Authorize</a>';

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


   * `type' - Required This is "db" for database or "fs" for filesystem.



   * `dsn' - Required for type=db This a PEAR::DB DSN connection string, for
     example:

          mysql://user:password@server/database



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

   * Some people will need to use phpSmug from behind a proxy server.  You can
     use the `setProxy()' method to set the appropriate proxy settings.

     For example:

          $f = new phpSmug("APIKey=<value>");
          $f->setProxy("server=<proxy_server>", "port=<value>");

     All your calls will then pass through the specified proxy on the specified
     port.

   * If phpSmug encounters an error, or SmugMug returns a "Fail" response, an
     exception will be thrown and your application will stop executing.

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
`http://phpSmug.com/phpSmug-2.1'.

If you are using phpSmug and wish to let the world know, drop me a line via the
contact form at `http://phpsmug.com/about' and I'll add a link and brief
description to the sidebar on `http://phpsmug.com/'.

Oh, and by all means, please feel free to show your appreciation for phpSmug by
buying me a beer or two (see the sidebar at `http://phpsmug.com/').

This document is also available online at `http://phpsmug.com/docs'.

Change History
==============

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

