# Change Log
All notable changes to this project will be documented in this file.
As of 4.0.0, this project adheres to [Semantic Versioning](http://semver.org/) and the format is based on the suggestions a <http://keepachangelog.com/>.

## [4.0.0] - 2015-12-31
### Added
- Support for SmugMug's v2 API.

### Changed
- Complete rewrite of phpSmug.
- Introduction of proper semantic versioning.
- Switched to using Guzzle for requests to the API.  This means more reliable and predictable behaviour and allows for easier future improvements in phpSmug without having to worry about maintaining a library that submits requests.
- All tests are now public and run on Travis CI with every push.
- phpSmug is now licensed under the MIT license.
- PSR-1, PSR-2, and PSR-4 coding standards are implemented and enforced by unit testing.
- phpSmug 4.0.0 is not backwardly compatible with phpSmug 3.5 and earlier.

### Removed
- Caching has been removed from this release.
- Support for SmugMug's 1.3.0 and earlier API.

## [3.5] - 2013-03-02
### Added
- Ability to sign an image URL with OAuth authentication parameters to allow embedding of "non-external" images within external sites. Fixes [#16](https://github.com/lildude/phpSmug/issues/16).
- `example-external-links.php` example to demonstrate the above.

### Changed
- Switched to using Markdown for the README file.
- Misc other little coding changes and improvements.

## [3.4] - 2011-06-21
### Fixed
- Added missing hidden header for image uploads that should be hidden. Fixes [#12](https://github.com/lildude/phpSmug/issues/12).

## [3.3] - 2011-06-03
### Fixed
- Worked around bizarre behaviour in the way PHP's `implode()` and `http_build_query()` handle associative array keys with empty values. Fixes [https://github.com/lildude/phpSmug/issues/11].

## [3.2] - 2011-05-30
### Added
- Implemented the ability to force all API communication to occur securely over HTTPS. OAuth Only. ([#9](https://github.com/lildude/phpSmug/issues/9))

### Changed
- phpSmug now uses the documented secure.smugmug.com API endpoints ([#8](https://github.com/lildude/phpSmug/issues/8))
- Updated OAuth example to use new Album URL and to remove its use of the deprecated `session_unregister()` PHP function.
- Improved support for the 1.3.0 API endpoint ([#10](https://github.com/lildude/phpSmug/issues/10))

## [3.1] - 2011-03-28
### Changed
- phpSmug now defaults to using the 1.2.2 API endpoint. All earlier endpoints are still available, but technically deprecated by SmugMug.
- Improved connection settings

### Fixed
- Removed erroneous re-instantiation of processor when setting adapter.
Corrected check for `safe_dir` OR `open_basedir` so fails over to socket connection correctly

## [3.0] - 2010-11-13
### Changed
- The `setProxy()` method now allows you to set a proxy username and password.
- phpSmug no longer depends on PEAR so no longer ships any PEAR modules.
- phpSmug is now 100% PHP 5 `E_STRICT` compliant ([#2](https://github.com/lildude/phpSmug/issues/2)).
phpSmug is now licensed under the GPLv3 license.

### Fixed
- OAuth token setting now works correctly again ([#7](https://github.com/lildude/phpSmug/issues/7)).

## [2.2] - 2010-07-21
### Added
- Added methods to handle calling of the various login.* methods offered by the API when using these instead of the single `login()` method offered by phpSmug. ([#6](https://github.com/lildude/phpSmug/issues/6))

### Changed
- https is forced for all calls that use OAuth with the PLAINTEXT signature method. WARNING: Uploads are however rejected by the API if you use PLAINTEXT (which is NOT the default).
- Upload filenames are now encoded to ensure spaces and non-ascii characters are correctly handled.
- `images_upload()` now honours any earlier `setProxy()` calls so uploads can occur through that proxy.
- `clearCache()` now takes a boolean argument to state whether you want the cache location to be removed when the cache is cleared. Default is FALSE, ie the cache location will NOT be removed.
- For my own benefit, I've now implemented a full PHPUnit test suite that checks all functionality of phpSmug.

### Fixed
- Failed upload responses and smugmug.auth.* method responses are no longer cached.

## [2.1] - 2009-09-27
### Added
- SmugMug's mode (ie read-only etc) is now stored in `$obj->mode` for easy checking of SmugMug's status.

### Changed
- Changed image_upload method to upload to upload.smugmug.com instead of api.smugmug.com. SmugMug made changes to enforce the use of upload.smugmug.com as uploading to api.smugmug.com was causing problems. ([#5](https://github.com/lildude/phpSmug/issues/))

### Fixed
- Resolved issue with recaching of cached data ([#4](https://github.com/lildude/phpSmug/issues/4)).
- Corrected "login with hash" example in the README file.

## [2.0.2] - 2009-02-22
### Changed
- Tidied up code so phpSmug.php is E_STRICT compliant and doesn't report any notice messages.

### Fixed
- Force error log level to be lower than E_STRICT due to limitation of PEAR modules (See notes in [#2](https://github.com/lildude/phpSmug/issues/2)).
- Resolved over-zealous `clearCache()` function ([#3](https://github.com/lildude/phpSmug/issues/3)).

## [2.0.1] - 2008-11-07
### Fixed
- Resolved issue where error code was not passed to `Exception()` line 350 ([#1](https://github.com/lildude/phpSmug/issues/1))

## [2.0.0] - 2008-10-31
### Changed
- phpSmug has been rewritten so it not a backwardly compatible drop-in replacement for phpSmug 1.x.
- Arguments to a method need to be passed either as a series of strings or an array.
- phpSmug is no longer compatible with PHP 4.
- Defaults to using the only stable endpoint provided by SmugMug: the 1.2.0 endpoint. However, it is fully functional with the later endpoint revisions. To use a later version of the endpoint, just set the version when instantiating the instance using `APIver`.
- All smugmug.login.* methods are handled by a single login() method.
- phpSmug now throws exceptions on error.
