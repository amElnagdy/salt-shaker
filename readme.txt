=== Salt Shaker ===
Contributors: nagdy, ahmedgeek
Tags: security, salts, salt keys, security keys, authentication keys, login, cookies, wp config
Requires at least: 4.0
Tested up to: 5.0
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Salt Shaker enhances WordPress security by changing WordPress security keys and salts manually and automatically.

== Description ==

Like Salt Shaker? Consider leaving a [5 star review](https://wordpress.org/support/plugin/salt-shaker/reviews/).

[youtube https://www.youtube.com/watch?v=SbbExLs7r8g]

**Salt Shaker Features**

* Improve your WordPress security.
* Easy to use, set it and forget it, with minimal settings.
* Manual and immediate WP security keys and salts changing.
* Set automated schedule for keys and salts change.

**Developers?**

Feel free to [fork the project on GitHub](https://github.com/amElnagdy/salt-shaker) and submit your contributions via pull request.

== Installation ==

1. Upload `salt-shaker` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. Navigate to `Tools > Salt Shaker` menu to configure the plugin.

== Frequently Asked Questions ==

= The plugin isn't working or have a bug? =

Post detailed information about the issue in the [support forum](http://wordpress.org/support/plugin/salt-shaker) and we will work to fix it.

== Screenshots ==
1. Plugin Settings.

== Changelog ==
= 1.2.2 =
* Redirect to the login page after the immediate change action.
* Check if wp-config.php is writable. How the heck this was missing?!

= 1.2.1 =
* Tested with the upcoming WordPress 5.0
* #11 - Added more interval times, quarterly and bianually.
* Fixed an issue with wp-config being in outside the root directory.
* Fixed a bug when updating the cron, now the old cron job is deleted.

= 1.2 =
* Tested with the upcoming WordPress 4.9
* #9 - Change salts if wp-config.php is moved one directory higher than the document root
* Setting the right permission to wp-config.php after changing the salts according to Codex recommendations.

= 1.1.6 =
* #8 - Change line endings to LF

= 1.1.5 =
* Security improvements

= 1.1.4 =
* Improvements:
** Ensure the user is administrator before processing AJAX requets
** Escape attributes using esc_attr_e

= 1.1.3 =
* WordPress 4.8 Compatibility.

= 1.1.2 =
* WordPress 4.7 Compatibility.

= 1.1.1 =
* Edited Arabic translation file.

= 1.1 =
* Few enhancements
* Multilingual Ready

= 1.0 =
* Initial Release