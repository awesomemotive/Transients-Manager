=== Transients Manager ===
Plugin URI: http://pippinsplugins.com/tranients-manager
Author URI: http://pippinsplugins.com
Contributors: mordauk
Donate link: http://pippinsplugins.com/support-the-site
Tags: transients
Requires at least: 3.6
Tested up to: 5.5
Stable Tag: 1.8.1

Provides a UI to manage your site's transients. You can view, search, edit, and delete transients at will.

== Description ==

This is a developer's tool that provides a UI to manage your site's transients. You can view, search, edit, and delete transients at will. A toolbar option is also provided that allows you to suspend transient updates to help with testing and debugging.

Provides following features:

- Button to suspend transient creation / update
- View all transients in database
- Edit the value, name, and expiration of any transient
- Delete any transient
- Search transients by name
- Button to delete all transients
- Button to delete all expired transients
- Button to delete all transients without an expiration

Have you found a bug or have a suggestion or improvement you'd like to submit? This plugin is available on [Github](https://github.com/pippinsplugins/Transients-Manager/) and pull requests are welcome!

== Screenshots ==

1. Transients manager

== Installation ==

1. Activate the plugin
2. Go to Tools > Transients

== Frequently Asked Questions ==

= Does this work with sites that use object caching? =

Not at this time, it only works when transients are stored in the options table.

== Changelog ==

= 1.8.1 - July 30, 2029

* Tweak: Added stripes to transients table rows for improved readability, props @malavvasita
* Tweak: Improved HTML markup and functionality of Cancel button on edit screens, props @cfoellmann

= 1.8 - November 20, 2019

* New: Added support for bulk deleting selected transients, props to Ash0ur

= 1.7.7 - April 28, 2019 =

* Fix: Transient search not working.

= 1.7.6 - March 30, 2019 =

* Changes text domain from "pw-transients-manager" to "transients-manager" in order to support translationsn through WordPress.org.

= 1.7.5 - July 24, 2018 =
* Fix: Language files not loading properly.

= 1.7.4 - January 8, 2018 =

* Fix: Site transients cannot be deleted

= 1.7.3 - October 10, 2016 =

* Updated translation files
* Bulk delete actions are now public so they can be accessed outside the plugin

= 1.7.2 - December 18, 2015 =

* Fix: Incorrect identification of site transients, props @ctalkington

= 1.7.1 - November 25, 2015 =

* Fix: Transients with "_site" in their name improperly processed

= 1.7 - October 20, 2015 =

* New: Added a Delete All Transients button, thanks @MatthewEppelsheimer

= 1.6.1 - September 3, 2015 =

* Fix: Only show Suspend Transients button to administrators

= 1.6 - August 18, 2015 =

* New: Added Toolbar option to suspend transient updates

= 1.5 - May 26, 2015 =

* Fix: Site wide transients not deleted when deleting transients with an expiration, props @freemp
* Fix: Undefined index notice when deleting transients with an expiration

= 1.4 - May 13, 2015 =

* Bug: Site transients were not supported

= 1.3 - January 29, 2015 =

* New: Added Cancel button to the edit screen, props @freemp
* New: Added Refresh button to the main screen, props @freemp
* New: Added German translation, props @freemp
* New: Added Brazilian Portuguese translation, props @freemp
* Fix: Replaced English PO file with POT file

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
