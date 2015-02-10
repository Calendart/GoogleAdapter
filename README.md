CalendArt - Google Adapter
==========================
<!-- BADGES HERE WHEN IT SHALL BE OPENED ! //-->
Bridge between [CalendArt](http://github.com/CalendArt/CalendArt) and the 
[Google calendars api](https://developers.google.com/google-apps/calendar)

Installation
============
You have multiple ways to install this bridge. If you are unsure what to do, go
with [the archive release](#archive-release).

### Archive Release
1. Download the most recent release from the [release page](https://github.com/CalendArt/GoogleAdapter/releases)
2. Unpack the archive
3. Move the files somewhere in your project

### Development version
1. Install Git
2. `git clone git://github.com/CalendArt/GoogleAdapter.git`

### Via Composer
1. Install composer in your project: `curl -s http://getcomposer.org/installer | php`
2. Create a `composer.json` file (or update it) in your project root:

    ```javascript

      {
        "require": {
          "calendArt/google-adapter": "~1.0"
        }
      }
    ```

3. Install via composer : `php composer.phar install`

Running Tests
=============
```console
$ php composer.phar install --dev
$ phpunit
```

Credits
=======
Made with love by [@wisembly](http://wisembly.com/en/)
