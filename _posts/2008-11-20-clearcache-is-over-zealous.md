---
layout: news_item
title: "clearCache() is Over Zealous"
date: "2008-11-20 13:41:23 +0000"
author: lildude
categories:
---

I've just found another bug ([#3 logged](http://phpsmug.com/bugs#3 "")) in phpSmug that you should be aware of.  The clearCache() function is over zealous when using the filesystem as the cache location.  The function deletes ALL files in the specified directory, not just it's own.

This isn't a problem if you've specified a unique directory for caching, but if you're using a shared caching directory, like you may do if including phpSmug in an application, then all the cache files are removed.

In order to rectify this, I'm going to have to change the caching filename or directory scheme to uniquely identify the phpSmug specific cache files so phpSmug can quickly and easily identify them when clearing the cache.

I'm considering changing phpSmug such that it creates it's own dedicated directory in the location specified in the enableCache() method call.  The DB caching method creates it's own table, so it only makes sense that the FS method creates it's own directory.

This bug does not affect database caching as phpSmug creates it's own table for the cache entries.

I'll fix this in the next release.
