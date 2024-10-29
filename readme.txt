=== Salt Shaker ===
Contributors: nagdy, ahmedgeek
Donate link: https://www.buymeacoffee.com/nagdy
Tags: security, salts, salt keys, security keys, authentication keys
Requires at least: 4.0
Tested up to: 6.7
Stable tag: 1.4.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Salt Shaker enhances WordPress security by changing WordPress security keys and salts manually and automatically.

== Description ==

By using Salt Shaker plugin, you'll be able to harden your WordPress security. It allows you to change the salt keys either manually or automatically.

Try it out on a free dummy site:
Click here and you'll get the chance to see it in action → [https://demo.tastewp.com/salt-shaker](https://demo.tastewp.com/salt-shaker)

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

= Nothing happens? =

Make sure that `wp-config.php` file has the salt keys. If for any reason the keys aren't there; you can always generate a set of keys from this link https://api.wordpress.org/secret-key/1.1/salt/ and add it to your `wp-config.php` file. Once that's done, the plugin will be able to shake them based on your settings.

= The plugin isn't working or have a bug? =

Post detailed information about the issue in the [support forum](http://wordpress.org/support/plugin/salt-shaker) and we will work to fix it.

= Custom wp-config.php location? =

You can use this filter to define the  file location `salt_shaker_salts_file`. Example:
In this example, the new location of the config file is in a folder that's outside WordPress location in a folder called `wpsecret`. Make sure to replace it with your secret location ;)

`function salt_shaker_new_file($salts_file_name) {
    $salts_file_name = '../wpsecret/wp-config';
    return $salts_file_name;
}

add_filter('salt_shaker_salts_file', 'salt_shaker_new_file');`

== Screenshots ==
1. Plugin Settings.


== Changelog ==

= 1.4.6 =
* WordPress 6.7 compatibility.
* Generate salts locally if the API call fails.
* Updated Freemius SDK.

= 1.4.5 =
* WordPress 6.6 compatibility.
* Show admin notices only on the plugin page.

= 1.4.4 =
* WordPress 6.5 compatibility.

= 1.4.3 =
* WordPress 6.4 compatibility.

= 1.4.2 =
* Minor bug fixes.
* Updated Freemius SDK.

= 1.4.1 =
* Quick fix for the wp-salt file path.

= 1.4.0 =
* WordPress 6.2 compatibility.
* Support for wp-salt files.
* Introducing Salt Shaker PRO.

= 1.3.2 =
* WordPress 6.1 compatibility.

= 1.3.1 =
* WordPress 6.0 compatibility.
* Fix an issue with the AUTH_KEY and AUTH_SALT keys not being changed.

= 1.3.0 =
* Tested with WordPress 5.9.

= 1.2.9 =
* WordPress 5.8 compatibility.

= 1.2.8 =
* WordPress 5.7 compatibility.

= 1.2.7 =
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
