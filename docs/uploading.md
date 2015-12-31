
# Uploading

Uploading is very easy. You can either upload an image from your local system, or from a location on the web.

In order to upload, you will need to have logged into SmugMug and have the album ID of the album you wish to upload to.

Then it’s just a matter of calling the method with the various optional parameters.

Whilst Guzzle supports asynchronous requests, phpSmug does not currently take advantage of this functionality so images can only be uploaded synchronously.

## Upload a Local Image

```php
# Optional options providing information about the image you're uploading.
$options = [
    'Altitude' => 1085,
    'Caption' => 'This is a photo from on top of Table Mountain',
    'FileName' => 'capetown.png',
    'Hidden' => false,
    'Keywords' => 'Cape Town; mountain; South Africa',
    'Latitude' => -34.045034,
    'Longitude' => 18.386065,
    'Pretty' => false,
    'Title' => 'From Table Mountain',
];

$response = $client->upload('album/r4nD0m', '/path/to/a/image.png', $options);
```

The `$options` you pass are all entirely optional and can be either in the short form shown above, or in the longer form [SmugMug documents](https://api.smugmug.com/api/v2/doc/reference/upload.html).


## Upload an Image from a URL

Uploading from a URL is slightly different in that you don't need to use the `upload()` method that uses a dedicated endpoint. Instead, you can POST to an album's `!uploadfromuri` endpoint passing the URL and any additional options:

```php
$options = [
    'Uri' => 'http://example.com/img/image.png',
    'Cookie' => 'foo',
    'Title' => 'Example.com Photo',
    'Caption' => 'This is a photo from example.com',
    'Hidden' => false,
    'FileName' => 'example.png',
    'Keywords' => 'example; photo',
];
$response = $client->post('album/r4nD0m!uploadfromuri', $options);
```

`Uri` (the source of the image) and `Cookie` (a string to send as the value of a Cookie header when fetching the source URI) are required options.


# Replacing Images

Replacing images is identical to uploading. The only difference is you need to specify the _full_ `ImageUri` of the image you wish to replace.

For example,

```php
$options = [
    'ImageUri' => '/api/v2/image/WxRHNQD-0',
];

$response = $client->upload('album/r4nD0m', '/path/to/a/replacement-image.png', $options);
```

Any other options provided will update those settings on the image.

You can't replace an image by uploading from a URL.
