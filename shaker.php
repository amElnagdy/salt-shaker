<?php
/*
Plugin Name: Salt Shaker
Plugin URI: https://nagdy.net/
Description: A plugin that changes the WP salt values to enhance and strengthen WordPress security.
Version: 1.2.3
Author: Nagdy
Author URI: https://nagdy.net/
License: GPLv2 or later
Text Domain: salt-shaker
Domain Path: /languages
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2019 Nagdy.net.
*/

include_once(plugin_dir_path(__FILE__) . "_inc/loader.php");
$salt_shaker = new Salter();

function salt_shaker_load_plugin_textdomain() {
    load_plugin_textdomain( 'salt-shaker', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'salt_shaker_load_plugin_textdomain' );

/**
 * Add a link to the settings page on the plugins.php page.
 *
 * @since 1.2.2
 *
 * @param  array $links List of existing plugin action links.
 * @return array         List of modified plugin action links.
 */
function salt_shaker_settings_link($links)
{
    $links = array_merge(array(
        '<a href="' . esc_url(admin_url('/tools.php?page=salt_shaker')) . '">' . __('Settings', 'salt-shaker') . '</a>'
    ), $links);
    return $links;
}

add_action('plugin_action_links_' . plugin_basename(__FILE__), 'salt_shaker_settings_link');