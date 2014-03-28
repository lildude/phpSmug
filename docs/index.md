---
layout: docs
title: Documentation
---

Requirements
============

phpSmug is written in PHP and utilises functionality supplied with PHP 5.2 and later and optionally PEAR.

From a PHP perspective, the only requirement is PHP 5.2 compiled with GD and optionally, curl support enabled.

If you wish to use a database for caching, you will also need the following PEAR packages:

   * [MDB2 2.5.0b3](http://pear.php.net/package/MDB2) or later.
   * The corresponding [MDB2_Driver_*](http://pear.php.net/search.php?q=MDB2_Driver&in=packages&setPerPage=20) for the database you wish to use.

Please consult the above links for details on installing the PEAR modules.


Installation
============

Copy the files from the installation package into a folder on your server. They need to be readable by your web server.  You can put them into an include folder defined in your php.ini file, if you like, though it's not required.


Usage
=====

To use phpSmug, all you have to do is include the file in your PHP scripts and create an instance.  For example:

    require_once("phpSmug/phpSmug.php");
    $f = new phpSmug(... arguments go here ...);

The constructor takes up to five arguments depending on which SmugMug API endpoint and authentication mechanism you wish to use:

   * `APIKey` - **Required for ALL endpoints**

     This is the API key you have obtained for your application from <http://www.smugmug.com/hack/apikeys>.

   * `OAuthSecret` - **Required for OAuth authentication (1.2.2 endpoint ONLY)**  
     Default: NULL

     This is the secret assigned to your API key and is displayed in the Settings tab of the SmugMug Control Panel. If no secret is displayed, select "change" next to the API key your application will use and click "save".  A secret will be generated for you.

     If you are not using OAuth authentication, then you don't need to worry about this argument.

   * `AppName` - Optional  
     Default: NULL

     This is the name, version and URL of the application you have built using the phpSmug. There is no required format, but something like:

         "My Cool App/1.0 (http://my.url.com)"

     ... would be very useful.

     Whilst this isn't obligatory, it is recommended as it helps SmugMug identify the application that is calling the API in the event one of your users reporting a problem on the SmugMug forums.

   * `APIVer` - Optional  
     Default: 1.2.0

     Use this to set the endpoint you wish to use.  The default is 1.2.0 as this is the latest "Stable" endpoint provided by SmugMug.

Arguments to all phpSmug methods must be provided as a series of strings or an associative array. For example:

* *Arguments as strings:*

       $f = new phpSmug("APIKey=12345678",
   	          "AppName=My Cool App/1.0 (http://app.com)",
   	          "APIVer=1.2.2");

* *Arguments as an associative array:*

       $f = new phpSmug(array("APIKey" => "12345678",
     	        "AppName" => "My Cool App/1.0 (http://app.com)",
     	        "APIVer" => "1.2.2")
     	       );

Naturally, you can predefine the array before instantiating the object and just pass the array variable.

phpSmug implements all methods and arguments as documented in the SmugMug API [documentation](http://wiki.smugmug.net/display/SmugMug/API).

To call a method, remove the "smugmug." part of the name and replace any fullstops with underscores. For example, instead of `smugmug.images.get`, you would call `images_get()`.

Remember: **ALL** function names and arguments **ARE** case sensitive.

There is no need to pass the `SessionID` or `oauth_token*` arguments to the various methods as phpSmug will automatically pass these, where applicable, unless otherwise documented. The exception is when using phpSmug to go through the OAuth authorization process detailed later.

`images_upload()` does not use the API for uploading, but instead HTTP PUT as recommended by SmugMug at <http://wiki.smugmug.net/display/SmugMug/Uploading>

HTTP PUT has been chosen as it's quicker, easier to use and more reliable than the other methods.



Authentication
==============

You must authenticate with SmugMug in order to use the API.

With the release of version 1.2.2 of the SmugMug API, there are now two methods to authenticate with SmugMug: standard email/password or userid/hash or OAuth. phpSmug allows you to implement either method in your application.

**Note: The 1.3.0 API endpoint only supports OAuth authentication.**

   * Method 1: EmailAddress/Password or UserID/Hash:

     This sets up your session ID required for interaction with the API using this authentication method.

     `login()` without any arguments will log you in anonymously and will allow you to access any public gallery, image or sharegroup.

     If you wish to access private albums and images, upload or change settings, you will need to login by providing either an EmailAddress/Password or UserID/Hash combination to login.

     For example, to login using an email address and password:

         $f->login("EmailAddress=you@domain.com", "Password=secretpassword");

     To login using a UserID and password hash (obtained from a previous Email/Password login):

         $f->login("UserID=<value>", "PasswordHash=<value>");

     Both methods will use HTTPS/SSL to ensure your username and password information is encrypted.

     Using a UserID and hash is probably the most secure method as your email and password can not be determined from the hash.  However, in order to obtain the hash and UserID, you need to login at least once using `login()` with the EmailAddress/Password combination.



   * Method 2: OAuth:

     This is the most secure of all the methods as your username and password are only ever entered on the SmugMug website.  If you've used Flickr's API, this very similar to the authorisation functionality Flickr uses.

     Authenticating using OAuth is a 3 step process.

     * First, you need to request an unauthorised request token:

           $resp = $f->auth_getRequestToken();

       Once you've obtained the token, you need to use it to direct the user to SmugMug to authorise your application.  You can do this in a variety of ways. It's up to you as the application developer to choose which method suits you. Ultimately, you need to direct the user to <http://api.smugmug.com/services/oauth/authorize.mg> with the required "Access", "Permissions" and the "oauth_token" arguments.

       phpSmug provides a simple method that generates a link you can use for redirection or for the user to click (it also takes care of passing the OAuth token too):

           echo '<a href="'.$f->authorize("Access=[Public|Full]", "Permissions=[Read|Add|Modify]").'">Authorize</a>';

       "Public" and "Read" are the default options for Access and Permissions respectively, so you can leave them out if you only need these permissions.

     * Once the user has authorized your application, you will need to request the access token and access token secret (once again phpSmug takes care of passing the relevant OAuth token):

           $token = $f->auth_getAccessToken();

       You will need to save the token and token secret returned by the `auth_getAccessToken()` call in your own location for later use.

     * Once you've saved the token and token secret, you will no longer need to use any of the authentication methods above.  Simply call `setToken("id=<value>", "Secret=<value>")` and pass the token ID and token secret immediately after instantiating your object instance.

       For example:

           $f = new phpSmug(array("APIKey" => "12345678",
        	     "AppName" => "My Cool App/1.0 (http://app.com)",
        	     "APIVer" => "1.2.2")
        	     );
           $f->setToken("id=<value>", "Secret=<value>");

       By default, phpSmug uses the HMAC-SHA1 signature method. This is the most secure signature method.  If you wish to use PLAINTEXT, simply set the `oauth_signature_method` class variable to `PLAINTEXT`.

           $f->oauth_signature_method = 'PLAINTEXT';

Caching
=======

Caching can be very important to a project as it can drastically improve the performance of your application.

phpSmug has caching functionality that can cache data to a database or files, you just need to enable it.

It is recommended that caching is enabled immediately after a new phpSmug instance is created, and before any other phpSmug methods are called.

To enable caching, use the `enableCache()` function.

The `enableCache()` function takes 4 arguments:

   * `type` - Required  
     This is "db" for database or "fs" for filesystem.

   * `dsn` - Required for type=db  
     This a PEAR::MDB2 DSN connection string, for example:

       mysql://user:password@server/database

     phpSmug uses the MDB2 PEAR module to interact with the database if you use database based caching.  phpSmug does *NOT* supply the necessary PEAR modules.  If you with to use a database for caching, you will need to download and install PEAR, the MDB2 PEAR module and the corresponding database driver yourself.  See [MDB2 Manual](http://pear.php.net/manual/en/package.database.mdb2.intro.php) for details.

   * `cache_dir` - Required for type=fs

     This is the folder/directory that the web server has write access to. This directory must already exist.

     Use absolute paths for best results as relative paths may have unexpected behaviour. They'll usually work, you'll just want to test them.

     You may not want to allow the world to view the files that are created during caching.  If you want to hide this information, either make sure that your permissions are set correctly, or prevent the webserver from displaying *.cache files.

     In Apache, you can specify this in the configuration files or in a .htaccess file with the following directives:

         <FilesMatch "\.cache$">
           Deny from all
         </FilesMatch>

     Alternatively, you can specify a directory that is outside of the web server's document root.

   * `cache_expire` - Optional  
     Default: 3600

     This is the maximum age of each cache entry in seconds.

   * `table` - Optional  
     Default: smugmug_cache

     This is the database table name that will be used for storing the cached data.  This is only applicable for database (db) caching and will be ignored if included for filesystem (fs) caching.

     If the table does not exist, phpSmug will attempt to create it.

Each of the caching methods can be enabled as follows:

Filesystem based cache:

    $f->enableCache("type=fs", "cache_dir=/tmp", "cache_expire=86400" );

Database based cache:

    $f->enableCache("type=db", "dsn=mysql://USERNAME:PASSWORD_database", "cache_expire=86400");

If you have caching enabled, and you make changes, it's a good idea to call `clearCache()` to refresh the cache so your changes are reflected immediately.


Display Unlinkable Images
=========================

**Note: This option is only available if you are using OAuth authentication.**

By default, when you create a new gallery within SmugMug, you will be able to display/embed the images from within this gallery on external websites.  If you change the gallery settings and set "External links" to "No", you will no longer be able to do that.

If you are using OAuth authentication, you can however sign your image URLs with your OAuth credentials using `signResource()` and display those images on an external site.

For example, you can display your "unlinkable" images using:

    <img src=" . $f->signResource( $img['TinyURL'] ) . " />

See the `example-external-links.php` for a complete implementation example.

Keep in mind, these links are time based so you will need to regenerate the links every time the page is loaded.  This may affect the rendering performance of the page containing these "signed" images.

As these links are time based, you won't be able to cache the HTML output, but you can still use the caching mechanisms supplied with phpSmug to cache the raw API data.


Uploading
=========

Uploading is very easy.  You can either upload an image from your local system, or from a location on the web.

In order to upload, you will need to have logged into SmugMug and have the album ID of the album you wish to upload to.

Then it's just a matter of calling the method with the various optional parameters.

For example, upload a local file using:

    $f->images_upload("AlbumID=123456", "File=/path/to/image.jpg");

... or from a remote site using:

    $f->images_uploadFromURL("AlbumID=123456", "URL=http://my.site.com/image.jpg");

If you want the file to have a specific name on SmugMug, then add the optional "FileName" argument.  If you don't specify a filename, the source filename will be used.

You can find a list of optional parameters, like caption and keywords on the API documentation page.


Replacing Photos
================

Replacing photos is identical to uploading.  The only difference is you need to specify the ImageID of the image you wish to replace.


Other Notes
===========

   * By default, phpSmug will attempt to use Curl to communicate with the SmugMug API endpoint if it's available.  If not, it will revert to using sockets based communication using `fsockopen()`.  If you wish to force the use of sockets, you can do so using the phpSmug supplied `setAdapter()' right after instantiating your instance:

         $f = new phpSmug("APIKey=<value>");
         $f->setAdapter("socket");

     Valid arguments are "curl" (default) and "socket".

   * Some people will need to use phpSmug from behind a proxy server.  You can use the `setProxy()` method to set the appropriate proxy settings.

     For example:

         $f = new phpSmug("APIKey=<value>");
         $f->setProxy("server=<proxy_server>", "port=<value>");

     All your calls will then pass through the specified proxy on the specified port.

     If your proxy server requires a username and password, then add those options to the `setProxy()` method arguments too.

     For example:

         $f = new phpSmug("APIKey=<value>");
         $f->setProxy("server=<proxy_server>",
                      "port=<value>",
                      "username=<proxy_username>",
                      "password=<proxy_password>");

   * By default phpSmug only uses HTTPS for authentication related methods like all the `login*()` and `*Token()` methods.  You can however force the use of HTTPS for ALL API calls, with the exception of uploads, by calling `setSecureOnly()` immediately after instantiating the object.

     For example:

         $f = new phpSmug("APIKey=<value>");
         $f->setSecureOnly();

     **NOTE**: Forcing the use of HTTPS for ALL API communication may have an impact on performance as HTTPS is inherently slower than HTTP.

   * If phpSmug encounters an error, or SmugMug returns a "Fail" response, an exception will be thrown and your application will stop executing. If there is a problem with communicating with the endpoint, a HttpRequestException will be thrown.  If an error is detected elsewhere, a PhpSmugException will be thrown.

     It is recommended that you configure your application to catch exceptions from phpSmug.

   * SmugMug occasionally puts the SmugMug site into read-only mode in order to carry out maintenance.  SmugMug's mode is now stored in the mode object variable (eg `$f->mode` for easy checking of SmugMug's status.  Note, this is not set for `login()` methods as the API doesn't return the mode for logins because you can't login when SmugMug is in read-only mode.  If SmugMug is not in read-only mode, this variable is empty.


Examples
========

phpSmug comes with 3 examples to help get you on your way.  All 3 examples perform the same thing, just using differing authentication methods.  They all show thumbnails of the first album found for the respective authentication methods:

   * `example-login.php` illustrates a username/password login.
   * `example-anon.php` illustrates an anonymous login.
   * `example-oauth.php` illustrates an OAuth login.
   * `example-external-links.php` illustrates displaying unlinkable images.


And that's all folks.

Keep up to date on developments and enhancements to phpSmug on it's new
dedicated site at <http://phpsmug.com/>.
