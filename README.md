flickr_photoset_downloader
==========================

CLI application in PHP that will download the highest available quality photos from a specified set.

flickr_photoset_downloader is intended to be a standalone CLI application. Use like this:

php -f flickr_photoset_downloader.php http://www.flickr.com/photos/someuser/sets/01234567890123456/

or like this: 

php -f flickr_photoset_downloader.php http://www.flickr.com/photos/someuser/sets/01234567890123456/ 291_example_32_char_api_key_7f8e

where the first parameter is the URL to the photo set, and the second optional parameter is the is the API Key you'd prefer to use. If you do not specify an API key, the default will be used, 29e1ef5030d211e2dd2813572d947f8e, which is registered and tracked by Matthew Poer and Flickr at http://www.flickr.com/services/apps/72157636529341406/

