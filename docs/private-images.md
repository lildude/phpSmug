
# Display Private Images

By default, when you create a new gallery within SmugMug, you will be able to display/embed the images from within this gallery on external websites. If you change the gallery settings and set "Visibility" set to "Private (Only Me)", you will no longer be able to do that.

You can however use OAuth to sign your image URLs with your OAuth credentials using `signResource()` and display those images on an external site.

For example, you can display your private images using:

```php
foreach ($images->AlbumImage as $image) {
    printf('<a href="%s"><img src="%s" title="%s" alt="%s" width="150" height="150" /></a>', $image->WebUri, $client->signResource($image->ThumbnailUrl), $image->Title, $image->ImageKey);
}
```

See the `example-external-links.php` for a complete implementation example.

Keep in mind, these links are time based so you will need to regenerate the links every time the page is loaded. This may affect the rendering performance of the page containing these signed images.
