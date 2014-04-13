---
layout: news_item
title: "New Release: phpSmug 3.2"
date: "2011-05-30 10:33:48 +0100"
author: lildude
categories: release
---

Ladies and gentlemen, phpSmug 3.2 is now available for you.

Once again, this is a minor release and features a few "behind the scenes" changes and fixes which do not change the functionality. phpSmug should now work properly with the 1.3.0 API endpoint. I've also added the ability to force all API communication, except for uploads, to occur over HTTPS if you use OAuth for authentication. SmugMug are encouraging people away from using basic login authentication in favour of OAuth (the 1.3.0 endpoint has no support for basic authentication) so accordingly, I have not implemented the "secure only" functionality for basic authentication. I may add it at a later date if there is the demand for it.

The changelog entry for phpSmug 3.2 is...

* Improved support for the 1.3.0 API endpoint ([#10](https://github.com/lildude/phpSmug/issues/10))
* Implemented the ability to force all API communication to occur securely over HTTPS. OAuth Only. ([#9](https://github.com/lildude/phpSmug/issues/9))
* phpSmug now uses the documented secure.smugmug.com API endpoints ([#8](https://github.com/lildude/phpSmug/issues/8))
* Updated OAuth example to use new Album URL and to remove its use of the deprecated session_unregister() PHP function.
phpSmug 3.2 is now available from the [download](http://phpsmug.com/download) page.
