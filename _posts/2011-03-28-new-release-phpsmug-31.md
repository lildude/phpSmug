---
layout: news_item
title: "New Release: phpSmug 3.1"
date: "2011-03-28 16:42:39 +0100"
author: lildude
categories: release
---

Time for another update of phpSmug.  I've just made phpSmug 3.1 available.

This is only a minor update which features a few "behind the scenes" changes and fixes which do not change the functionality. The only thing that may appear to change functionality is the default API endpoint is now 1.2.2 instead of 1.2.0. All earlier endpoints are still available, but technically deprecated by SmugMug.

For the curious, the exact changes from the change log are:

* phpSmug now defaults to using the 1.2.2 API endpoint. All earlier endpoints are still available, but technically deprecated by SmugMug.
* Removed erroneous re-instantiation of processor when setting adapter.
* Corrected check for `safe_dir` OR `open_basedir` so fails over to socket connection correctly
* Improved connection settings

phpSmug 3.1 is now available from the [download](http://phpsmug.com/download) page.
