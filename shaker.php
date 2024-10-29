<?php

/**
 * Plugin Name: Salt Shaker
 * Plugin URI: https://nagdy.me/
 * Description: A plugin that changes WordPress Authentication Unique Keys and Salts to enhance and strengthen WordPress security.
 * Version: 1.4.6
 * Author: Nagdy
 * Author URI: https://nagdy.me/
 * License: GPLv2 or later
 * Text Domain: salt-shaker
 * Domain Path: /languages

 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

 * Copyright 2024 Nagdy.me.
 */


if (!defined('ABSPATH')) {
	exit;
}

/**
 * Let's make sure that Salt Shaker PRO is not active.
 *
 * @since 1.4.0
 */


function salt_shaker_pro_deactivate()
{
	if (is_plugin_active('salt-shaker-pro/shaker.php')) {
		deactivate_plugins('salt-shaker-pro/shaker.php');
	}
}
register_activation_hook(__FILE__, 'salt_shaker_pro_deactivate');


include_once(plugin_dir_path(__FILE__) . "_inc/freemius.php");
include_once(plugin_dir_path(__FILE__) . "_inc/loader.php");


/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 *
 * Hook in on the init action as it is recommended than using the plugins_loaded action.
 */

function salt_shaker_load_plugin_textdomain()
{
	load_plugin_textdomain('salt-shaker', FALSE, basename(dirname(__FILE__)) . '/languages/');
}
add_action('init', 'salt_shaker_load_plugin_textdomain');

/**
 * Add a link to the settings page on the plugins.php page.
 *
 * @param $actions
 * @param $plugin_file
 *
 * @return array         List of modified plugin action links.
 * @since 1.2.7
 *
 */

function salt_shaker_settings_link($actions, $plugin_file)
{
	static $plugin;

	if (!isset($plugin)) {
		$plugin = plugin_basename(__FILE__);
	}
	if ($plugin == $plugin_file) {
		$settings  = array('settings' => '<a href="' . esc_url(admin_url('/tools.php?page=salt_shaker')) . '">' . __('Settings', 'salt-shaker') . '</a>');
		$actions = array_merge($settings, $actions);
	}

	return $actions;
}
add_filter('plugin_action_links', 'salt_shaker_settings_link', 10, 5);




use SaltShaker\Salter;

$salt_shaker = new Salter();
