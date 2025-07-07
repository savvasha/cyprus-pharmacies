=== Cyprus Pharmacies ===
Contributors: savvasha
License: GPLv2 or later
Tags: Cyprus, pharmacy, pharmacies, greek, night
Requires at least: 5.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.8

Show all-night today and tomorrow pharmacies of Cyprus

== Description ==
An easy way to show the all-night today and tomorrow pharmacies of Cyprus per city (Nicosia, Limassol, Larnaca, Paralimni, Paphos).

A simple example

`[cypharm show_title=false]`

**More Options**

The plugin supports the following optional parameters:

* `city`: select the Cyprus city between Nicosia, Limassol, Larnaca, Paralimni and Paphos (defaults to `Paphos`)
* `title`: Choose a custom title to use (defaults to `false`)

**Performance Features**

* **Caching**: API responses are cached for 12 hours to improve performance and reduce API calls
* **Cache Management**: Admin interface to clear cache when needed
* **Smart Loading**: Only makes API calls when cached data is expired or doesn't exist

**Developer Features**

* **Customizable Cache Duration**: Use the `cypharm_cache_duration` filter to modify cache duration
* **Cache Management**: Programmatic cache clearing with `clear_cache()` method
* **Extensible**: Easy to extend with additional caching features

== Customizing Cache Duration ==

You can customize the cache duration by adding the following code to your theme's `functions.php` file:

**Set cache to 24 hours:**
```php
add_filter( 'cypharm_cache_duration', function() {
    return 86400; // 24 hours in seconds
});
```

**Set cache to 1 hour:**
```php
add_filter( 'cypharm_cache_duration', function() {
    return 3600; // 1 hour in seconds
});
```

**Set cache to 30 minutes:**
```php
add_filter( 'cypharm_cache_duration', function() {
    return 1800; // 30 minutes in seconds
});
```

**Disable caching (not recommended for production):**
```php
add_filter( 'cypharm_cache_duration', function() {
    return 0; // No caching
});
```

**Using a named function (recommended):**
```php
function my_custom_cypharm_cache_duration() {
    return 7200; // 2 hours in seconds
}
add_filter( 'cypharm_cache_duration', 'my_custom_cypharm_cache_duration' );
```

= Credits =

* [National Opendata Portal](https://www.data.gov.cy/)

== Screenshots ==

1.  The all-night pharmacies of Paphos for 4th and 5th of January

== Changelog ==

= 1.2.8 =

* UX: Added "Settings" link to plugin action links for easy access
* UX: Improved admin interface with better code examples

= 1.2.7 =

* Performance: Added intelligent caching system to reduce API calls
* Performance: Cache pharmacy data for 12 hours to improve page load times
* Admin: Added cache management interface in Settings
* Developer: Added filter hook to customize cache duration
* Developer: Added cache clearing functionality for plugin activation/deactivation
* Security: Maintained all previous security improvements

= 1.2.6 =

* Security: Fixed XSS vulnerabilities by properly escaping all user data from external APIs
* Security: Added input validation for shortcode parameters to prevent invalid city values
* Security: Improved error handling for API responses with proper JSON validation
* Security: Added SSL verification to prevent man-in-the-middle attacks
* Security: Added proper user-agent headers for API requests
* Security: Enhanced error logging for debugging API issues

= 1.2.5 =

* Fix: data.gov schema was changed.

= 1.2.4 =

* Fix: Link to Google Maps is not working.

= 1.2.3 =

* Fix: data.gov schema uses multiple date formats.

= 1.2.2 =

* Fix: data.gov schema was changed.

= 1.2.1 =

* Tweak: Add a filter hook.
* Tweak: Add extra checks to avoid warnings for missing fields.

= 1.2.0 =

* Tweak: Code refactoring
* New: Now the plugin is ready to be translated to your language!

= 1.1.2 =

* FIX: Wrong surname was returned

= 1.1.1 =

* FIX: Wrong community and telephone numbers

= 1.1.0 =

* Link to the Google Maps position of the pharmacy for Paphos Area.

= 1.0.0 =

* First Release!