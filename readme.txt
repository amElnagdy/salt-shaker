=== Salt Shaker ===
Contributors: nagdy, ahmedgeek
Donate link: https://www.buymeacoffee.com/nagdy
Tags: security, salts, salt keys, security keys, authentication keys, login, cookies, wp config
Requires at least: 4.0
Tested up to: 5.5
Stable tag: 1.2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Salt Shaker enhances WordPress security by changing WordPress security keys and salts manually and automatically.

== Description ==

By using Salt Shaker plugin, you'll be able to harden your WordPress security. It helps you changing the salt keys either manually or automatically.

**Why Use SALT Keys in WordPress?**

When you log in to WordPress, you have the option to remain logged in long-term. To achieve this, WordPress stores your login data in cookies instead of in a PHP session. Malicious individuals can hijack your cookies through various means, leaving your website vulnerable.

To make it harder for attackers to use cookie data, you can take advantage of SALT keys. WordPress SALT keys encrypt your password, making it harder to guess. What’s more, it’s next to impossible for hackers to simply ‘unscramble’ the result in order to get at the original password.

[Read more on WPEngine Blog](https://wpengine.com/resources/generate-wordpress-salt-keys/#Why_Use_SALT_Keys_in_WordPress)

**What people says about Salt Shaker**

[WPBeginner](https://www.wpbeginner.com/wp-tutorials/how-to-automatically-change-wordpress-salt-keys/)
[Kinsta](https://kinsta.com/knowledgebase/wordpress-salts/)
[WPEngine](https://wpengine.com/resources/generate-wordpress-salt-keys/)
[Elgenat Themes](https://www.elegantthemes.com/blog/tips-tricks/what-are-wordpress-salt-keys-and-how-can-you-change-them)
[Hostinger](https://www.hostinger.com/tutorials/wordpress-salts)

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

=1.2.7 =
* WordPress 5.5 compatibility.

= 1.2.6 =
* WordPress 5.4 compatibility.
* Replacing some functions with standard WP functions.

= 1.2.5 =
* Enhanced internationalization.
* WordPress 5.3 compatibility.

= 1.2.4 =
* Keeping the original permissions of the config file.
* Performance improvement

= 1.2.3 =
* Changing the config permission to 0640
* Added: filters for additional salts

= 1.2.2 =
* Tested with WordPress 5.1.
* Added: link to the settings page from the plugins page.
* Added: redirect to the login page after the immediate change action.
* Added: check if wp-config.php is writable. How the heck this was missing?!
* Added: Filter to define a custom salts file. salt_shaker_salts_file

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