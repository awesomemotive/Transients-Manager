=== Transients Manager ===
Contributors:      wpbeginner, smub, mordauk, johnjamesjacoby
Author:            WPBeginner
Author URI:        https://www.wpbeginner.com
Plugin URI:        https://wordpress.org/plugins/transients-manager/
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
License:           GNU General Public License v2 or later
Tags:              cron, tool, transient
Requires PHP:      5.6.20
Requires at least: 5.3
Tested up to:      6.5
Stable Tag:        2.0.5

Provides a familiar interface to view, search, edit, and delete Transients.

== Description ==

= Easily Manage Transients =

This is a developer tool that provides a user interface to manage transients.

You can easily view, search, edit, and delete transients from Tools > Transients.

A toolbar toggle allows you to suspend transient updates to help with testing and debugging.

= Features of Transients Manager =

* Toolbar button to suspend transient writes
* View all transients in the database
* Edit the name, expiration, and value of any transient
* Delete any transient
* Search transients by name
* Bulk actions to delete: all, expired, unexpired, or persistent transients

= Credits =

This plugin is owned by <a href="https://syedbalkhi.com/" rel="friend">Syed Balkhi</a> and maintained by the <a href="https://www.wpbeginner.com/" rel="friend">WPBeginner</a> team.

It was originally created by <a href="https://pippinsplugins.com/" rel="friend">Pippin Williamson</a>.

= What's Next =

If you like this plugin and find it useful to manage transients, please leave a good rating and consider checking out our other projects:

* [OptinMonster](https://optinmonster.com/) – Get more email subscribers with the most popular conversion optimization plugin for WordPress.
* [WPForms](https://wpforms.com/) – #1 drag & drop online form builder for WordPress (trusted by 5 million sites).
* [MonsterInsights](https://www.monsterinsights.com/) – See the stats that matter and grow your business with confidence. Best Google Analytics plugin for WordPress.
* [SeedProd](https://www.seedprod.com/) – Create beautiful landing pages with our powerful drag & drop landing page builder.
* [WP Mail SMTP](https://wpmailsmtp.com/) – Improve email deliverability for your contact form with the most popular SMTP plugin for WordPress.
* [RafflePress](https://rafflepress.com/) – Best WordPress giveaway and contest plugin to grow traffic and social followers.
* [Smash Balloon](https://smashballoon.com/) – #1 social feeds plugin for WordPress - display social media content in WordPress without code.
* [AIOSEO](https://aioseo.com/) – The original WordPress SEO plugin to help you rank higher in search results (trusted by over 3 million sites).
* [PushEngage](https://www.pushengage.com/) – Connect with visitors after they leave your website with the leading web push notification plugin.
* [TrustPulse](https://trustpulse.com/) – Add real-time social proof notifications to boost your store conversions by up to 15%.
* [SearchWP](https://searchwp.com/) – The most advanced custom WordPress search plugin to improve WordPress search quality.
* [AffiliateWP](https://affiliatewp.com/) – #1 affiliate management plugin for WordPress. Add a referral program to your online store.
* [WP Simple Pay](https://wpsimplepay.com/) – #1 Stripe payments plugin for WordPress. Start accepting one-time or recurring payments without a shopping cart.
* [Easy Digital Downloads](https://easydigitaldownloads.com/) – The best WordPress eCommerce plugin to sell digital products (eBooks, software, music, and more).
* [Sugar Calendar](https://sugarcalendar.com/) – A simple event calendar plugin for WordPress that’s both easy and powerful.

Visit [WPBeginner](https://www.wpbeginner.com/) to learn from our [WordPress Tutorials](https://www.wpbeginner.com/category/wp-tutorials/) and about the [best WordPress plugins](https://www.wpbeginner.com/category/plugins/).

= Thanks =

Transients Manager is the best way to manage transients in your WordPress site.

Our goal is to make using WordPress easy, both with our <a href="https://www.wpbeginner.com/wordpress-plugins/" rel="friend">WordPress plugins</a> and resources like <a href="https://www.wpbeginner.com/" rel="friend">WPBeginner</a>, the largest WordPress resource site for beginners.

I feel that we have done that here, and I hope you find Transients Manager useful.

Thank you,
–Syed Balkhi

== Screenshots ==

1. View Transients
2. Edit Transient

== Installation ==

1. Install Transients Manager by uploading the `transients-manager` directory to the `/wp-content/plugins/` directory. (See instructions on <a href="https://www.wpbeginner.com/beginners-guide/step-by-step-guide-to-install-a-wordpress-plugin-for-beginners/" rel="friend">how to install a WordPress plugin</a>.)
2. Activate Transients Manager through the `Plugins` menu in WordPress.
3. Go to Tools > Transients

== Frequently Asked Questions ==

= Does this work with Object Caching (Memcached, Redis, etc...)? =

No. It only works when transients are stored in the database.

== Changelog ==

= 2.0.5 - December 12, 2023 =
* Improved: Support for PHP8.2 and below

= 2.0.3 - August 2, 2022 =
* Misc: The plugin is tested up to WordPress 6.0

= 2.0.2 - December 23, 2021 =

* Fixed: "Delete All" bulk action works again
* Fixed: Add "Delete All" button to table actions
* Improved: More value types now visible in List Table (up to 100 characters)

= 2.0.1 - December 21, 2021 =

* Fix: PHP fatal error when guessing JSON value type

= 2.0.0 - December 16, 2021 =

* Added: Small-screen support
* Added: Value type hints
* Added: Ability to delete all without expiration
* Added: Site & Auto clean timestamps
* Added: CodeMirror support when editing
* Added: Notices on successful save & delete
* Added: Notice when using object caching
* Improved: Bulk actions
* Improved: List table styling
* Improved: Pagination styling
* Improved: Truncation of large keys & values
* Improved: Security & performance

= 1.8.1 - July 30, 2019 =

* Tweak: Added stripes to transients table rows for improved readability, props @malavvasita
* Tweak: Improved HTML markup and functionality of Cancel button on edit screens, props @cfoellmann

= 1.8 - November 20, 2019 =

* New: Added support for bulk deleting selected transients, props @ash0ur

= 1.7.7 - April 28, 2019 =

* Fix: Transient search not working.

= 1.7.6 - March 30, 2019 =

* Changes text domain from "pw-transients-manager" to "transients-manager" to support translations through WordPress.org.

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
