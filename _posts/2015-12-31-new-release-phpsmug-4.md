---
layout: news_item
title: "New Release: phpSmug 4.0.0"
date: "2015-12-31 16:42:02 +0000"
author: lildude
categories: release
---

Wow!!! Look at that, a new release of phpSmug!!! :grin:  phpSmug 4.0.0 is finally here.

Yes, I know, it's been a long time coming, but there hasn't been a need to release another version since I released phpSmug 3.5 back in March 2013, until SmugMug released their newest API, and even then, it was in a long beta.  The deprecation of the 1.3.0 API was the kick in the pants I needed to finish off phpSmug 4.0.0.

So what's new in phpSmug 4.0.0?  I'm glad you asked.

For a start, it's a complete rewrite.  Rather than maintaining my own code to perform the HTTP requests, I've switched to using [Guzzle](http://guzzle.readthedocs.org/en/latest/index.html).  This allows me to concentrate on phpSmug and let someone else concentrate on the finer details of actually talking HTTP.  It does however mean phpSmug 4.0.0 and later is _not_ a drop in replacement for phpSmug 3.x.

This has a big advantage in now phpSmug can take advantage of Guzzle's functionality without too much effort.  Whilst phpSmug still doesn't have support for asynchronous requests, it shouldn't be too hard to implement it in future as Guzzle already has this functionality.  This use of Guzzle also means we can extend phpSmug with relative ease.

phpSmug is now installed using the industry standard method of using [Composer](https://getcomposer.org/).  This makes it easier to integrate phpSmug with your projects and pull in all of the dependencies.

And of course, phpSmug is now compatible with SmugMug's vastly superior v2 API.  This has got to be one of the best APIs I've worked with.  This does unfortunately mean phpSmug 4.0.0 and later is _not_ backwardly compatible with the now deprecated 1.x.x API.

Some other changes include the publication of the test suite.  I used to have a test suite before, but due to the embedding of credentials, it was kept private.  The switch to Guzzle means I can use Mock objects to test phpSmug without revealing any credentials.

A few lesser changes and improvements:

- phpSmug now uses semantic versioning.
- phpSmug is now licensed under the [MIT license](https://opensource.org/licenses/MIT).
- Unit tests are run with every push to the GitHub repository.
- PSR-1, PSR-2, and PSR-4 coding standards are implemented and enforced by unit testing.

Other than that, phpSmug is pretty much the same as it was before.

So what's on the cards for phpSmug in future?

Well, I'd like to bring back caching, and in a significantly more intelligent method than before.  I'd also like to introduce asynchronous uploads for a start, and then maybe extend that to all requests. If you'd like to help out, please feel free to do so.  Check the [About](../about) page or the [CONTRIBUTING.md](https://github.com/lildude/phpSmug/blob/master/CONTRIBUTING.md) for more details on how you can help.

If you have any questions or hit any problems, please open an [issue](https://github.com/lildude/phpSmug/issues) and I'll do my best to help you out as soon as I can.

So what are you waiting for?  Go [download](../downloads) phpSmug 4.0.0 **NOW!!**
