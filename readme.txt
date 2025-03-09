=== Salt Shaker ===
Contributors: nagdy, ahmedgeek
Donate link: https://ko-fi.com/nagdy
Tags: security, salts, salt keys, security keys, authentication keys
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Salt Shaker enhances WordPress security by changing WordPress security keys and salts manually and automatically.

== Description ==

By using Salt Shaker plugin, you'll be able to harden your WordPress security. It allows you to change the salt keys either manually or automatically.

Try it out on a [free dummy site](https://demo.tastewp.com/salt-shaker).

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

= 2.0.0 =

* Major update with complete rewrite of the plugin architecture, while keeping the same functionality.
* Modern React-based admin interface with improved UX

Read the full changelog [here](https://saltshakerwp.com/changelog/)