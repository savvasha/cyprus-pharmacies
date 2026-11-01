=== Cyprus Pharmacies ===
Contributors: savvasha
License: GPLv2 or later
Tags: Cyprus, pharmacy, pharmacies, greek, night
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.3.0

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
```
add_filter( 'cypharm_cache_duration', function() {
    return 86400; // 24 hours in seconds
});
```

**Set cache to 1 hour:**
```
add_filter( 'cypharm_cache_duration', function() {
    return 3600; // 1 hour in seconds
});
```

**Set cache to 30 minutes:**
```
add_filter( 'cypharm_cache_duration', function() {
    return 1800; // 30 minutes in seconds
});
```

**Disable caching (not recommended for production):**
```
add_filter( 'cypharm_cache_duration', function() {
    return 0; // No caching
});
```

**Using a named function (recommended):**
```
function my_custom_cypharm_cache_duration() {
    return 7200; // 2 hours in seconds
}
add_filter( 'cypharm_cache_duration', 'my_custom_cypharm_cache_duration' );
```

= Credits =

* [National Opendata Portal](https://www.data.gov.cy/)

== External services ==

This plugin connects to the following external service to get the list of on-duty (all-night) pharmacies. It does not work without it.

**National Opendata Portal of Cyprus (data.gov.cy)**

The plugin requests the on-duty pharmacies schedule from the public API of the National Opendata Portal of the Republic of Cyprus.

* **When:** whenever a page containing the `[cypharm]` shortcode is rendered and the requested city and day (today or tomorrow) are not in the plugin's cache. Requests are made by your server, not by your visitors' browsers.
* **What is sent:** the city's dataset ID and the requested date. As with any HTTP request made by WordPress, the request includes a User-Agent header with your WordPress version and your site's URL, and your server's IP address is visible to the service.
* Provider: Department of Information Technology Services, Republic of Cyprus. [Disclaimer / terms of use](https://www.data.gov.cy/el/node/96), [Privacy policy](https://www.data.gov.cy/en/privacy-policy).

== Screenshots ==

1.  The all-night pharmacies of Paphos for 4th and 5th of January

== Changelog ==

= 1.3.0 =

* Fix: Today/tomorrow are now calculated in Cyprus time (Asia/Nicosia); between midnight and 02:00/03:00 the previous day used to be shown, and the day headings could disagree with the listed pharmacies.
* Fix: Empty results (e.g. a schedule that is not published yet) are no longer cached for 12 hours.
* Fix: Returning 0 from the `cypharm_cache_duration` filter now really disables caching (it used to cache forever).
* Fix: The day headings were never closed (`<h3>` instead of `</h3>`).
* Fix: No PHP warnings when a record lacks a field; map links behave the same for today and tomorrow.
* Fix: "Clear Cache" now also works with a persistent object cache (Redis, Memcached).
* Fix: The `cypharm_coordinates` filter can now be used from a theme's functions.php.
* Tweak: data.gov.cy requests time out after 8 seconds (was 15), ask for up to 50 records, and are logged only when `WP_DEBUG_LOG` is enabled.
* Tweak: Map links use HTTPS; translatable day heading format; escaping hardening; direct file access guards.
* DEV: Removed unused code (`CyPharm_Data::cypharms()`, the `cypharm_pharmacies` filter, unused properties).
* DEV: `CyPharm_Data::$date` is now a `Y-m-d` date.
* Readme: Added an "External services" section.
* Requirement: PHP 8.2 or newer (was 7.4).

= 1.2.9 =

* Tested up to WordPress 7.1
* DEV: Codebase brought in line with WordPress Coding Standards; no functional changes

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