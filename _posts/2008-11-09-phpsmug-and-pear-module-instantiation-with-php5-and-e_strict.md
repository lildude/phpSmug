---
layout: news_item
title: "phpSmug and PEAR Module Instantiation with PHP5 and E_STRICT"
date: "2008-11-09 09:45:01 +0000"
author: lildude
categories:
---

I'm in the process of creating a project using my phpSmug class and I've discovered I'm not quite 100% PHP5 E_STRICT compliant in two locations in the way I instantiate new PEAR objects:

```
105         $this-&gt;req = new HTTP_Request();
[...]
472         $upload_req = new HTTP_Request();
```

(105 and 472 are the line numbers.)

This instantiation by reference is going to result in errors similar to:

<code>PHP Fatal error:  Class '&lt;CLASSNAME&gt;' not found in /path/to/file.php on line ###</code>

... where &lt;CLASSNAME&gt; will possibly be a class that isn't even supplied as part of phpSmug and line ### will be the line in which the class has been instantiated using the "<code>=& new</code>" code.

There's no need to instantiate by reference anymore in PHP5.  PHP5 will actually throw the above error as it's ambiguous.  Now this is easy enough for me to fix in the files I ship (which I'll do in the next release), however it's more of a challenge to correct the files I have no control over, like PEAR modules.

Unfortunately, PEAR has (had? - the [source](http://pear.php.net/pepr/pepr-proposal-show.php?id=419 "") seems quite old) a backward compatibility requirement which unfortunately includes the requirement of instantiating objects by reference to keep PHP4 happy. PHP4 is now EOL, so maybe that requirement has been dropped.

I'm going to need to do some research to see how I can resolve this.  This may mean forcing the use of the classes supplied with phpSmug ahead of those supplied by your ISP/system. I provide these only for convenience at the moment, but I may need to change that to "necessity".

If you encounter a similar situation, you can resolve the issue by replacing all instances of "<code>=& new</code>" with "<code>= new</code>" when objects are being instantiated.

**Update**: It looks like this is only when E_STRICT error reporting is set.  Lowering the level allows things to work as expected. Ticket #2 logged to get phpSmug E_STRICT compliant.
