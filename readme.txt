=== Transients Manager ===
Plugin URI: http://pippinsplugins.com/tranients-manager
Author URI: http://pippinsplugins.com
Contributors: mordauk
Donate link: http://pippinsplugins.com/support-the-site
Tags: transients
Requires at least: 3.6
Tested up to: 4.0
Stable Tag: 1.2.1

Provides a UI to manage your site's transients. You can view, search, edit, and delete transients at will.

== Description ==

This is a developer's tool that provides a UI to manage your site's transients. You can view, search, edit, and delete transients at will.

Have you found a bug or have a suggestion or improvement you'd like to submit? This plugin is available on [Github](https://github.com/pippinsplugins/Transients-Manager/) and pull requests are welcome!


== Screenshots ==

1. Transients manager

== Installation ==

1. Activate the plugin
2. Go to Tools > Transients

= How do I Show My Shopping Cart? =

_Does this work with sites that use object caching?_

Not at this time, it only works when transients are stored in the options table.


== Changelog ==

= 1.2.1 - January 26, 2015 =

* Fix: Properly detect class definitions that are passed to get_transient_value(), props @freemp 

= 1.2 - July 21, 2014 =

* New: Added an option to bulk delete all transients that have an expiration date, thanks to [Mike Grace](https://github.com/MikeGrace)

= 1.1 =

* New: Added support for deleting expired transients in bulk
* Fix: Bug with how the transient expiration date is determined
* Fix: Bug with how the expiration date is shown for transients that don't have an expiration date

= 1.0.1 =

* Fix: Bug with transients that include _transient_ in their name
* Fix: Bug with the way expired transients are displayed
* New: Added a languages folder with default language files

= 1.0 =

* First release!