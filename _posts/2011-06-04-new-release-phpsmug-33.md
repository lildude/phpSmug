---
layout: news_item
title: "New Release: phpSmug 3.3"
date: "2011-06-04 11:34:24 +0100"
author: lildude
categories:
---

Well, hot on the heels of phpSmug 3.2 comes phpSmug 3.3.

For a long time now, I've been working around what I thought was an undocumented change in the way the API was handling the boolean literal FALSE and empty strings.  My workaround seemed to work well for boolean literals, but fell apart when an empty string value was passed to the API, like when unsetting an album's password, as Anthony Humes discovered and from which I created ticket [#11](http://github.com/lildude/phpsmug/issues/11).

Well, after a bit of digging I discovered the problem was NOT with the API, but rather the way PHP's `implode()` and `http_build_query()` functions handle associative arrays with empty values. `implode()` seems to completely ignore the empty value when imploding and `http_build_query()` converts the empty value to a 0. Neither of which were desired behaviours and both of which I didn't notice until now.

phpSmug was using both of these methods in different places to effectively come to the same result - concat the keys and values into a single string for the POST data and for calculating the signature.

I have now fixed this by implemented a different method of reliably concatting the keys and values for submission to the API endpoint and for the signature calculation.  The result of this is now 0, an empty string and the boolean literal FALSE all equate to FALSE and works with the API.  Empty string values are now also correctly handled so doing things like unsetting passwords will now work too.

phpSmug 3.3 is now available from the [download](http://phpsmug.com/download) page.
