# Uploading

```php
$options = [
  'Altitude' => 1085,
  'Caption' => 'This is a photo from on top of Table Mountain',
  'FileName' => 'image.jpg',
  'Hidden' => false,
  'Keywords' => 'Cape Town; mountain; South Africa',
  'Latitude' => -34.045034,
  'Longitude' => 18.386065,
  'Pretty' => false,
  'Title' => 'From Table Mountain',
];

$response = $client->upload('/api/v2/album/r4nD0m', '/path/to/a/image.png', $options);
```

# Replacing Photos

Replacing photos is identical to uploading. The only difference is you need to specify the `ImageUri` of the image you wish to replace.

For example,

```php
$options = [
  'ImageUri' => '/api/v2/image/WxRHNQD-0',
];

$response = $client->upload('/api/v2/album/r4nD0m', '/path/to/a/replacement-image.png', $options);
```

Any other options provided will update those settings on the image.
